<?php

require_once dirname(__FILE__).'/../Client.php';

class CredisTest extends PHPUnit_Framework_TestCase
{

  /** @var Credis_Client */
  protected $credis;

  protected $config;

  protected $useStandalone = FALSE;

  protected function setUp()
  {
    if($this->config === NULL) {
      $configFile = dirname(__FILE__).'/test_config.json';
      $config = file_get_contents($configFile);
      if( ! $config) {
        throw new ErrorException('Could not load '.$configFile);
      }
      $this->config = json_decode($config);
    }
    $this->credis = new Credis_Client($this->config->host, $this->config->port, $this->config->timeout);
    if($this->useStandalone) {
      $this->credis->forceStandalone();
    }
  }

  protected function tearDown()
  {
    $this->credis->flushDb();
    $this->credis->close();
    $this->credis = NULL;
  }

  public function testStrings()
  {
    $this->credis->set('foo','FOO');
    $this->assertEquals('FOO', $this->credis->get('foo'));
    $this->credis->set('bar','BAR');

    // Array
    $mget = $this->credis->mget(array('foo','bar'));
    $this->assertTrue(in_array('FOO', $mget));
    $this->assertTrue(in_array('BAR', $mget));

    // Non-array
    $mget = $this->credis->mget('foo','bar');
    $this->assertTrue(in_array('FOO', $mget));
    $this->assertTrue(in_array('BAR', $mget));

    $this->assertEquals(2, $this->credis->del('foo','bar'));
    $this->assertNull($this->credis->get('foo'));
    $this->assertNull($this->credis->get('bar'));

    $longString = str_repeat(md5('asd')."\r\n", 500);
    $this->assertEquals('OK', $this->credis->set('long', $longString));
    $this->assertEquals($longString, $this->credis->get('long'));
  }

  public function testVariadic()
  {
    $this->assertEquals(2, $this->credis->sAdd('myset', 'Hello', 'World'));
    $this->assertEquals(1, $this->credis->sAdd('myset', array('Hello','Cruel','World')));
    $members = $this->credis->sMembers('myset');
    $this->assertEquals(3, count($members));
    $this->assertTrue(in_array('Hello', $members));
  }

  public function testFalsey()
  {
    $this->assertEquals(Credis_Client::TYPE_NONE, $this->credis->type('foo'));
  }

  public function testPipeline()
  {
    $reply = $this->credis->pipeline()
        ->set('a', 123)
        ->get('a')
        ->sAdd('b', 123)
        ->sMembers('b')
        ->exec();
    $this->assertEquals(array(
      'OK', 123, 1, array(123)
    ), $reply);
  }

  public function testTransaction()
  {
    $reply = $this->credis->multi()
        ->incr('foo')
        ->incr('bar')
        ->exec();
    $this->assertEquals(array(1,1), $reply);

    $reply = $this->credis->pipeline()->multi()
        ->incr('foo')
        ->incr('bar')
        ->exec();
    $this->assertEquals(array(2,2), $reply);

    $reply = $this->credis->multi()->pipeline()
        ->incr('foo')
        ->incr('bar')
        ->exec();
    $this->assertEquals(array(3,3), $reply);

    $reply = $this->credis->multi()
        ->set('a', 3)
        ->lpop('a')
        ->exec();
    $this->assertEquals(2, count($reply));
    $this->assertEquals('OK', $reply[0]);
    $this->assertFalse($reply[1]);
  }

}
