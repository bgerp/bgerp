#!/bin/sh
# Changes settings of serial/usb port and baud rate for serial port
# Example: set_com_port.sh /dev/ttyS0 9600

stty -F $1 -inlcr $2
stty -F $1 -opost -onlcr $2
stty -F $1 -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke $2

