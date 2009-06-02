# Redisent

Redisent is a simple, no-nonsense interface to the [Redis](http://code.google.com/p/redis/) key-value store for modest developers.

## Getting to work

If you're at all familiar with the Redis protocol and PHP objects, you've already mastered Redisent.
All Redisent does is map the Redis protocol to a PHP object, abstract away the nitty-gritty, and make the return values PHP compatible.

    require 'redisent.php';
    $redis = new Redisent('localhost');
    $redis->set('awesome', 'absolutely');
    echo sprintf('Is Redisent awesome? %s.\n', $redis->get('awesome'));

You use the exact same command names, and the exact same argument order. **How wonderful.** How about a more complex example?

    require 'redisent.php';
    $redis = new Redisent('localhost');
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


## About

&copy; 2009 [Justin Poliey](http://justinpoliey.com)
