#!/bin/sh
for file in `find -name "redis-*.pid"`
do
	local pid=`cat "$file"`
	echo "Killing PID $pid in $file"
	kill -9 "$pid"
	rm "$file"
done

