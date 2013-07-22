<?php
	namespace The\Core;

	use The\Core\Util;
	use The\Core\FieldDependency;

	abstract class Yaml
	{
		public static $globals = array();
		public static $aliases = array();
		public static $encodeFunctions = array();
		public static $decodeFunctions = array();

		public static function data($data)
		{
			return str_replace(array("\t", "\r\n", "\r"), array('  ', "\n", "\n"), self::globals()."\n".$data);
		}

		public static function registerAlias($name, $value)
		{
			self::$aliases[$name] = $value;
			return true;
		}

		public static function registerGlobal($name, $value)
		{
			self::$globals[$name] = $value;
			return true;
		}

		public static function globals()
		{
			$res = '';
			/*$res .= '__alias__:'."\n";
			foreach(self::$aliases as $name => $value)
			{
				$res .= '  - &'.$name."\n";
				$res .= self::encode($value);
			}*/

			return $res;
		}

		public static function decodeValue($value)
		{
			$res = self::decode('__value__: '.$value.'');
			return $res['__value__'];
		}

		public static function params($value)
		{
			$val = '['.$value.']';
			return self::decodeValue($val);
		}

		public static function registerDecodeFunction($functionName, $callback)
		{
			self::$decodeFunctions['!'.$functionName] = $callback;
			return true;
		}

		public static function registerEncodeFunction($functionName, $callback)
		{
			self::$encodeFunctions[$functionName] = $callback;
			return true;
		}

		public static function decodeFunctions()
		{
			$funcs = array_merge(self::$decodeFunctions, array(
				'!test' => function($a)
				{
					return 'test';
				},
				'!include' => function($fileName)
				{
					return Yaml::decode( file_get_contents(Util::getApplication()->getApplicationDir('Config').$fileName) );
				},
				'!validator' => function($type)
				{
					$args = func_get_args();
					array_shift($args);
					return array('type' => $type, 'params' => $args);
				},
				'!dependency' => function($fieldName)
				{
					return new FieldDependency($fieldName);
				},
			));
			foreach($funcs as $funcName => $callback)
			{
				$res[$funcName] = function($value, $tag, $flag) use ($funcs)
				{
					$params = Yaml::params($value);
					$func = Util::arrayGet($funcs, $tag);
					if(is_callable($func))
					{
						return call_user_func_array($func, $params);
					}else
					{
						return $value;
					}
				};
			}
			return $res;
		}

		public static function encode($data)
		{
			return yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
		}

		public static function decode($data)
		{
			$ndocs = false;
			$res = yaml_parse( self::data($data), 0, $ndocs, self::decodeFunctions() );
			unset($res['__alias__']);
			return $res;
		}
	}