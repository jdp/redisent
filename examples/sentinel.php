<?php
/**
 * Run start.sh to fire up 1 master, 2 slaves and a Sentinel
 * Execute the script and take down the master
 * You can take down the master by calling "kill `cat redis-master.pid`"
 * After 5 seconds Sentinel will elect a new master
 * Be aware that Sentinel will rewrite your config files when a new master is elected
 */

require(dirname(__DIR__).'/Client.php');
require(dirname(__DIR__).'/Sentinel.php');
require(dirname(__DIR__).'/Rwsplit.php');
require(dirname(__DIR__).'/Cluster.php');

$sentinel = new Credis_Sentinel(new Credis_Client('127.0.0.1',26379),true);
$masterAddress = $sentinel->getMasterAddressByName('mymaster');
$cluster = $sentinel->getCluster('mymaster');

echo 'Writing to master: '.$masterAddress[0].' on port '.$masterAddress[1].PHP_EOL;
$cluster->set('key','value');
echo $cluster->get('key').PHP_EOL;