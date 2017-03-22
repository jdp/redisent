<?php
class TearDownAfter extends PHPUnit_Framework_TestCase
{
    public static function tearDownAfterClass()
    {
        if(preg_match('/^WIN/',strtoupper(PHP_OS))){
            echo "Please kill all Redis instances manually:".PHP_EOL;
        } else {
            chdir(__DIR__);
            $directoryIterator = new DirectoryIterator(__DIR__);
            foreach($directoryIterator as $item){
                if(!$item->isfile() || !preg_match('/^redis\-(.+)\.pid$/',$item->getFilename())){
                    continue;
                }
                $pid = trim(file_get_contents($item->getFilename()));
                if(function_exists('posix_kill')){
                    posix_kill($pid,15);
                } else {
                    exec('kill '.$pid);
                }
            }
            unlink('dump.rdb');
            unlink('redis-master.conf');
            unlink('redis-slave.conf');
            unlink('redis-sentinel.conf');
            rename('redis-master.conf.bak','redis-master.conf');
            rename('redis-slave.conf.bak','redis-slave.conf');
            rename('redis-sentinel.conf.bak','redis-sentinel.conf');
        }
    }
    public function testFoo()
    {
        $this->assertTrue(true);
    }
}