<?php
	namespace The\Core\Storage;

	class RedisStorage
	{
		private $host;
		private $port;
		private $instance;

		function __construct($host, $port)
		{
			$this->host = $host;
			$this->port = $port;
		}

		public function init()
		{
			$this->instance = new \Core\RedisServer($this->host, $this->port);
		}

		public function get($collection, $name, $default = null)
		{
			$name = $collection.'_'.$name;
			$r = $this->instance->Get($name);
			return (bool)$r ? unserialize($r) : $default;
		}

		public function set($collection, $name, $value, $ttl = 0, $tags = array())
		{
			return (bool)$this->instance->Set($collection.'_'.$name, serialize($value));
		}

		public function exists($collection, $name)
		{
			return (bool)$this->instance->Exists($collection.'_'.$name);
		}

		public function delete($collection, $name)
		{
			return (bool)$this->instance->Del($collection.'_'.$name);
		}
	}