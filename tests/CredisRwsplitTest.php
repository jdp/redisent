<?php

require_once dirname(__FILE__).'/../Rwsplit.php';

class CredisRwsplitTest extends PHPUnit_Framework_TestCase
{
    public function testRwsplit()
    {
        $readOnlyCommands = array(
            'EXISTS',
            'TYPE',
            'KEYS',
            'SCAN',
            'RANDOMKEY',
            'TTL',
            'GET',
            'MGET',
            'SUBSTR',
            'STRLEN',
            'GETRANGE',
            'GETBIT',
            'LLEN',
            'LRANGE',
            'LINDEX',
            'SCARD',
            'SISMEMBER',
            'SINTER',
            'SUNION',
            'SDIFF',
            'SMEMBERS',
            'SSCAN',
            'SRANDMEMBER',
            'ZRANGE',
            'ZREVRANGE',
            'ZRANGEBYSCORE',
            'ZREVRANGEBYSCORE',
            'ZCARD',
            'ZSCORE',
            'ZCOUNT',
            'ZRANK',
            'ZREVRANK',
            'ZSCAN',
            'HGET',
            'HMGET',
            'HEXISTS',
            'HLEN',
            'HKEYS',
            'HVALS',
            'HGETALL',
            'HSCAN',
            'PING',
            'AUTH',
            'SELECT',
            'ECHO',
            'QUIT',
            'OBJECT',
            'BITCOUNT',
            'TIME',
            'SORT'
        );
        foreach($readOnlyCommands as $command){
            $this->assertTrue(Credis_Rwsplit::isReadOnlyCommand($command));
        }
        $this->assertFalse(Credis_Rwsplit::isReadOnlyCommand("SET"));
        $this->assertFalse(Credis_Rwsplit::isReadOnlyCommand("HDEL"));
        $this->assertFalse(Credis_Rwsplit::isReadOnlyCommand("RPUSH"));
        $this->assertFalse(Credis_Rwsplit::isReadOnlyCommand("SMOVE"));
        $this->assertFalse(Credis_Rwsplit::isReadOnlyCommand("ZADD"));
    }
}
