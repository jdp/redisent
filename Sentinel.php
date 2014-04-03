<?php
class Credis_Sentinel
{
    /**
     * @var Credis_Client
     */
    protected $_client;
    /**
     * Should the master be used for read queries too?
     * @var bool
     */
    protected $_readOnMaster = true;
    /**
     * @param Credis_Client $client
     * @param bool $readOnMaster
     */
    public function __construct(Credis_Client $client, $readOnMaster=true)
    {
        if(!$client instanceof Credis_Client){
            throw new CredisException('Sentinel client should be an instance of Credis_Client');
        }
        $client->forceStandalone();
        $this->_client = $client;
        $this->_readOnMaster = (bool)$readOnMaster;
    }
    /**
     * @param string $name
     * @return Credis_Client
     */
    public function getMasterClient($name)
    {
        $master = $this->getMasterAddressByName($name);
        if(!isset($master[0]) || !isset($master[1])){
            throw new CredisException('No master found');
        }
        return new Credis_Client($master[0],$master[1]);
    }
    /**
     * @param string $name
     * @return Credis_Client[]
     */
    public function getSlaveClients($name)
    {
        $slaves = $this->getSlaves($name);
        if(!is_array($slaves) && count($slaves) == 0){
            throw new CredisException('No slaves found');
        }
        if($this->_readOnMaster){
            $slaves[] = $this->getMaster($name);
        }
        $workingSlaves = array();
        foreach($slaves as $slave) {
            if(!isset($slave[9])){
                throw new CredisException('Can\' retrieve slave status');
            }
            if(!strstr($slave[9],'s_down') && !strstr($slave[9],'disconnected')) {
                $workingSlaves[] = new Credis_Client($slave[3],$slave[5]);
            }
        }
        return $workingSlaves;
    }
    /**
     * Returns a Redis cluster object containing all slaves and the master
     * @param string $name
     * @return Credis_Cluster
     */
    public function getCluster($name)
    {
        $clients = array();
        $master = $this->getMaster($name);
        $clients[] = array('host'=>$master[3],'port'=>$master[5],'master'=>true);
        $slaves = $this->getSlaves($name);
        foreach($slaves as $slave){
            if(!strstr($slave[9],'s_down') && !strstr($slave[9],'disconnected')) {
                $clients[] =  array('host'=>$slave[3],'port'=>$slave[5],'master'=>false);
            }
        }
        return new Credis_Cluster($clients,0,true);
    }
    /**
     * @return mixed
     */
    public function getMasters()
    {
        return $this->_client->sentinel('masters');
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function getSlaves($name)
    {
        return $this->_client->sentinel('slaves',$name);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function getMaster($name)
    {
        return $this->_client->sentinel('master',$name);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function getMasterAddressByName($name)
    {
        return $this->_client->sentinel('get-master-addr-by-name',$name);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function ping()
    {
        return $this->_client->ping();
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function failover($name)
    {
        return $this->_client->sentinel('failover',$name);
    }
}