<?php defined('SYSPATH') OR die('No direct script access.');

class GPIO 
{
	const BOARD = 0;
	const BCM	= 1;
	
	const NONE = null;
	const INPUT = 1;
	const OUTPUT = 2;
	
	const EDGE_NONE = 0;
	const EDGE_RISING = 1;
	const EDGE_FALLLING = 2;
	const EDGE_BOTH = 3;
	
	/**
	 * Wheteher to use BCM or Board pin numbering.
	 * 
	 * @var integer
	 */
	protected $__mode;
	
	/**
	 * Path to the GPIO pin space
	 * 
	 * @var string 
	 */
	protected $_path = '/sys/class/gpio/gpio';
	
	/**
	 * Internally we always use BCM
	 * 
	 * Holds the mapping from BCM to BOARD numbering.
	 * 
	 * Index is the BOARD number
	 * Values are the BCM pin that has to be changed by the Pi
	 * 
	 * See https://projects.drogon.net/raspberry-pi/wiringpi/pins/
	 */
	protected $__relations = array(
		 1 => null,
		 2 => null,
		 3 => 2,	// Changes for R1 to R2 boards
		 4 => null,
		 5 => 3,	// Changes for R1 to R2 boards
		 6 => null,
		 7 => 4,
		 8 => 14,
		 9 => null,
		10 => 15,
		11 => null,
		12 => 18,
		13 => 21,	// Changes for R1 to R2 boards, and unreliable
		14 => null,
		15 => 22,
		16 => null,	// Is the "Timer" pin, 23,
		17 => null,
		18 => 24,
		19 => 10,
		20 => null,
		21 => 9,
		22 => 25,
		23 => 11,
		24 => 8,
		25 => null,
		26 => 7,
	);
	
	/**
	 * Which pins are GPIO capable pins in BCM mode.  
	 * If it's not on the list, it's not a GPIO.
	 * 
	 * Can be used as a check to prevent screwing up
	 */
	protected $__pins_gpio = array(2,3,4,7,8,9,10,11,14,15,18,21,22,24,25);
	/**
	 * Could be used as input or output.  Turns on/off every half second.
	 * @var array 
	 */
	protected $__pins_timer = array(23);
	/**
	 * 5v Pins.  Cant be used.
	 * @var array
	 */
	protected $__pins_5v = array( 1, 5);
	/**
	 * 3v Pins.  Cant be used.
	 * @var array
	 */
	protected $__pins_3v = array();
	/**
	 * Ground Pins.  Cant be used.
	 * @var array
	 */
	protected $__pins_gnd = array();
	
	/**
	 * List of active pins and what they are used for. 
	 * @var array
	 */
	protected $__pins = array();
	
	/**
	 * Not a static class.
	 */
	public function __construct($mode = self::BCM) 
	{
		$this->set_mode( $mode );
		
		if( !file_exists( dirname($this->_path) ))
		{
			throw new Kohana_Exception('GPIO pins are unavailable');
		}
		else if( !is_writable( $this->_path.reset($this->_pins).'/value' ) )
		{
			throw new Kohana_Exception('Unable to write to GPIO pins');			
		}
		
		foreach($this->__pins_gpio as $pin)
		{
			$this->__pins[$pin] = self::NONE;
		}
	}
	
	/**
	 * Accepts a pin number in Board (human readable) mode.  This is 2 to the 
	 * top left, 1 to bottom left, 26 top right, and 25 bottom right.
	 * 
	 * If we state we're using BOARD, then we need to change it.
	 * 
	 * This returns the ID you need to change in BCM (as the computer sees 
	 * them) mode. 
	 * 
	 * @param integer $id
	 * @return integer
	 */
	public function to_pin_no($id)
	{
		if($this->__mode == self::BOARD)
		{
			if(isset($this->__relations[$id]))
			{
				return $this->__relations[$id];		
			}
		}
		return $id;
	}
	
	/**
	 * Set the mode we're using.  The PI uses BCM, so we default to that.
	 * 
	 * @param type $mode
	 */
	public function set_mode($mode = self::BCM)
	{
		if($this->__mode == self::BOARD)
		{
			$this->__mode = self::BOARD;
		}
		else
		{
			$this->__mode = self::BCM;
		}
	}
	
	/**
	 * 
	 * @param type $pin
	 * @param type $direction
	 * @throws Kohana_Exception
	 */
	public function pin_setup($pin, $direction = self::OUTPUT)
	{
		if($this->__pins[$pin] == self::NONE)
		{
			/* We dont do the "export" action because it would require us
			 * to have root. As such, we just maintain a list.
			*/
			$this->__pins[$pin] = $direction;
			$value = ($direction == self::OUTPUT)?'out':'in';
			try
			{
				$value = file_put_contents($this->_path.$pin.'/direction', $value);
			} 
			catch(Exception $e)
			{
				throw new Kohana_Exception('Unable to setup GPIO pin');	
			}
		}
		else 
		{
			throw new Kohana_Exception('Re-initialising a pin that is in use');
		}
	}
	
	/**
	 * Reads the value of pin.  
	 * 
	 * @param integer $pin
	 * @return bool
	 */
	public function pin_input($pin)
	{
		if( isset($this->__pins[$pin]) && $this->__pins[$pin] == self::INPUT)
		{
			try
			{
				$value = file_get_contents($this->_path.$pin.'/value');
			} 
			catch(Exception $e)
			{
				throw new Kohana_Exception('Unable to read from GPIO pins');	
			}
			return ($value == 1);
		}
		else
		{
			throw new Kohana_Exception('Attempt to read from non-input pin');
		}
		return false;
	}
	
	/**
	 * Sets the value of GPIO pin to value
	 * 
	 * @param integer $pin
	 * @param bool $value
	 */
	public function pin_output($pin, $value)
	{
		if( isset($this->__pins[$pin]) && $this->__pins[$pin] == self::OUTPUT)
		{
			try
			{
				$value = file_put_contents($this->_path.$pin.'/value', ($value == 1));
			} 
			catch(Exception $e)
			{
				throw new Kohana_Exception('Unable to write to GPIO pins');	
			}
		}		
		else
		{
			throw new Kohana_Exception('Attempt to write to a non-output pin');
		}
		return $value;
	}
	
	/**
	 * 
	 * Static function to add a detection event to the 
	 * 
	 * @param integer $id
	 * @param integer $status
	 * @param string $req
	 */
	public static function add_event_detect( $id, $status, $req )
	{
		
	}
}
