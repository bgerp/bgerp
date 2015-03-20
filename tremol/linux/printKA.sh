#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )"/fp_exec_bg"" && pwd )"
cd $DIR
eval $DIR"/fp_exec -d TremolFP -f" $1

