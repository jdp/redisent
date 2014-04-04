<?php
/**
 * Run start-single.sh to fire up a single Redis instance
 */

require(dirname(__DIR__).'/Client.php');

$client = new Credis_Client();
$client->set('key','value');
echo $client->get('key').PHP_EOL;