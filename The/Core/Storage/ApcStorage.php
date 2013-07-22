<?php
	namespace The\Core\Storage;

	use The\Core\Storage;

	class APCStorage extends Storage
	{
		function __construct()
		{

		}

		public function init()
		{

		}

		public function get($collection, $name, $default = null)
		{
			$name = $collection.'_'.$name;

			$r = apc_fetch($name);

			return $r !== null ? $r : $default;
		}

		public function set($collection, $name, $value, $ttl = 0, $tags = array())
		{
			$key = $collection.'_'.$name;

			if (apc_exists($key)){
				return apc_store($key, $value);
			} else {
				return apc_add($key, $value);
			}

		}

		public function exists($collection, $name)
		{
			//return false;
			return apc_exists($collection.'_'.$name);
		}

		public function delete($collection, $name)
		{
			return apc_delete($collection.'_'.$name);
		}
	}