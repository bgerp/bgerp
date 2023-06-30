#!/bin/sh
set -e
URL="$1"
SAVEFILE="$2"
DURATION="$3"
WIDTH="$4"
HEIGHT="$5"
FPS="$6"


ffmpeg -hide_banner -y -loglevel error -rtsp_transport tcp -use_wallclock_as_timestamps 1 -i "$URL" -vcodec copy -acodec aac -f segment -reset_timestamps 1 -segment_time 1600 -segment_format mp4 -segment_atclocktime 1 -strftime 1 -t $DURATION $SAVEFILE
