# Redisent

Redisent is a simple, no-nonsense interface to the [Redis](http://redis.io) data structure store for modest developers.
It is designed to flexible and tolerant of changes to the Redis protocol.

## Introduction

If you're at all familiar with the Redis protocol and PHP objects, you've already mastered Redisent.
All Redisent does is map the Redis protocol to a PHP object, abstract away the nitty-gritty, and make the return values PHP compatible.
Any method call on a `Redis` instance is aliased to a Redis command with the same name, so a call to `$redis->set('foo', 'bar')` is translated to `SET foo bar`.
This is possible because of the [Unified Protocol](http://redis.io/topics/protocol), which makes Redisent very resilient against changes to the Redis command set.

## Quick Start

Redisent has no dependencies aside from requiring PHP versions 5.3 and later.
To add it to your project, simply drop the redisent.php file into your project structure, instantiate a Redis instance, and start issuing commands.

```php
require 'redisent.php';

$redis = new redisent\Redis('redis://localhost');
$redis->set('awesome', 'absolutely');
echo "Is Redisent awesome? ", $redis->get('awesome'), "\n";
```

Any errors originating from Redis will be wrapped in a `resident\RedisException` and thrown.

## Pipelining

Redisent supports a fluent interface for [pipelining].
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

## Roadmap

Redis has grown to be very feature rich, and Redisent is lagging behind.

* Support for publish/subscribe
* Support for transactions

## About

Copyright &copy; 2009-2012 [Justin Poliey](http://justinpoliey.com)

## License

Licensed under the [ISC License](http://www.opensource.org/licenses/ISC).

Copyright (c) 2009-2012 Justin Poliey <justin@getglue.com>

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.