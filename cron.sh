#!/bin/bash

webroot=$1

if [ -e "$webroot" ]
then
    echo "Changed directory to $webroot";
	cd $webroot;
fi

# BvdB import
/usr/local/bin/wp bvdb-import start > logs/import-$(date '+%Y-%m-%d_%H:%M:00').log --allow-root