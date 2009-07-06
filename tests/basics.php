<?php
require '../redisent.php';

$redis = new Redisent('localhost');

$start_time = microtime(true);

echo "** GET/SET/DEL\n";
$redis->set('a', 'foo');
$redis->set('b', 'bar');
$redis->set('c', 'baz');
echo $redis->get('a') . "\n";
echo $redis->get('b') . "\n";
echo $redis->get('c') . "\n";
$redis->del('c');
echo (($redis->get('c') == null) ? 'null' : 'not null') . "\n";

echo "** MGET\n";
print_r($redis->mget('a', 'b'));

echo "** KEYS\n";
print_r($redis->keys('*'));

echo "** RANDOMKEY\n";
echo $redis->randomkey() . "\n";

echo "** LISTS\n";
$redis->rpush('particles', 'proton');
$redis->rpush('particles', 'electron');
$redis->rpush('particles', 'neutron');
print_r($redis->lrange('particles', 0, 2));
echo $redis->llen('particles') . "\n";
//$redis->lrem('particles', 0, 'proton');
$redis->rpop('particles');
$redis->lpop('particles');
$redis->lrem('particles', 0, 'electron');
print_r($redis->lrange('particles', 0, -1));

$end_time = microtime(true);

echo sprintf("Tests completed in %f seconds\n", $end_time-$start_time);
