***
*** Jennic module programmer v0.7
***

From: https://github.com/fairecasoimeme/ZiGate/tree/master/Tools/JennicModuleProgrammer

Some updates done
- Added full detection of 5168 models (zigate v1 was displayed as "unknown" chip)
- Changed model detection to be performed BEFORE baudrate.
  This allows to display a communication error with JN5168 module which might be due to wrong GPIO (RESET & FLASH) settings
  instead of getting "Error setting baudrate".
- For Abeille, build directory is located in "tmp"
