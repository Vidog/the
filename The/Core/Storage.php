<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;

	class Storage
	{
		use ApplicationImplant;

		public $className;
		public $options = array();
		public $reflecton;
		public $instance;

		public function __construct($className, $options = array())
		{
			$this->className = $className;
			$this->options = $options;
		}

		public function init()
		{
	        $this->reflecton = new \ReflectionClass($this->className);
	        $constructMethod = $this->reflecton->getMethod('__construct');
	        $params = $constructMethod->getParameters();
	        $opt = array();
	        foreach($params as $v)
	        {
	            $opt[$v->name] = isset($this->options[$v->name]) ? $this->options[$v->name] : null;
	        }
	        $this->instance = $this->reflecton->newInstanceArgs($opt);
	        return $this->instance->init();
		}

		public function get($collection, $name, $default = null)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function getByTag($collection, $tag)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function set($collection, $name, $value, $ttl = 0, $tags = array())
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function exists($collection, $name)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function existsByTag($collection, $tag)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function delete($collection, $name)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function deleteByTag($collection, $tag)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function count($collection, $name)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function countByTag($collection, $tag)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function increment($collection, $name, $value = 1)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function incrementByTag($collection, $tag, $value = 1)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function decrement($collection, $name, $value = 1)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function decrementByTag($collection, $tag, $value = 1)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}

		public function clear($collection)
		{
			$this->init();
			return call_user_func_array(array($this->instance, __FUNCTION__), func_get_args());
		}
	}