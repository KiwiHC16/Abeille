***
*** Jennic module programmer v0.7
*** Tcharp38 updated version for Abeille
***

From: https://github.com/fairecasoimeme/ZiGate/tree/master/Tools/JennicModuleProgrammer

Modifications history
=====================
12/sep/24 T38 Package revisited to be standalone + added 'flashZigate.sh' script
2022-23   T38 Added full detection of 5168 models (zigate v1 was displayed as "unknown" chip)
                Changed model detection to be performed BEFORE baudrate.
                This allows to display a communication error with JN5168 module which might be due to wrong GPIO (RESET & FLASH) settings
                instead of getting "Error setting baudrate".

How to compile
==============
root@FelicityDbox: make clean all

    rm -f build/uart.o build/JN51xx_BootLoader.o build/Firmware.o build/main.o build/JennicModuleProgrammer
    cc -I. -Wall -O2 -I../source/ -DDBG_ENABLE -DVERSION='"56124"' -c source/uart.c -o build/uart.o
    cc -I. -Wall -O2 -I../source/ -DDBG_ENABLE -DVERSION='"56124"' -c source/JN51xx_BootLoader.c -o build/JN51xx_BootLoader.o
    cc -I. -Wall -O2 -I../source/ -DDBG_ENABLE -DVERSION='"56124"' -c source/Firmware.c -o build/Firmware.o
    cc -I. -Wall -O2 -I../source/ -DDBG_ENABLE -DVERSION='"56124"' -c source/main.c -o build/main.o
    cc build/uart.o build/JN51xx_BootLoader.o build/Firmware.o build/main.o   -o build/JennicModuleProgrammer

How to flash
============
root@FelicityDbox: ./flashZigate.sh /dev/ttyS1 PI WiringPi ../fw_zigate/zigatev1-AB01-0000-opdm-dev.bin

    Checking Zigate type PI access on port /dev/ttyS1
    - /dev/ttyS1 port found
    - Port seems free
    Configuring PI Zigate for 'flash' mode (lib=WiringPi)
    Flashing Zigate
    - Port tty: /dev/ttyS1
    - Type    : PI
    - Lib GPIO: WiringPi
    - File    : ../fw_zigate/zigatev1-AB01-0000-opdm-dev.bin

    JennicModuleProgrammer Version: 0.7-r56124-Abeille
    - Detected Chip: JN5168
    - MAC Address  : 00:15:8D:00:01:ED:33:65
    Setting baudrate: 115200
    Opened firmware file size 199244
    Module uses Bootloader v2 Header
    Erasing:   100%
    Writing Program to Flash
    Writing:   100%
    Verifying Program in Flash
    Verifying: 100%
    = Success
    Restoring PI Zigate GPIOs config for prod mode
    = Tout s'est bien passé. Vous pouvez fermer ce log.
