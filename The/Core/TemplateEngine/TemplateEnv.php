<?php
	namespace The\Core\TemplateEngine;

	use The\Core\Util;
	use The\Core\Exception\TemplateEngineException;

	class TemplateEnv
	{
		public $globalVariables = array();
		public $tplEx = array();
		public $functions = array();
		public $filters = array();

		public function __construct()
		{
			/**
			@TODO kostyle
			*/
			$app = Util::getApplication();
			$prefixPath = $app->getConfig()->get('routing.prefix_path');

			$this->addGlobalVariable('app', $app);

			$this->addFunction('asset', function($fileName) use ($prefixPath)
			{
				return ($prefixPath == '/' ? '' : $prefixPath).$fileName;
			});

			$this->addFunction('route', function($routeName, $params = array(), array $getParams = array()) use ($app)
			{
				if (!is_array($params))
				{
					$params = array();
				}
				return $app->buildRoute($routeName, $params, $getParams);
			});

			$this->addFunction('makeUrl', function(array $params) use ($app)
			{
				return $app->makeUrl($params);
			});

			$this->addF('test', array('\The\Core\TemplateEngine\TemplateFunctions', 'test'));
			$this->addF('raw', array('\The\Core\TemplateEngine\TemplateFunctions', 'raw'));

			$this->addFunction('range', array('\The\Core\TemplateEngine\TemplateFunctions', 'range'));
			$this->addFunction('rand', 'mt_rand');
			$this->addFunction('mt_rand', 'mt_rand');

			$this->addFunction('parent', function()
			{
				return '';
			});

			$this->addF('isset', 'isset');
			$this->addF('length', 'sizeof');
			$this->addF('is_a', 'is_a');
			$this->addF('type', 'gettype');
			$this->addF('dump', array('\The\Core\Util', 'pr'));
			$this->addF('capitalize', 'ucfirst');
			$this->addF('title', 'ucfirst');
			$this->addF('upper', 'mb_strtoupper');
			$this->addF('lower', 'mb_strtolower');
			$this->addF('batch', 'array_chunk');
			$this->addF('default', function($value, $default)
			{
				return !$value ? $default : $value;
			});
			$this->addF('split', function($value, $delimiter, $limit = null)
			{
				return $limit ? explode($delimiter, $value, $limit) : explode($delimiter, $value);
			});
			$this->addF('join', function($value, $glue)
			{
				return implode($glue, $value);
			});
			$this->addF('has_key', function($value, $key)
			{
				return ($value ? array_key_exists($key, $value) : false);
			});
			$this->addF('push', function(&$var, $value)
			{
				$var[] = $value;
			});

			$this->addF('sin');
			$this->addF('cos');
			$this->addF('tan');
			$this->addF('sqrt');
			$this->addF('min');
			$this->addF('max');
			$this->addF('abs');
		}

		function addGlobalVariable($varName, $value)
		{
			$this->globalVariables[$varName] = $value;
		}

		function addF($funcName, $callback = null)
		{
			if($callback === null)
			{
				$callback = $funcName;
			}
			$this->addFunction($funcName, $callback);
			$this->addFilter($funcName, $callback);
		}

		function addFunction($funcName, $callback)
		{
			$this->functions[$funcName] = $callback;
		}

		function addFilter($funcName, $callback)
		{
			$this->filters[$funcName] = $callback;
		}

		function error($message)
		{
			Util::getApplication()->addError('TemplateEngine', 0, $message, '', 0);
			#throw new TemplateEngineException($message);
		}

		function callFunction($funcName, $params)
		{
			if(isset($this->functions[$funcName]) && is_callable($this->functions[$funcName]))
			{
				return call_user_func_array($this->functions[$funcName], $params);
			}else
			{
				return $this->error('No such function "'.$funcName.'"');
			}
		}

		function callFilter($funcName, $params)
		{
			if(isset($this->filters[$funcName]) && is_callable($this->filters[$funcName]))
			{
				return call_user_func_array($this->filters[$funcName], $params);
			}else
			{
				return $this->error('No such filter "'.$funcName.'"');
			}
		}

		function getObjectProperty($object, $property)
		{
			if(is_object($object))
			{
				if(property_exists($object, $property))
				{
					return $object->$property;
				}else
				{
					return $this->error('No such property "'.$property.'" in object');
				}
			}elseif(is_array($object))
			{
				if(array_key_exists($property, $object))
				{
					return $object[$property];
				}else
				{
					return $this->error('No such property "'.$property.'" in object');
				}
			}else
			{
				#throw new \Exception('Wrong object property');
			}
			return $this->error('Can\'t get property "'.$property.'" from a non-object');
		}

		function setObjectProperty($object, $property, $value)
		{
			if(is_object($object))
			{
				$object->$property = $value;
			}elseif(is_array($object))
			{
				$object[$property] = $value;
			}else
			{
				#throw new \Exception('Wrong object property');
				return $this->error('Can\'t set property "'.$property.'" on a non-object');
			}
		}

		function callObjectFunction($object, $method, $params)
		{
			if(is_object($object))
			{
				return call_user_func_array(array($object, $method), $params);
			}elseif(is_array($object))
			{
				return $this->error('No such method "'.$method.'" in object');
			}else
			{
				return $this->error('Can\'t call method "'.$method.'" on a non-object');
			}
		}
	}