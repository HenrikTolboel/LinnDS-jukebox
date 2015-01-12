#!/bin/sh
# Copyright (c) 2010-2015 Henrik Tolbøl. All rights reserved.


Php="/usr/bin/php"
LinnDir="/volume1/web/musik"
Linnd="$LinnDir/LinnDS-jukebox-daemon.php"

if [ "$1" = "start" ]; then
   if [ -f "$Linnd" ]; then
      cd $LinnDir
      nohup $Php $Linnd &
   else
      echo "$Linnd doesn't exist!"
   fi
fi

if [ "$1" = "stop" ]; then
   pkill -9 -f "$Php $Linnd"
   exit 0
fi

if [ "$1" = "restart" ]; then
   $0 stop
   sleep 1
   $0 start
fi
