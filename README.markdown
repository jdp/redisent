# Credis

Credis is a lightweight interface to the [Redis](http://redis.io/) key-value store which wraps the [phpredis](https://github.com/nicolasff/phpredis)
library when available for better performance. This project was forked from one of the many redisent forks.

## Getting Started

Credis_Client uses methods named the same as Redis commands, and translates return values to the appropriate
PHP equivalents.

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

Credis_Client also supports transparent command renaming. Write code using the original command names and the
client will send the aliased commands to the server transparently. Specify the renamed commands using a prefix
for md5, a callable function, individual aliases, or an array map of aliases. See "Redis Security":http://redis.io/topics/security for more info.

## Clustering your servers

Credis also includes a way for developers to fully utilize the scalability of Redis with multiple servers and [consistent hashing](http://en.wikipedia.org/wiki/Consistent_hashing).
Using the Credis_Cluster class, you can use Credis the same way, except that keys will be hashed across multiple servers.
Here is how to set up a cluster:

```php
require 'Credis/Client.php';
require 'Credis/Cluster.php';

$cluster = new Credis_Cluster(array(
    'alpha' => array('host' => '127.0.0.1', 'port' => 6379),
    'beta'  => array('host' => '127.0.0.1', 'port' => 6380),
));
$cluster->set('key','value');
$cluster->to('alpha')->info();
```

## About

&copy; 2011 [Colin Mollenhour](http://colin.mollenhour.com)
&copy; 2009 [Justin Poliey](http://justinpoliey.com)
