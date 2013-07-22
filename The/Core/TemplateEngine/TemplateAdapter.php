<?php
	namespace The\Core\TemplateEngine;

	use The\Core\Util;

	class TemplateAdapter
	{
		protected $template;
		protected $variables = array();
		protected $env;

		public function __construct($env)
		{
			$this->env = $env;
		}

		function setVariable($varName, $value)
		{
			$this->variables[$varName] = $value;
		}

		function run($template, $variables)
		{
			$this->template = $template;
			$this->variables = array_merge($this->env->globalVariables, $variables);

			try
			{
				ob_start();
				eval('?>'.$this->template.'<?');
				$tpl = ob_get_clean();
			} catch(\Exception $e)
			{
				/**
				@TODO kostyle
				*/
				Util::getApplication()->onException($e);

				$tpl = '';
			}

			return $tpl;
		}

		function getString($s)
		{
			return $s;
		}

		function getVariable($var)
		{
			if(array_key_exists($var, $this->variables))
			{
				return $this->variables[$var];
			}else
			{
				return $this->env->error('No such variable "'.$var.'"');
			}
		}

		function callFunction($funcName, $params)
		{
			return $this->env->callFunction($funcName, $params);
		}

		function callFilter($funcName, $params)
		{
			return $this->env->callFilter($funcName, $params);
		}

		function getObjectProperty($object, $property)
		{
			return $this->env->getObjectProperty($object, $property);
		}

		function setObjectProperty($object, $property, $value)
		{
			return $this->env->setObjectProperty($object, $property, $value);
		}

		function callObjectFunction($object, $method, $params)
		{
			return $this->env->callObjectFunction($object, $method, $params);
		}

		function makeEcho($s)
		{
			return (is_a($s, 'The\Core\TemplateEngine\TemplateRawInput') ? (string)$s : htmlspecialchars($s));
		}

		function getNone1()
		{
			return '';
		}

		function getNone2()
		{
			return '';
		}
	}