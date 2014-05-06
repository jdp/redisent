# Credis

Credis is a lightweight interface to the [Redis](http://redis.io/) key-value store which wraps the [phpredis](https://github.com/nicolasff/phpredis)
library when available for better performance. This project was forked from one of the many redisent forks.

## Getting Started

Credis uses methods named the same as Redis commands, and translates return values to the appropriate PHP equivalents.

```php
require 'Credis/Client.php';
$redis = new Credis_Client('localhost');
$redis->set('awesome', 'absolutely');
echo sprintf('Is Credis awesome? %s.\n', $redis->get('awesome'));

// When arrays are given as arguments they are flattened automatically
$redis->rpush('particles', array('proton','electron','neutron'));
$particles = $redis->lrange('particles', 0, -1);
```
Redis error responses will be wrapped in a CredisException class and thrown.

## Clustering your servers

Credis also includes a way for developers to fully utilize the scalability of Redis with multiple servers and [consistent hashing](http://en.wikipedia.org/wiki/Consistent_hashing).
Using the Credis_Cluster class, you can use Credis the same way, except that keys will be hashed across multiple servers.
Here is how to set up a cluster:

```php
require 'Credis/Client.php';
require 'Credis/Cluster.php';

$cluster = new Credis_Cluster(array(
    array('host' => '127.0.0.1', 'port' => 6379, 'alias'=>'alpha'),
    array('host' => '127.0.0.1', 'port' => 6380, 'alias'=>'beta')
));
$cluster->set('key','value');
$cluster->client('alpha')->info();
```

## Master/slave replication

The Credis_Cluster class can also be used for [master/slave replication](http://redis.io/topics/replication).
By including the *Credis_Rwsplit* class, Credis_Cluster will automatically perform *read/write splitting* and send the write requests exclusively to the master server.
Read requests will be handled by all servers unless you set the *readOnMaster* flag to false.


```php
require 'Credis/Client.php';
require 'Credis/Cluster.php';
require 'Credis/Rwsplit.php';

$cluster = new Credis_Cluster(array(
    array('host' => '127.0.0.1', 'port' => 6379, 'alias'=>'master', 'master'=>true),
    array('host' => '127.0.0.1', 'port' => 6380, 'alias'=>'slave')
));
$cluster->set('key','value');
echo $cluster->client('slave')->get('key').PHP_EOL;

$cluster->client('master')->set('key2','value');
echo $cluster->client('slave')->get('key').PHP_EOL;
```

Setting  up the replication is simple and only requires adding the following line to the config of the slave server:

```
slaveof 127.0.0.1 6379
```

## About

&copy; 2011 [Colin Mollenhour](http://colin.mollenhour.com)
&copy; 2009 [Justin Poliey](http://justinpoliey.com)
