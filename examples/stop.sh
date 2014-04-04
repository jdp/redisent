#!/bin/sh
for file in `find ./ -name "redis-*.pid"`
do
    kill `cat "$file"`
done
rm -f dump.rdb

