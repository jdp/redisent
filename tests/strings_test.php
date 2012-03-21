<?php
namespace redisent\tests;

require_once __DIR__.'/../simpletest/autorun.php';
require_once __DIR__.'/../src/redisent/Redis.php';

class StringsTest extends \UnitTestCase {

  private $dsn = 'redis://localhost/';
  
  function setUp() {
    $this->r = new \redisent\Redis($this->dsn);
    $this->assertIsA($this->r, 'redisent\Redis');
  }

  function testSet() {
    $this->assertTrue($this->r->set('foo', 'bar'));
    $this->assertEqual($this->r->get('foo'), 'bar');
  }

  function testExists() {
    $this->assertEqual($this->r->exists('foo'), 1);
    $this->assertEqual($this->r->exists('bar'), 0);
  }

  function testDel() {
    $this->assertEqual($this->r->del('foo'), 1);
    $this->assertNull($this->r->get('foo'));
  }

}
