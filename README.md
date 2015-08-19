koh-gpio
========

Kohana module to provide access to GPIO functions (on a Raspberry pi).

Current features include reading and writing to the pins, but I did not get time to work on setting up events.  Perhaps in the new year...

If accessing pins is not working on your Pi, it's probably because only root can read or write to them.  Look at the noroot-gpio option in https://github.com/Cavey/pi-tools for a script to set them to be non-root accessible.
