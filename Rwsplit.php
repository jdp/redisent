<?php
class Credis_Rwsplit
{
    public static $commands = array(
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
    /**
     * Is this Redis command a read-only command?
     * @param string $command
     * @return bool
     */
    public static function isReadOnlyCommand($command)
    {
        return in_array(strtoupper($command),self::$commands);
    }
}