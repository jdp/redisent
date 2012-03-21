#!/bin/sh
simpletest=simpletest.tar.gz
wget -O $simpletest -- 'http://downloads.sourceforge.net/project/simpletest/simpletest/simpletest_1.1/simpletest_1.1.0.tar.gz'
tar zxf $simpletest
rm $simpletest
