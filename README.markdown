# Redisent

Redisent is a simple, no-nonsense interface to the [Redis](http://redis.io) key-value store for modest developers.
Due to the way it is implemented, it is flexible and tolerant of changes to the Redis protocol.

## Introduction

If you're at all familiar with the Redis protocol and PHP objects, you've already mastered Redisent.
All Redisent does is map the Redis protocol to a PHP object, abstract away the nitty-gritty, and make the return values PHP compatible.

``` php
require 'redisent.php';

$redis = new redisent\Redis('redis://localhost');
$redis->set('awesome', 'absolutely');
echo sprintf('Is Redisent awesome? %s.\n', $redis->get('awesome'));
```

Redisent takes advantage of the [Unified Protocol](http://redis.io/topics/protocol) to be resilient to changes to the Redis command set.

``` php
require 'redisent.php';

$redis = new redisent\Redis();
$redis->rpush('particles', 'proton');
$redis->rpush('particles', 'electron');
$redis->rpush('particles', 'neutron');
$particles = $redis->lrange('particles', 0, -1);
$particle_count = $redis->llen('particles');

echo "<p>The {$particle_count} particles that make up atoms are:</p>";
echo "<ul>";
foreach ($particles as $particle) {
  echo "<li>{$particle}</li>";
}
echo "</ul>";
```

Redis error replies will be wrapped in a `RedisException` and thrown.

## Implementation

Behind the scenes, method calls to a `Redis` instance go through the `[__call](http://us3.php.net/manual/en/language.oop5.overloading.php#object.call)` magic method. The Unified Protocol command is then generated and sent to the Redis server, and the response is returned.

## About

&copy; 2009-2012 [Justin Poliey](http://justinpoliey.com)

## [License](http://www.opensource.org/licenses/ISC)

Copyright (c) 2009-2012 Justin Poliey <justin@getglue.com>

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.