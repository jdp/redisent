<?php

require_once dirname(__FILE__).'/../Client.php';
require_once dirname(__FILE__).'/../Cluster.php';
require_once dirname(__FILE__).'/../Rwsplit.php';
require_once dirname(__FILE__).'/../Sentinel.php';

class CredisSentinelTest extends PHPUnit_Framework_TestCase
{

  /** @var Credis_Sentinel */
  protected $sentinel;

  protected $sentinelConfig;
  protected $redisConfig;

  protected $useStandalone = FALSE;

  protected function setUp()
  {
    if($this->sentinelConfig === NULL) {
      $configFile = dirname(__FILE__).'/sentinel_config.json';
      if( ! file_exists($configFile) || ! ($config = file_get_contents($configFile))) {
        $this->markTestSkipped('Could not load '.$configFile);
        return;
      }
      $this->sentinelConfig = json_decode($config);
    }

    if($this->redisConfig === NULL) {
        $configFile = dirname(__FILE__).'/redis_config.json';
        if( ! file_exists($configFile) || ! ($config = file_get_contents($configFile))) {
            $this->markTestSkipped('Could not load '.$configFile);
            return;
        }
        $this->redisConfig = json_decode($config);
        $arrayConfig = array();
        foreach($this->redisConfig as $config) {
            $arrayConfig[] = (array)$config;
        }
        $this->redisConfig = $arrayConfig;
    }
    $sentinelClient = new Credis_Client($this->sentinelConfig->host, $this->sentinelConfig->port);
    $this->sentinel = new Credis_Sentinel($sentinelClient);
    if($this->useStandalone) {
      $sentinelClient->forceStandalone();
    } else if ( ! extension_loaded('redis')) {
      $this->fail('The Redis extension is not loaded.');
    }
  }
  protected function tearDown()
  {
    if($this->sentinel) {
      if($this->sentinel->getCluster($this->sentinelConfig->clustername)->isConnected()) {
          $this->sentinel->getCluster($this->sentinelConfig->clustername)->flushAll();
          $this->sentinel->getCluster($this->sentinelConfig->clustername)->close();
      }
      $this->sentinel = NULL;
    }
  }
  public function testMasterClient()
  {
      $master = $this->sentinel->getMasterClient($this->sentinelConfig->clustername);
      $this->assertInstanceOf('Credis_Client',$master);
      $this->assertEquals($this->redisConfig[0]['port'],$master->getPort());
      $this->setExpectedException('CredisException','Master not found');
      $this->sentinel->getMasterClient('non-existing-cluster');
  }
  public function testSlaveClient()
  {
      $slaves = $this->sentinel->getSlaveClients($this->sentinelConfig->clustername);
      $this->assertInternalType('array',$slaves);
      $this->assertCount(2,$slaves);
      foreach($slaves as $slave){
          $this->assertInstanceOf('Credis_Client',$slave);
      }
  }
  public function testNonExistingClusterNameWhenCreatingSlaves()
  {
      $this->setExpectedException('CredisException','No such master with that name');
      $this->sentinel->createSlaveClients('non-existing-cluster');
  }
  public function testCreateCluster()
  {
      $cluster = $this->sentinel->createCluster($this->sentinelConfig->clustername);
      $this->assertInstanceOf('Credis_Cluster',$cluster);
      $this->assertCount(2,$cluster->clients());
      $this->setExpectedException('CredisException','The master is down');
      $this->sentinel->createCluster($this->sentinelConfig->downclustername);
  }
}
