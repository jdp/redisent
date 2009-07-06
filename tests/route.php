<?php
error_reporting(E_ALL);

include '../redisent_cluster.php';

$cluster = new RedisentCluster(array(
	'alpha' => array('host' => '127.0.0.1', 'port' => 6379),
	'beta'  => array('host' => '127.0.0.1', 'port' => 6380)
));

echo "Set 'pokemon' to 'squirtle' on alpha\n";
$cluster->to('alpha')->set('pokemon', 'squirtle');
echo "Set 'pokemon' to 'bulbasaur' on beta\n";
$cluster->to('beta')->set('pokemon', 'bulbasaur');

if ($cluster->to('alpha')->get('pokemon') == 'squirtle') {
	echo "[PASS] Got 'squirtle' from 'pokemon' on alpha, great!\n";
}
else {
	echo "[FAIL] Seems we have a Poke-mixup on alpha\n";
}

if ($cluster->to('beta')->get('pokemon') == 'bulbasaur') {
	echo "[PASS] Got 'bulbasaur' from 'pokemon' on beta, great!\n";
}
else {
	echo "[FAIL] Seems we have a Poke-mixup on beta\n";
}
