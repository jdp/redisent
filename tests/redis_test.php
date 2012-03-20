<?php
namespace redisent\tests\units;

require_once __DIR__.'/../mageekguy.atoum.phar';
require_once __DIR__.'/../redisent.php';

use mageekguy\atoum;

class Redis extends atoum\test {

  private $dsn = 'redis://jdp:c5bfca3c8324fef99e0a920d37ea3232@catfish.redistogo.com:9512/';
  private $redis;
  
  function test__construct() {
    // Test default setup
    $this->assert()
      ->if($this->redis = new \redisent\Redis($this->dsn))
      ->then
        ->object($this->redis)->isInstanceOf('redisent\Redis');
  }

  function testKeys() {
    $this->redis = new \redisent\Redis($this->dsn);

    $this->redis->set('foo', 'bar');
    $this->assert->string($this->redis->get('foo'))->isEqualTo('bar');

    $this->assert->integer($this->redis->exists('foo'))->isEqualTo(1);
    $this->assert->integer($this->redis->exists('bar'))->isEqualTo(0);

    $this->redis->del('foo');
    $this->assert->variable($this->redis->get('foo'))->isNull();
  }

}
