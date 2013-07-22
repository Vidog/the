<?php
	namespace The\Core\Storage;

	class NoneStorage
	{
		function __construct($host, $port)
		{
		}

		public function init()
		{
		}

		public function get($collection, $name, $default = null)
		{
			return $default;
		}

		public function set($collection, $name, $value, $ttl = 0, $tags = array())
		{
		}

		public function exists($collection, $name)
		{
			return false;
		}

		public function delete($collection, $name)
		{
		}
	}