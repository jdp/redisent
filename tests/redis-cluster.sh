#!/bin/sh
for port in 6379 6380 6381 6382
do
	redis-cli -h localhost -p $port $*
done
