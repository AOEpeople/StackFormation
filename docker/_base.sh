#!/usr/bin/env bash

function echoerr {
    echo "============================================" 1>&2;
    echo "ERROR: $@" 1>&2;
    echo "============================================" 1>&2;
}
function error_exit { echoerr "$1"; exit 1; }

if [ -z "$1" ] ; then error_exit "Version number not set!"; fi
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

clean() {
    rm -rf $DIR/slim/php-fpm.conf
    rm -rf $DIR/golang/php-fpm.conf
}

cp php-fpm.conf ./slim
cp php-fpm.conf ./golang
