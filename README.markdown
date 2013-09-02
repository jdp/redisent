# Redisent

Redisent is a simple, no-nonsense interface to the [Redis](http://redis.io) data structure store for modest developers.
It is designed to flexible and tolerant of changes to the Redis protocol.

## Introduction

If you're at all familiar with the Redis protocol and PHP objects, you've already mastered Redisent.
Redisent translates method calls to their [Redis protocol](http://redis.io/topics/protocol) equivalent, abstracting away the nitty-gritty, and then makes the return values PHP compatible.

## Features

### Shared Redis API

The Redisent method names map directly to their Redis command counterparts.
The full list is available in the [command reference](http://redis.io/commands).

#### Setting Keys

```php
$redis->set('foo', 'bar')
// SET foo bar
```

#### Working with lists

```php
$redis->lpush('particles', 'electron')
// LPUSH particles electron
$redis->lpush('particles', 'proton')
// LPUSH particles proton
$redis->lpush('particles', 'neutron')
// LPUSH particles neutron
$redis->llen('particles')
// LLEN particles
```

### Pipelining

Redisent provides a fluent interface for [pipelining](http://redis.io/topics/pipelining) commands to Redis.

```php
$redis->pipeline()
  ->set('X', 2)
  ->incr('X')
  ->incr('X')
  ->uncork(); // #=> array containing the responses of each command
```

## Quick Start

Redisent has no dependencies aside from requiring PHP versions 5.3 and later.
To add it to your project, simply drop the Redis.php file into your project structure, instantiate a Redis instance, and start issuing commands.

```php
require_once 'redisent/Redis.php';
$redis = new redisent\Redis('redis://localhost');
$redis->set('awesome', 'absolutely');
echo "Is Redisent awesome? ", $redis->get('awesome'), "\n";
```

Any errors originating from Redis will be wrapped in a `resident\RedisException` and thrown.

## Pipelining

Redisent supports a fluent interface for [pipelining](http://redis.io/topics/pipelining).
A pipeline is started by calling the `pipeline` method on a `Redis` instance, using Redisent as usual, and then calling the `uncork` method.
The `uncork` method returns an array of the responses of the pipelined commands.

### Example

```php
$redis = new redisent\Redis();
$responses = $redis->pipeline()
    ->incr('X')
    ->incr('X')
    ->incr('X')
    ->incr('X')
    ->uncork();
print_r($responses);
```

If the key X didn't exist, the first INCR would create it and return 1, and successive calls would increment it by 1.
The return value of the call to `uncork()` would be `array(1,2,3,4)`, the responses of each INCR command.

## Contributing

Pull requests please! Feature/topic branches are especially appreciated.
Unit tests are written with [SimpleTest](http://simpletest.org/), please include tests in your pull request.
To run tests, run `sh setup.sh` script to get set up and then `php tests/all_tests.php` to run the suite.

## Roadmap

Redis has grown to be very feature rich, and Redisent is lagging behind.

* Publish/subscribe
* Transactions

## About

Copyright &copy; 2009-2012 [Justin Poliey](http://justinpoliey.com)

## License

Licensed under the [ISC License](http://www.opensource.org/licenses/ISC).

Copyright (c) 2009-2012 Justin Poliey <justin@getglue.com>

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/jdp/redisent/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

