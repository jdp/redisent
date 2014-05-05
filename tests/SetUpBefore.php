<?php
class SetUpBefore extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        chdir(__DIR__);
        $directoryIterator = new DirectoryIterator(__DIR__);
        foreach($directoryIterator as $item){
            if(!$item->isfile() || !preg_match('/^redis\-(.+)\.conf$/',$item->getFilename()) || $item->getFilename() == 'redis-sentinel.conf'){
                continue;
            }
            exec('redis-server '.$item->getFilename());
        }
        copy('redis-master.conf','redis-master.conf.bak');
        copy('redis-slave.conf','redis-slave.conf.bak');
        copy('redis-sentinel.conf','redis-sentinel.conf.bak');
        exec('redis-sentinel redis-sentinel.conf');
    }
    public function testFoo()
    {
        $this->assertTrue(true);
    }
}