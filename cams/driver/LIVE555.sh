#!/bin/sh
set -e
URL="$1"
SAVEFILE="$2"
DURATION="$3"
WIDTH="$4"
HEIGHT="$5"
FPS="$6"

DISPLAY= openRTSP -4 -d $DURATION -w $WIDTH -h $HEIGHT -f $FPS -b 150000 "$URL" > $SAVEFILE



