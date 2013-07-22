<?php
	namespace The\Core\Storage;

	class MongoStorage
	{
		private $host;
		private $port;
		private $database;
		private $instance;
		private $databaseInstance;

		function __construct($host, $port, $database)
		{
			$this->host = $host;
			$this->port = $port;
			$this->database = $database;
		}

		public function init()
		{
			$this->instance = new \MongoClient();
			$db = $this->database;
			$this->databaseInstance = $this->instance->$db;
		}

		public function getCollection($collection)
		{
			return $this->databaseInstance->$collection;
		}

		public function get($collection, $name, $default = null)
		{
			$c = $this->getCollection($collection);
			$cur = $c->findOne(array('name' => $name));
			return (bool)$cur ? $cur['value'] : $default;
		}

		public function getByTag($collection, $tag)
		{
			$c = $this->getCollection($collection);
		}

		public function set($collection, $name, $value, $ttl = 0, $tags = array())
		{
			$c = $this->getCollection($collection);
			$doc = array(
				'name' => $name,
				'value' => $value,
				'die_at' => ($ttl > 0) ? (time() + $ttl) : 0,
				'tags' => $tags,
			);
			if($this->exists($collection, $name))
			{
				return (bool)$c->update(array('name' => $name), $doc);
			}else
			{
				return (bool)$c->insert($doc);
			}
		}

		public function exists($collection, $name)
		{
			$c = $this->getCollection($collection);
			$cur = $c->findOne(array('name' => $name));
			return (bool)$cur;
		}

		public function existsByTag($collection, $tag)
		{
			$c = $this->getCollection($collection);
		}

		public function delete($collection, $name)
		{
			$c = $this->getCollection($collection);
			return (bool)$c->remove(array('name' => $name));
		}

		public function deleteByTag($collection, $tag)
		{
			$c = $this->getCollection($collection);
		}

		public function count($collection, $name)
		{
			$c = $this->getCollection($collection);
		}

		public function countByTag($collection, $tag)
		{
			$c = $this->getCollection($collection);
		}

		public function increment($collection, $name, $value = 1)
		{
			$c = $this->getCollection($collection);
		}

		public function incrementByTag($collection, $tag, $value = 1)
		{
			$c = $this->getCollection($collection);
		}

		public function decrement($collection, $name, $value = 1)
		{
			$c = $this->getCollection($collection);
		}

		public function decrementByTag($collection, $tag, $value = 1)
		{
			$c = $this->getCollection($collection);
		}

		public function clear($collection = null)
		{
			$c = $this->getCollection($collection);
		}
	}