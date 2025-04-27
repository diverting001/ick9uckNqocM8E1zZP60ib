#!/bin/sh
Dir=$(cd `dirname $0`; pwd)
Dir1=$(pwd)
cd $Dir
php localcall.php $1$2
cd $Dir1