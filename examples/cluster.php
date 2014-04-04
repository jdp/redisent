<?php
/**
 * Run start-cluster.sh to fire up 2 independent masters
 */

require(dirname(__DIR__).'/Client.php');
require(dirname(__DIR__).'/Sentinel.php');
require(dirname(__DIR__).'/Cluster.php');

$cluster = new Credis_Cluster(array(
    array('host' => '127.0.0.1', 'port' => 6379, 'alias'=>'alpha'),
    array('host' => '127.0.0.1', 'port' => 6380, 'alias'=>'beta')
));

echo "==Using hash to distribute==".PHP_EOL;
$cluster->set('key','value').PHP_EOL;
echo "Cluster: ".$cluster->get('key').PHP_EOL;
echo "Alpha: ".$cluster->client('alpha')->get('key').PHP_EOL;
echo "Beta: ".$cluster->client('beta')->get('key').PHP_EOL;

echo PHP_EOL."==Writing to all instances==".PHP_EOL;
$cluster->all('set','key2','value2');
echo "Alpha: ".$cluster->client('alpha')->get('key2').PHP_EOL;
echo "Beta: ".$cluster->client('beta')->get('key2').PHP_EOL;
