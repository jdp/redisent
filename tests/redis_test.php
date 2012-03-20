<?php
namespace redisent\tests\units;

require_once __DIR__.'/../mageekguy.atoum.phar';
require_once __DIR__.'/../redisent.php';

use mageekguy\atoum;

class Redis extends atoum\test {

  private $dsn = 'redis://localhost/';
  private $redis;
  
  function test__construct() {
    $this->assert()
      ->if($this->redis = new \redisent\Redis($this->dsn))
      ->then
        ->object($this->redis)->isInstanceOf('redisent\Redis');
  }

  function testKeys() {
    $this->redis = new \redisent\Redis($this->dsn);

    $this->assert->boolean($this->redis->set('foo', 'bar'))
      ->isTrue('Failed to set key `foo`');
    $this->assert->string($this->redis->get('foo'))
      ->isEqualTo('bar', 'Key `foo` should have value `bar`');

    $this->assert->integer($this->redis->exists('foo'))
      ->isEqualTo(1, 'Key `foo` should exist after being set');
    $this->assert->integer($this->redis->exists('bar'))
      ->isEqualTo(0, 'Key `bar` should not exist, was never set');

    $this->assert->integer($this->redis->del('foo'))
      ->isEqualTo(1, 'Failed to delete key `foo`');
    $foo = $this->redis->get('foo');
    $this->assert->variable($foo)
      ->isNull("Key `foo` should have been deleted: '{$foo}'");
  }

  function testPipeline() {
    $this->redis = new \redisent\Redis($this->dsn);

    $responses = $this->redis->pipeline()
      ->incr('X')
      ->incr('X')
      ->incr('X')
      ->incr('X')
      ->uncork();

    $this->assert->array($responses)
      ->isNotEmpty("INCR should have run multiple times")
      ->containsValues(array(1, 2, 3, 4), "INCR should have run 4 times, resulting in values 1 through 4");

    $this->assert->integer($this->redis->del('X'))
      ->isEqualTo(1, "Failed to delete key used for INCR pipelining");
  }

}
