<?php

require_once dirname(__FILE__).'/../Client.php';
require_once dirname(__FILE__).'/../Cluster.php';
require_once dirname(__FILE__).'/../Rwsplit.php';

class CredisClusterTest extends PHPUnit_Framework_TestCase
{

  /** @var Credis_Cluster */
  protected $cluster;

  protected $config;

  protected $useStandalone = FALSE;

  protected function setUp()
  {
    if($this->config === NULL) {
      $configFile = dirname(__FILE__).'/test_config.json';
      if( ! file_exists($configFile) || ! ($config = file_get_contents($configFile))) {
        $this->markTestSkipped('Could not load '.$configFile);
        return;
      }
      $this->config = json_decode($config);
      $arrayConfig = array();
      foreach($this->config as $config) {
          $arrayConfig[] = (array)$config;
      }
      $this->config = $arrayConfig;
      if(count($this->config) < 6) {
          $this->markTestSkipped('Config file '.$configFile.' should contain at least 6 entries');
          return;
      }
    }

    if(!$this->useStandalone && !extension_loaded('redis')) {
      $this->fail('The Redis extension is not loaded.');
    }
    $clients = array_slice($this->config,0,4);
    $this->cluster = new Credis_Cluster($clients,2,$this->useStandalone);
  }

  protected function tearDown()
  {
    if($this->cluster) {
      foreach($this->cluster->clients() as $client){
        if($client->isConnected()) {
            $client->flushAll();
            $client->close();
        }
      }
      $this->cluster = NULL;
    }
  }

  public function testKeyHashing()
  {
      $this->tearDown();
      $this->cluster = new Credis_Cluster(array_slice($this->config,0,3),2,$this->useStandalone);
      $keys = array();
      $lines = explode("\n", file_get_contents("keys.test"));
      foreach ($lines as $line) {
          $pair = explode(':', trim($line));
          if (count($pair) >= 2) {
              $keys[$pair[0]] = $pair[1];
          }
      }
      foreach ($keys as $key => $value) {
          $this->assertTrue($this->cluster->set($key, $value));
      }
      $this->cluster = new Credis_Cluster(array_slice($this->config,0,4),2,$this->useStandalone);
      $hits = 0;
      foreach ($keys as $key => $value) {
          if ($this->cluster->get($key)) {
              $hits++;
          }
      }
      $this->assertEquals(count($keys),$hits);
  }
}
