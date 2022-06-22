#!/bin/bash

webroot=$1

if [ -e "$webroot" ]
then
    echo "Changed directory to $webroot";
	cd $webroot;
fi

# BvdB import
# /usr/local/bin/wp bvdb-image-optimize start --allow-root
echo "Feature is currently disabled";