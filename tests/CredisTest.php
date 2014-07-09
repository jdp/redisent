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
            $configFile = dirname(__FILE__).'/redis_config.json';
            if( ! file_exists($configFile) || ! ($config = file_get_contents($configFile))) {
                $this->markTestSkipped('Could not load '.$configFile);
                return;
            }
            $this->config = json_decode($config);
            if(count($this->config) < 6) {
              $this->markTestSkipped('Config file '.$configFile.' should contain at least 6 entries');
              return;
            }
        }
        $this->credis = new Credis_Client($this->config[0]->host, $this->config[0]->port, $this->config[0]->timeout);
        if($this->useStandalone) {
            $this->credis->forceStandalone();
        } else if ( ! extension_loaded('redis')) {
            $this->fail('The Redis extension is not loaded.');
        }
        $this->credis->flushDb();
    }
    protected function tearDown()
    {
        if($this->credis) {
            $this->credis->close();
            $this->credis = NULL;
        }
    }
    public function testFlush()
    {
        $this->credis->set('foo','FOO');
        $this->assertTrue($this->credis->flushDb());
        $this->assertFalse($this->credis->get('foo'));
    }

    public function testReadTimeout()
    {
        $this->credis->setReadTimeout(0.0001);
        try {
            $this->credis->save();
            $this->fail('Expected exception (read should timeout since disk sync should take longer than 0.0001 seconds).');
        } catch(CredisException $e) {
        }
        $this->credis->setReadTimeout(10);
    }

    public function testPHPRedisReadTimeout()
    {
        try {
            $this->credis->setReadTimeout(-1);
        } catch(CredisException $e) {
            $this->fail('setReadTimeout should accept -1 as timeout value');
        }
        try {
            $this->credis->setReadTimeout(-2);
            $this->fail('setReadTimeout should not accept values less than -1');
        } catch(CredisException $e) {
        }
    }

    public function testScalars()
    {
        // Basic get/set
        $this->credis->set('foo','FOO');
        $this->assertEquals('FOO', $this->credis->get('foo'));
        $this->assertFalse($this->credis->get('nil'));

        // Empty string
        $this->credis->set('empty','');
        $this->assertEquals('', $this->credis->get('empty'));

        // UTF-8 characters
        $utf8str = str_repeat("quarter: ¼, micro: µ, thorn: Þ, ", 500);
        $this->credis->set('utf8',$utf8str);
        $this->assertEquals($utf8str, $this->credis->get('utf8'));

        // Array
        $this->assertTrue($this->credis->mSet(array('bar' => 'BAR', 'apple' => 'red')));
        $mGet = $this->credis->mGet(array('foo','bar','empty'));
        $this->assertTrue(in_array('FOO', $mGet));
        $this->assertTrue(in_array('BAR', $mGet));
        $this->assertTrue(in_array('', $mGet));

        // Non-array
        $mGet = $this->credis->mGet('foo','bar');
        $this->assertTrue(in_array('FOO', $mGet));
        $this->assertTrue(in_array('BAR', $mGet));

        // Delete strings, null response
        $this->assertEquals(2, $this->credis->del('foo','bar'));
        $this->assertFalse($this->credis->get('foo'));
        $this->assertFalse($this->credis->get('bar'));

        // Long string
        $longString = str_repeat(md5('asd'), 4096); // 128k (redis.h REDIS_INLINE_MAX_SIZE = 64k)
        $this->assertTrue($this->credis->set('long', $longString));
        $this->assertEquals($longString, $this->credis->get('long'));
    }

    public function testSets()
    {
        // Multiple arguments
        $this->assertEquals(2, $this->credis->sAdd('myset', 'Hello', 'World'));

        // Array Arguments
        $this->assertEquals(1, $this->credis->sAdd('myset', array('Hello','Cruel','World')));

        // Non-empty set
        $members = $this->credis->sMembers('myset');
        $this->assertEquals(3, count($members));
        $this->assertTrue(in_array('Hello', $members));

        // Empty set
        $this->assertEquals(array(), $this->credis->sMembers('noexist'));
    }

    public function testHashes()
    {
        $this->assertEquals(1, $this->credis->hSet('hash','field1','foo'));
        $this->assertEquals(0, $this->credis->hSet('hash','field1','foo'));
        $this->assertEquals('foo', $this->credis->hGet('hash','field1'));
        $this->assertEquals(NULL, $this->credis->hGet('hash','x'));
        $this->assertTrue($this->credis->hMSet('hash', array('field2' => 'Hello', 'field3' => 'World')));
        $this->assertEquals(array('foo','Hello',FALSE), $this->credis->hMGet('hash', array('field1','field2','nilfield')));
        $this->assertEquals(array(), $this->credis->hGetAll('nohash'));
        $this->assertEquals(array('field1' => 'foo', 'field2' => 'Hello', 'field3' => 'World'), $this->credis->hGetAll('hash'));

        // Test long hash values
        $longString = str_repeat(md5('asd'), 4096); // 128k (redis.h REDIS_INLINE_MAX_SIZE = 64k)
        $this->assertEquals(1, $this->credis->hMSet('long_hash', array('count' => 1, 'data' => $longString)), 'Set long hash value');
        $this->assertEquals($longString, $this->credis->hGet('long_hash', 'data'), 'Get long hash value');
    }

    public function testFalsey()
    {
        $this->assertEquals(Credis_Client::TYPE_NONE, $this->credis->type('foo'));
    }

    public function testPipeline()
    {
        $longString = str_repeat(md5('asd')."\r\n", 500);
        $reply = $this->credis->pipeline()
                ->set('a', 123)
                ->get('a')
                ->sAdd('b', 123)
                ->sMembers('b')
                ->set('empty','')
                ->get('empty')
                ->set('big', $longString)
                ->get('big')
                ->exec();
        $this->assertEquals(array(
            TRUE, 123, 1, array(123), TRUE, '', TRUE, $longString
        ), $reply);

        $this->assertEquals(array(), $this->credis->pipeline()->exec());
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
        $this->assertEquals(TRUE, $reply[0]);
        $this->assertFalse($reply[1]);
    }

    public function testServer()
    {
        $this->assertArrayHasKey('used_memory', $this->credis->info());
        $this->assertArrayHasKey('maxmemory', $this->credis->config('GET', 'maxmemory'));
    }

    public function testScripts()
    {
        $this->assertNull($this->credis->evalSha('1111111111111111111111111111111111111111'));
        $this->assertEquals(3, $this->credis->eval('return 3'));
        $this->assertEquals('09d3822de862f46d784e6a36848b4f0736dda47a', $this->credis->script('load', 'return 3'));
        $this->assertEquals(3, $this->credis->evalSha('09d3822de862f46d784e6a36848b4f0736dda47a'));

        $this->credis->set('foo','FOO');
        $this->assertEquals('FOOBAR', $this->credis->eval("return redis.call('get', KEYS[1])..ARGV[1]", 'foo', 'BAR'));

        $this->assertEquals(array(1,2,'three'), $this->credis->eval("return {1,2,'three'}"));
        try {
            $this->credis->eval('this-is-not-lua');
            $this->fail('Expected exception on invalid script.');
        } catch(CredisException $e) {
        }
    }
    public function testPubsub()
    {
        $timeout = 2;
        $time = time();
        $this->credis->setReadTimeout($timeout);
        try {
            $testCase = $this;
            $this->credis->pSubscribe(array('foobar','test*'), function ($credis, $pattern, $channel, $message) use ($testCase, &$time) {
                $time = time(); // Reset timeout
                // Test using: redis-cli publish foobar blah
                $testCase->assertEquals('blah', $message);
            });
            $this->fail('pSubscribe should not return.');
        } catch (CredisException $e) {
            $this->assertEquals($timeout, time() - $time);
            if ($this->useStandalone) { // phpredis does not distinguish between timed out and disconnected
                $this->assertEquals($e->getCode(), CredisException::CODE_TIMED_OUT);
            } else {
                $this->assertEquals($e->getCode(), CredisException::CODE_DISCONNECTED);
            }
        }

        // Perform a new subscription. Client should have either unsubscribed or disconnected
        $timeout = 2;
        $time = time();
        $this->credis->setReadTimeout($timeout);
        try {
            $testCase = $this;
            $this->credis->subscribe('foobar', function ($credis, $channel, $message) use ($testCase, &$time) {
                $time = time(); // Reset timeout
                // Test using: redis-cli publish foobar blah
                $testCase->assertEquals('blah', $message);
            });
            $this->fail('subscribe should not return.');
        } catch (CredisException $e) {
            $this->assertEquals($timeout, time() - $time);
            if ($this->useStandalone) { // phpredis does not distinguish between timed out and disconnected
                $this->assertEquals($e->getCode(), CredisException::CODE_TIMED_OUT);
            } else {
                $this->assertEquals($e->getCode(), CredisException::CODE_DISCONNECTED);
            }
        }
    }
  public function testDb()
  {
      $this->tearDown();
      $this->credis = new Credis_Client($this->config[0]->host, $this->config[0]->port, $this->config[0]->timeout,false,1);
      $this->assertTrue($this->credis->set('database',1));
      $this->credis->close();
      $this->credis = new Credis_Client($this->config[0]->host, $this->config[0]->port, $this->config[0]->timeout,false,0);
      $this->assertFalse($this->credis->get('database'));
      $this->credis = new Credis_Client($this->config[0]->host, $this->config[0]->port, $this->config[0]->timeout,false,1);
      $this->assertEquals(1,$this->credis->get('database'));

  }

  public function testPassword()
  {
      $this->tearDown();
      $this->assertObjectHasAttribute('password',$this->config[4]);
      $this->credis = new Credis_Client($this->config[4]->host, $this->config[4]->port, $this->config[4]->timeout,false,0,$this->config[4]->password);
      $this->assertInstanceOf('Credis_Client',$this->credis->connect());
      $this->assertTrue($this->credis->set('key','value'));
      $this->credis->close();
      $this->credis = new Credis_Client($this->config[4]->host, $this->config[4]->port, $this->config[4]->timeout,false,0,'wrongpassword');
      $this->credis->connect();
      $this->assertFalse($this->credis->set('key','value'));
      $this->assertFalse($this->credis->auth('anotherwrongpassword'));
      $this->assertTrue($this->credis->auth('thepassword'));
      $this->assertTrue($this->credis->set('key','value'));
  }
  public function testGettersAndSetters()
  {
      $this->assertEquals($this->credis->getHost(),$this->config[0]->host);
      $this->assertEquals($this->credis->getPort(),$this->config[0]->port);
      $this->assertEquals($this->credis->getSelectedDb(),0);
      $this->assertTrue($this->credis->select(2));
      $this->assertEquals($this->credis->getSelectedDb(),2);
      $this->assertTrue($this->credis->isConnected());
      $this->credis->close();
      $this->assertFalse($this->credis->isConnected());
      $this->credis = new Credis_Client($this->config[0]->host,$this->config[0]->port,null,'persistenceId');
      $this->assertEquals('persistenceId',$this->credis->getPersistence());
      $this->credis = new Credis_Client('localhost', 12345);
      $this->credis->setMaxConnectRetries(1);
      $this->setExpectedException('CredisException','Connection to Redis failed after 2 failures.');
      $this->credis->connect();
  }

  public function testConnectionStrings()
  {
      $this->credis->close();
      $this->credis = new Credis_Client('tcp://'.$this->config[0]->host.':'.$this->config[0]->port);
      $this->assertEquals($this->credis->getHost(),$this->config[0]->host);
      $this->assertEquals($this->credis->getPort(),$this->config[0]->port);
      $this->credis = new Credis_Client('tcp://'.$this->config[0]->host);
      $this->assertEquals($this->credis->getPort(),$this->config[0]->port);
      $this->credis = new Credis_Client('tcp://'.$this->config[0]->host.':'.$this->config[0]->port.'/abc123');
      $this->assertEquals('abc123',$this->credis->getPersistence());
      $this->credis = new Credis_Client(realpath(__DIR__).'/redis.sock',0,null,'persistent');
      $this->credis->connect();
      $this->credis->set('key','value');
      $this->assertEquals('value',$this->credis->get('key'));
  }

  public function testInvalidTcpConnectionstring()
  {
      $this->credis->close();
      $this->setExpectedException('CredisException','Invalid host format; expected tcp://host[:port][/persistence_identifier]');
      $this->credis = new Credis_Client('tcp://'.$this->config[0]->host.':abc');
  }

  public function testInvalidUnixSocketConnectionstring()
  {
      $this->credis->close();
      $this->setExpectedException('CredisException','Invalid unix socket format; expected unix:///path/to/redis.sock');
      $this->credis = new Credis_Client('unix://path/to/redis.sock');
  }

  public function testForceStandAloneAfterEstablishedConnection()
  {
      $this->credis->connect();
      $this->setExpectedException('CredisException','Cannot force Credis_Client to use standalone PHP driver after a connection has already been established.');
      $this->credis->forceStandalone();
  }
}