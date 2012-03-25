<?php
error_reporting(E_ALL);

$replicas = 128;
if($argc == 2) $replicas = $argv[1];

require '../Client.php';
require '../Cluster.php';

$start_time = microtime(true);

/* Use a cluster of 3 servers, make sure they're clean */
$cluster = new Credis_Cluster(array(
	array('host' => '127.0.0.1', 'port' => 6379),
	array('host' => '127.0.0.1', 'port' => 6380),
	array('host' => '127.0.0.1', 'port' => 6381)
), $replicas);
printf("Initialized 3 servers with $replicas replicas in %f seconds\n", microtime(true)-$start_time);

/* Get all the keys to use */
$keys = array();
$lines = explode("\n", file_get_contents("keys.test"));
foreach ($lines as $line) {
	$pair = explode(':', trim($line));
	if (count($pair) >= 2) {
		$keys[$pair[0]] = $pair[1];
	}
}
printf("Setting %d keys\n", count($keys));
$cluster->all('flushDb');
foreach ($keys as $key => $value) {
	$cluster->set($key, $value);
}

/* Now use a 4th server, and get the key sharding */
echo "Adding a new server to the cluster\n";
$cluster = new Credis_Cluster(array(
	array('host' => '127.0.0.1', 'port' => 6379),
	array('host' => '127.0.0.1', 'port' => 6380),
	array('host' => '127.0.0.1', 'port' => 6381),
	array('host' => '127.0.0.1', 'port' => 6382)
), $replicas);

/* Try to reset all the keys, and keep track of shards */
$hits = 0;
foreach ($keys as $key => $value) {
	if ($cluster->get($key)) {
		$hits++;
	}
}

foreach($cluster->all('info') as $info) {
  if(isset($info['db0'])) {
    echo "{$info['db0']}\n";
  }
}

/* End tests and print results */
printf("%d key hits (%f%% efficiency)\n", $hits, ($hits / count($keys)) * 100);
printf("Tests completed in %f seconds\n", microtime(true)-$start_time);
