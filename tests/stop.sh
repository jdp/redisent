#!/bin/sh
for file in `find ./ -name "redis-*.pid"`
do
	pid=`cat "$file"`
	echo "Killing PID $pid in $file"
	kill "$pid"
done
rm -f dump.rdb
