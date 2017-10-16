#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )"/fp_exec_bg"" && pwd )"
cd $DIR
eval $DIR"/set_com_port.sh /dev/ttyACM0 9600"
eval $DIR"/fp_exec -d TremolFP -f" $1

