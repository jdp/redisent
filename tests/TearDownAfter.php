<?php
class TearDownAfter extends PHPUnit_Framework_TestCase
{
    public static function tearDownAfterClass()
    {
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
    }
    public function testFoo()
    {
        $this->assertTrue(true);
    }
}