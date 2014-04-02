#!/bin/sh
redis-server redis-master.conf
redis-server redis-slave.conf
redis-server redis-slave2.conf
redis-sentinel redis-sentinel.conf
