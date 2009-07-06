<?php
/**
 * Redisent, a Redis interface for the modest
 * @author Justin Poliey <jdp34@njit.edu>
 * @copyright 2009 Justin Poliey <jdp34@njit.edu>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package Redisent
 */

class RedisException extends Exception {
}

class Redisent {

	private $__sock;
	
	/**
	 * Redis bulk commands, they are sent in a slightly different format to the server
	 */
	private $bulk_cmds = array(
		'SET',   'GETSET', 'SETNX', 'ECHO',
		'RPUSH', 'LPUSH',  'LSET',  'LREM',
		'SADD',  'SREM',   'SMOVE', 'SISMEMBER'
	);
	
	function __construct($host, $port = 6379) {
		$this->__sock = fsockopen($host, $port, $errno, $errstr);
		if (!$this->__sock) {
			throw new Exception("{$errno} - {$errstr}");
		}
	}
	
	function __destruct() {
		fclose($this->__sock);
	}
	
	function __call($name, $args) {
	
		/* Build the Redis protocol command */
		$name = strtoupper($name);
		if (in_array($name, $this->bulk_cmds)) {
			$value = array_pop($args);
			$command = sprintf("%s %s %d\r\n%s\r\n", $name, trim(implode(' ', $args)), strlen(trim($value)), trim($value));
		}
		else {
			$command = sprintf("%s %s\r\n", $name, trim(implode(' ', $args)));
		}
		
		/* Open a Redis connection and execute the command */
		fwrite($this->__sock, $command);
		
		/* Parse the response based on the reply identifier */
		$reply = trim(fgets($this->__sock, 1024));
		switch (substr($reply, 0, 1)) {
			/* Error reply */
			case '-':
				throw new RedisException(trim(substr($reply, 4)));
				break;
			/* Inline reply */
			case '+':
				$response = substr($reply, 1);
				break;
			/* Bulk reply */
			case '$':
				if ($reply == '$-1') {
					$response = null;
					break;
				}
				$raw_response = explode(' ', trim(fgets($this->__sock, 1024)));
				$response = count($raw_response) > 1 ? $raw_response : $raw_response[0];
				break;
			/* Multi-bulk reply */
			case '*':
				$count = substr($reply, 1);
				if ($count == '-1') {
					return null;
				}
				$response = array();
				for ($i = 0; $i < $count; $i++) {
					$bulk_head = trim(fgets($this->__sock, 1024));
					$response[] = ($bulk_head == '$-1') ? null : trim(fgets($this->__sock, 1024));
				}
				break;
			/* Integer reply */
			case ':':
				$response = substr($reply, 1);
				break;
			default:
				throw new RedisException('invalid server response type');
				break;
		}
		return $response;
	}
	
}
		
