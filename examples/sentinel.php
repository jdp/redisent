<?php
date_default_timezone_set('Europe/Brussels');

require(dirname(__DIR__).'/Client.php');
require(dirname(__DIR__).'/Sentinel.php');
require(dirname(__DIR__).'/Rwsplit.php');
require(dirname(__DIR__).'/Cluster.php');

$sentinel = new Credis_Sentinel(new Credis_Client('127.0.0.1',26379),true);
$cluster = $sentinel->getCluster('mymaster');

$key = 'key'.rand(0,100);
$value = 'value'.rand(0,100);

$cluster->set($key,$value);
echo $cluster->get($key).PHP_EOL;