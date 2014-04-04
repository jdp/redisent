<?php
/**
 * Run start-masterslave.sh to fire up a master and a slave
 */

require(dirname(__DIR__).'/Client.php');
require(dirname(__DIR__).'/Sentinel.php');
require(dirname(__DIR__).'/Rwsplit.php');
require(dirname(__DIR__).'/Cluster.php');

$cluster = new Credis_Cluster(array(
    array('host' => '127.0.0.1', 'port' => 6379, 'alias'=>'alpha', 'master'=>true),
    array('host' => '127.0.0.1', 'port' => 6381, 'alias'=>'beta')
));
echo "==Set on cluster==".PHP_EOL;
$cluster->set('key','value').PHP_EOL;
echo "Cluster: ".$cluster->get('key').PHP_EOL;
echo "Alpha: ".$cluster->client('alpha')->get('key').PHP_EOL;
echo "Beta: ".$cluster->client('beta')->get('key').PHP_EOL;

echo PHP_EOL."==Set on alpha, get from beta==".PHP_EOL;
echo "Beta: ".$cluster->client('beta')->get('key2').PHP_EOL;