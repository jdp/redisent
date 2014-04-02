#!/bin/sh
for file in `find ./ -name "redis-*.pid"`
do
    echo "$file"
    kill `cat "$file"`
done

