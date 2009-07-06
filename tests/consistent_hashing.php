<?php
error_reporting(E_ALL);

include '../redisent_cluster.php';

$start_time = microtime(true);

/* Get all the keys to use */
$keys = array();
$lines = explode("\n", file_get_contents("keys.test"));
foreach ($lines as $line) {
	$pair = explode(':', trim($line));
	if (count($pair) >= 2) {
		$keys[$pair[0]] = $pair[1];
	}
}
echo sprintf("Got %d keys\n", count($keys));

/* Use a cluster of 3 servers, make sure they're clean */
echo "Using a cluster of 3 servers\n";
$cluster = new RedisentCluster(array(
	array('host' => '127.0.0.1', 'port' => 6379),
	array('host' => '127.0.0.1', 'port' => 6380),
	array('host' => '127.0.0.1', 'port' => 6381)
));

echo sprintf("Setting %d keys\n", count($keys));
foreach ($keys as $key => $value) {
	$cluster->set($key, $value);
}

/* Now use a 4th server, and get the key sharding */
echo "Adding a new server to the cluster\n";
$cluster = new RedisentCluster(array(
	array('host' => '127.0.0.1', 'port' => 6379),
	array('host' => '127.0.0.1', 'port' => 6380),
	array('host' => '127.0.0.1', 'port' => 6381),
	array('host' => '127.0.0.1', 'port' => 6382)
));

/* Try to reset all the keys, and keep track of shards */
$hits = 0;
foreach ($keys as $key => $value) {
	if ($cluster->get($key)) {
		$hits++;
	}
	else {
		$cluster->set($key, $value);
	}
}

/* End tests and print results */
$end_time = microtime(true);
echo sprintf("%d key hits (%f%% efficiency)\n", $hits, ($hits / count($keys)) * 100);
echo sprintf("Tests completed in %f seconds\n", $end_time-$start_time);
