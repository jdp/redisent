<?php
/**
 * Credis_Client, a fork of Redisent (a Redis interface for the modest)
 *
 * All commands are compatible with phpredis library except:
 *   - use "pipeline()" to start a pipeline of commands instead of multi(Redis::PIPELINE)
 *   - any arrays passed as arguments will be flattened automatically
 *   - setOption and getOption are not supported in native mode
 *   - order of arguments follows redis-cli instead of phpredis where they differ
 *
 * Uses phpredis library if extension is installed.
 *
 * Establishes connection lazily.
 *
 * @author Colin Mollenhour <colin@mollenhour.com>
 * @author Justin Poliey <jdp34@njit.edu>
 * @copyright 2009 Justin Poliey <jdp34@njit.edu>, Colin Mollenhour <colin@mollenhour.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package Credis_Client
 */

if( ! defined('CRLF')) define('CRLF', sprintf('%s%s', chr(13), chr(10)));

/**
 * Wraps native Redis errors in friendlier PHP exceptions
 */
class CredisException extends Exception {
}

/**
 * Credis_Client, a Redis interface for the modest among us
 */
class Credis_Client {

    const TYPE_STRING      = 'string';
    const TYPE_LIST        = 'list';
    const TYPE_SET         = 'set';
    const TYPE_ZSET        = 'zset';
    const TYPE_HASH        = 'hash';
    const TYPE_NONE        = 'none';

    /**
     * Socket connection to the Redis server or Redis library instance
     * @var resource|Redis
     */
    private $redis;
    
    /**
     * Host of the Redis server
     * @var string
     */
    protected $host;
    
    /**
     * Port on which the Redis server is running
     * @var integer
     */
    protected $port;

    /**
     * Timeout for connecting to Redis server
     * @var float
     */
    protected $timeout;

    /**
     * @var bool
     */
    protected $connected = FALSE;

    /**
     * @var bool
     */
    protected $standalone;

    /**
     * @var bool
     */
    protected $use_pipeline = FALSE;

    /**
     * @var array
     */
    protected $commands;

    /**
     * @var bool
     */
    protected $is_multi = FALSE;

    /**
     * Aliases for backwards compatibility with phpredis
     * @var array
     */
    protected $aliased_methods = array("delete"=>"del","getkeys"=>"keys","sremove"=>"srem");

    /**
     * Creates a Redisent connection to the Redis server on host {@link $host} and port {@link $port}.
     * @param string $host The hostname of the Redis server
     * @param integer $port The port number of the Redis server
     * @param float $timeout  Timeout period in seconds
     */
    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 2.5)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->standalone = ! extension_loaded('redis');
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return Credis_Client
     */
    public function forceStandalone()
    {
        if($this->connected) {
            throw new CredisException('Cannot force Credis_Client to use native PHP after a connection has already been established.');
        }
        $this->standalone = TRUE;
        return $this;
    }

    /**
     * @return bool
     */
    public function connect()
    {
        if($this->standalone) {
            $this->redis = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
            return (bool) $this->redis;
        }
        else {
            $this->redis = new Redis;
            return $this->redis->connect($this->host, $this->port, $this->timeout);
        }
    }

    /**
     * @return bool
     */
    public function close()
    {
        $result = TRUE;
        if($this->connected) {
            if($this->standalone) {
                $result = fclose($this->redis);
            }
            else {
                $result = $this->redis->close();
            }
            $this->connected = FALSE;
        }
        return $result;
    }
    
    public function __call($name, $args)
    {
        // Lazy connection
        if( ! $this->connected) {
            if( ! $this->connect() ) {
                throw new CredisException('Could not connect to redis server.');
            }
        }

        $name = strtolower($name);

        // Flatten array arguments to multiple arguments except if using phpredis with mget
        if($name == 'mget' && ! $this->standalone) {
            if(isset($args[0]) && ! is_array($args[0])) {
                $args = array($args);
            }
        }
        else if($name == 'lrem' && ! $this->standalone) {
            $args = array($args[0], $args[2], $args[1]);
        }
        else {
            $argsFlat = NULL;
            foreach($args as $index => $arg) {
                if(is_array($arg)) {
                    if($argsFlat === NULL) {
                        $argsFlat = array_slice($args, 0, $index);
                    }
                    $argsFlat = array_merge($argsFlat, $arg);
                } else if($argsFlat !== NULL) {
                    $argsFlat[] = $arg;
                }
            }
            if($argsFlat !== NULL) {
                $args = $argsFlat;
            }
        }

        // Send request via native PHP
        if($this->standalone)
        {
            // Translate multi(Redis::PIPELINE) to pipeline()
            if($name == 'multi' && isset($args[0]) && $args[0] == 2 /*Redis::PIPELINE*/) {
                $name = 'pipeline';
            }

            // In pipeline mode
            if($this->use_pipeline)
            {
                if($name == 'pipeline') {
                    throw new CredisException('A pipeline is already in use and only one pipeline is supported.');
                }
                if($name == 'exec') {
                    $commands = array_map( array('self', '_prepare_command'), $this->commands );
                    if($commands) {
                        $this->write_command(implode('', $commands));
                    }
                    $response = array();
                    foreach($this->commands as $command) {
                        $response[] = $this->read_reply($command[0]);
                    }
                    $this->use_pipeline = FALSE;
                    $this->commands = NULL;
                    return $response;
                }
                else {
                    array_unshift($args, $name);
                    $this->commands[] = $args;
                    return $this;
                }
            }

            // Start pipeline mode
            if($name == 'pipeline')
            {
                $this->use_pipeline = TRUE;
                $this->commands = array();
                return $this;
            }

            // Non-pipeline mode
            array_unshift($args, $name);
            $command = self::_prepare_command($args);
            $this->write_command($command);
            $response = $this->read_reply($name);

            // Transaction mode
            if($this->is_multi && $name == 'exec') {
                $this->is_multi = FALSE;
            }
            else if($this->is_multi || $name == 'multi') {
                $this->is_multi = TRUE;
                $response = $this;
            }
        }

        // Send request via phpredis client
        else
        {
            // Proxy pipeline mode to the phpredis library
            if($name == 'pipeline') {
                $this->redis->pipeline();
                return $this;
            }
            // Use aliases to be compatible with phpredis wrapper
            if(isset($this->aliased_methods[$name])) {
                $name = $this->aliased_methods[$name];
            }
            $response = call_user_func_array(array($this->redis, $name), $args);
        }

        return $response;
    }

    protected function write_command($command)
    {
        /* Execute the command */
        for ($written = 0; $written < strlen($command); $written += $fwrite) {
            $fwrite = fwrite($this->redis, substr($command, $written));
            if ($fwrite === FALSE) {
                throw new CredisException('Failed to write entire command to stream');
            }
        }
    }

    protected function read_reply($name = '')
    {
        $reply = trim(fgets($this->redis, 512));
        switch (substr($reply, 0, 1)) {
            /* Error reply */
            case '-':
                throw new CredisException(substr(trim($reply), 4));
                break;
            /* Inline reply */
            case '+':
                $response = substr(trim($reply), 1);
                break;
            /* Bulk reply */
            case '$':
                if ($reply == '$-1') return null;
                $response = null;
                $read = 0;
                $size = substr($reply, 1);
                if ($size > 0){
                    do {
                        $block_size = $size - $read;
                        if ($block_size > 1024) $block_size = 1024;
                        if ($block_size < 1) break;
                        $response .= fread($this->redis, $block_size);
                        $read += $block_size;
                    } while ($read < $size);
                }
                fread($this->redis, 2); /* discard crlf */
                break;
            /* Multi-bulk reply */
            case '*':
                $count = substr($reply, 1);
                if ($count == '-1') return null;

                $response = array();
                for ($i = 0; $i < $count; $i++) {
                        $response[] = $this->read_reply();
                }
                break;
            /* Integer reply */
            case ':':
                $response = intval(substr(trim($reply), 1));
                break;
            default:
                throw new CredisException("invalid server response: {$reply}");
                break;
        }

        // Smooth over differences between phpredis and native response
        switch($name)
        {
            case 'hgetall':
                $keys = $values = array();
                while($response) {
                    $keys[] = array_shift($response);
                    $values[] = array_shift($response);
                }
                $response = array_combine($keys, $values);
                break;

            case 'info':
                $lines = explode(CRLF, $response);
                $response = array();
                foreach($lines as $line) {
                    list($key, $value) = explode(':', $line, 2);
                    $response[$key] = $value;
                }
                break;
            
            default:
                break;
        }

        /* Party on */
        return $response;
    }

    /**
     * Build the Redis unified protocol command
     *
     * @param array $args
     * @return string
     */
    private static function _prepare_command($args)
    {
        $args[0] = strtoupper($args[0]);
        return sprintf('*%d%s%s%s', count($args), CRLF, implode(array_map(array('self', '_map'), $args), CRLF), CRLF);
    }

    private static function _map($arg)
    {
        return sprintf('$%d%s%s', strlen($arg), CRLF, $arg);
    }

}
