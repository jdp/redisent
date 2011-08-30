<?php
/**
 * Credis, a fork of Redisent (a Redis interface for the modest)
 *
 * @author Colin Mollenhour <colin@mollenhour.com>
 * @author Justin Poliey <jdp34@njit.edu>
 * @copyright 2009 Justin Poliey <jdp34@njit.edu>, Colin Mollenhour <colin@mollenhour.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package Credis
 */

if( ! defined('CRLF')) define('CRLF', sprintf('%s%s', chr(13), chr(10)));

/**
 * Wraps native Redis errors in friendlier PHP exceptions
 */
class CredisException extends Exception {
}

/**
 * Credis, a Redis interface for the modest among us
 */
class Credis {

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
    protected $native;

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
        $this->native = ! extension_loaded('redis');
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return bool
     */
    public function connect()
    {
        if($this->native) {
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
            if($this->native) {
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
        $name = strtolower($name);

        // Flatten array arguments to multiple arguments
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

        // Send request via native PHP
        if($this->native) {
            $response = $this->write_request($name, $args);
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
        }
        // Send request via phpredis client
        else {
            // Use aliases to be compatible with phpredis wrapper
            if(isset($this->aliased_methods[$name])) {
                $name = $this->aliased_methods[$name];
            }
            $response = call_user_func_array(array($this->redis, $name), $args);
        }

        return $response;
    }

    private static function _map($arg)
    {
        return sprintf('$%d%s%s', strlen($arg), CRLF, $arg);
    }

    protected function write_request($name, $args)
    {
		/* Build the Redis unified protocol command */
		array_unshift($args, strtoupper($name));
		$command = sprintf('*%d%s%s%s', count($args), CRLF, implode(array_map(array($this, '_map'), $args), CRLF), CRLF);
		
		/* Open a Redis connection and execute the command */
		for ($written = 0; $written < strlen($command); $written += $fwrite) {
			$fwrite = fwrite($this->redis, substr($command, $written));
			if ($fwrite === FALSE) {
				throw new CredisException('Failed to write entire command to stream');
			}
		}
		
		/* Parse the response based on the reply identifier */
		return $this->read_reply();
	}

	protected function read_reply()
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
		/* Party on */
		return $response;
	}
}
