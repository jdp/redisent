<?php
namespace Redisent\tests;

require_once __DIR__.'/../simpletest/autorun.php';
require_once __DIR__.'/../src/Redisent/Redis.php';
require_once __DIR__.'/../src/Redisent/Exception.php';

class PipelineTest extends \UnitTestCase {

  private $dsn = 'redis://localhost/';
  
  function setUp() {
    $this->r = new \Redisent\Redis($this->dsn);
    $this->assertIsA($this->r, 'Redisent\Redis');
  }

  function testFluentIncr() {
    // Test the fluent interface
    $responses = $this->r->pipeline()
      ->incr('X')
      ->incr('X')
      ->incr('X')
      ->incr('X')
      ->uncork();
    $this->assertEqual(count($responses), 4);
    $this->assertEqual($this->r->del('X'), 1);
  }

  function testProceduralIncr() {
    // Test a less fluent interface
    $pipeline = $this->r->pipeline();
    for ($i = 0; $i < 10; $i++) {
      $pipeline->incr('X');
    }
    $responses = $pipeline->uncork();

    $this->assertEqual(count($responses), 10);
    $this->assertEqual($this->r->del('X'), 1);
  }

}
