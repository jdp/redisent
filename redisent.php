<?php
/*
 * Redisent, a Redis interface for the modest
 * Copyright (c) 2009 Justin Poliey <jdp34@njit.edu>
 * Licensed under the MIT license
 */

class RedisException extends Exception {
}

class Redisent {

	private $host;
	private $port;
	private $__sock;
	
	/* Bulk commands have a slightly different format than others */
	private $bulk_cmds = array(
		'SET',   'GETSET', 'SETNX',
		'RPUSH', 'LPUSH',  'LSET',  'LREM',
		'SADD',  'SREM',   'SMOVE', 'SISMEMBER'
	);
	
	private $boolean_cmds = array(
		'SETNX', 'EXISTS', 'DEL', 'RENAMENX', 'MOVE', 
		'SADD',  'SREM',   'SISMEMBER'
	);
	
	function __construct($host, $port = 6379) {
		$this->host = $host;
		$this->port = $port;
	}
	
	function __call($name, $args) {
	
		/* Build the Redis protocol command */
		$name = strtoupper($name);
		if (in_array($name, $this->bulk_cmds)) {
			$value = array_pop($args);
			$command = sprintf("%s %s %d\r\n%s\r\n", $name, trim(implode(" ", $args)), strlen($value), $value);
		}
		else {
			$command = sprintf("%s %s\r\n", $name, trim(implode(" ", $args)));
		}
		
		/* Open a Redis connection and execute the command */
		$this->__sock = fsockopen($this->host, $this->port, $errno, $errstr);
		if (!$this->__sock) {
			throw new Exception("{$errno} - {$errstr}");
		}
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
		}
		fclose($this->__sock);
		return $response;
	}
	
}
		
