<?php
/**
 * Credis, a Redis interface for the modest
 * @author Justin Poliey <jdp34@njit.edu>
 * @copyright 2009 Justin Poliey <jdp34@njit.edu>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package Credis
 */

#require_once 'Credis/Client.php';

/**
 * A generalized Credis_Client interface for a cluster of Redis servers
 */
class Credis_Cluster {

	/**
	 * Collection of Credis_Client objects attached to Redis servers
	 * @var array
	 * @access private
	 */
	private $clients;
	
	/**
	 * Aliases of Credis_Client objects attached to Redis servers, used to route commands to specific servers
	 * @see Credis_Cluster::to
	 * @var array
	 * @access private
	 */
	private $aliases;
	
	/**
	 * Hash ring of Redis server nodes
	 * @var array
	 * @access private
	 */
	private $ring;
	
	/**
	 * Individual nodes of pointers to Redis servers on the hash ring
	 * @var array
	 * @access private
	 */
	private $nodes;
	
	/**
	 * Number of replicas of each node to make around the hash ring
	 * @var integer
	 * @access private
	 */
	private $replicas = 128;

	/**
	 * The commands that are not subject to hashing
	 * @var array
	 * @access private
	 */
	private $dont_hash = array(
		'RANDOMKEY', 'DBSIZE',
		'SELECT',    'MOVE',    'FLUSHDB',  'FLUSHALL',
		'SAVE',      'BGSAVE',  'LASTSAVE', 'SHUTDOWN',
		'INFO',      'MONITOR', 'SLAVEOF'
	);

	/**
	 * Creates an interface to a cluster of Redis servers
	 * @param array $servers The Redis servers in the cluster. Each server should be in the format array('host' => hostname, 'port' => port)
	 */
	function __construct($servers) {
		$this->ring = array();
		$this->aliases = array();
		foreach ($servers as $alias => $server) {
			$this->clients[] = new Credis_Client($server['host'], $server['port'], isset($server['timeout']) ? $server['timeout'] : 2.5);
			if (is_string($alias)) {
				$this->aliases[$alias] = $this->clients[count($this->clients)-1];
			}
 			for ($replica = 1; $replica <= $this->replicas; $replica++) {
				$this->ring[crc32($server['host'].':'.$server['port'].'-'.$replica)] = $this->clients[count($this->clients)-1];
			}
		}
		ksort($this->ring, SORT_NUMERIC);
		$this->nodes = array_keys($this->ring);
	}
	
	/**
	 * Routes a command to a specific Redis server aliased by {$alias}.
	 * @param string $alias The alias of the Redis server
	 * @return Credis_Client The client object attached to the Redis server
	 */
	function to($alias) {
		if (isset($this->aliases[$alias])) {
			return $this->aliases[$alias];
		}
		else {
			throw new CredisException("That Credis_Client alias does not exist");
		}
	}

	/* Execute a Redis command on the cluster */
	function __call($name, $args) {
	
		/* Pick a server node to send the command to */
		$name = strtoupper($name);
		if (!in_array($name, $this->dont_hash)) {
			$node = $this->nextNode(crc32($args[0]));
			$credisClient = $this->ring[$node];
    	}
    	else {
			$credisClient = $this->clients[0];
    	}

		/* Execute the command on the server */
    	return call_user_func_array(array($credisClient, $name), $args);
	}

	/**
	 * Routes to the proper server node
	 * @param integer $needle The hash value of the Redis command
	 * @return Credis_Client The client object associated with the hash
	 */
	private function nextNode($needle) {
		$haystack = $this->nodes;
		while (count($haystack) > 2) {
			$try = floor(count($haystack) / 2);
			if ($haystack[$try] == $needle) {
				return $needle;
			}
			if ($needle < $haystack[$try]) {
				$haystack = array_slice($haystack, 0, $try + 1);
			}
			if ($needle > $haystack[$try]) {
				$haystack = array_slice($haystack, $try + 1);
			}
		}
		return $haystack[count($haystack)-1];
	}

}

