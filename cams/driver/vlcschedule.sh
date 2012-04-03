#!/bin/sh
set -e
URL="$1"
SAVEFILE="$2"
DURATION="$3"
CODEC="$4"
# mp4v, h264
DISPLAY= cvlc "$URL" \ # --run-time $DURATION \
	--sout="#transcode{vcodec=$CODEC,vb=800,acodec=mpga,ab=128,fps=4,deinterlace}:std{access=file,mux=mp4,dst='$SAVEFILE'}" \
	< /dev/null > /dev/null 2>&1 &
pid=$!
sleep $DURATION
kill $pid
wait $pid
