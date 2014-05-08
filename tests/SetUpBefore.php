<?php
class SetUpBefore extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        if(preg_match('/^WIN/',strtoupper(PHP_OS))){
            echo "Unit tests will not work automatically on Windows. Please setup all Redis instances manually:".PHP_EOL;
            echo "\tredis-server redis-master.conf".PHP_EOL;
            echo "\tredis-server redis-slave.conf".PHP_EOL;
            echo "\tredis-server redis-2.conf".PHP_EOL;
            echo "\tredis-server redis-3.conf".PHP_EOL;
            echo "\tredis-server redis-4.conf".PHP_EOL;
            echo "\tredis-server redis-auth.conf".PHP_EOL;
            echo "\tredis-server redis-socket.conf".PHP_EOL;
            echo "\tredis-sentinel redis-sentinel.conf".PHP_EOL.PHP_EOL;
        } else {
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
    }
    public function testFoo()
    {
        $this->assertTrue(true);
    }
}