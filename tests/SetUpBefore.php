<?php
class SetUpBefore extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        chdir(__DIR__);
        $directoryIterator = new DirectoryIterator(__DIR__);
        foreach($directoryIterator as $item){
            if(!$item->isfile() || !preg_match('/^redis\-(.+)\.conf$/',$item->getFilename())){
                continue;
            }
            exec('redis-server '.$item->getFilename());
        }
    }
    public function testFoo()
    {
        $this->assertTrue(true);
    }
}