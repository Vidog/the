<?php
	namespace The\Core;

	use The\Core\Config;

	abstract class Util
	{
		protected static $application;
		protected static $data = array();

		public static $a = 1;

		public static function setApplication($application)
		{
			self::$application = $application;
		}

		public static function getApplication()
		{
			return self::$application;
		}

		public static function arrayGet($arr, $key, $default = null)
		{
			return isset($arr[$key]) ? $arr[$key] : $default;
		}

		public static function arrayGetPath($arr, $key, $default = null)
		{
			$config = new Config();
			$config->setData($arr);
			return $config->get($key, $default);
		}

		public static function ifNull($value, $default = null)
		{
			return $value === null ? $default : $value;
		}

		public static function pr()
		{
			static $data = array();

			if (func_num_args() == 0)
			{
				return $data;
			} else
			{
				foreach (func_get_args() as $var)
				{
					$data[] = $var;
				}
			}
		}

		public static function data($type = null, $value = null)
		{
			static $data = array();

			if ($type === null)
			{
				return $data;
			} elseif ($value === null)
			{
				return self::arrayGet($data, $type);
			} else
			{
				if (!isset($data[$type]))
				{
					$data[$type] = array();
				}
				$data[$type][] = $value;
			}
		}

		public static function registry($key = null, $value = null)
		{
			static $data = array();

			if ($key === null)
			{
				return $data;
			} elseif ($value === null)
			{
				return self::arrayGet($data, $key);
			} else
			{
				$data[$key] = $value;
			}
		}

		public static function timer($name)
		{
			static $data = array();
			if (isset($data[$name]))
			{
				$res = microtime(true) - $data[$name];
				unset($data[$name]);

				return $res;
			} else
			{
				$data[$name] = microtime(true);
			}
		}

		public static function pre($s)
		{
			if (php_sapi_name() == 'cli')
			{
				return $s;
			} else
			{
				return '<pre>' . $s . '</pre>';
			}
		}

		public static function br()
		{
			if (php_sapi_name() == 'cli')
			{
				return "\r\n";
			} else
			{
				return '<br />';
			}
		}

		public static function hr()
		{
			if (php_sapi_name() == 'cli')
			{
				return '-----------------------------' . self::br();
			} else
			{
				return '<hr />';
			}
		}

		public static function makeCachedObject($className, $arr)
		{
			$obj = new \stdClass();
			foreach ($arr as $k => $v)
			{
				$obj->$k = $v;
			}

			return $obj;
		}

		public static function phpToString($php, $withReturn = false)
		{
			$makePHPValue = function ($makePHPValue, $v, &$objs, $level = 0)
			{
				$br   = "\n";
				$repl = function ($s) { return str_replace('\'', '\\\'', $s); };

				$tp  = strtolower(gettype($v));
				$val = '';

				switch ($tp)
				{
					default:
						throw new \Exception('Unknown type "' . $tp . '"');
						//print '['.$tp.']';
						break;

					case 'null':
						$val = 'null';
						break;

					case 'integer':
					case 'float':
					case 'double':
						$val = $v;
						break;

					case 'string':
						$val = "'" . $repl($v) . "'";
						break;

					case 'boolean':
						$val = $v ? 'true' : 'false';
						break;

					case 'object':
						#$objs[] = $v;
						#$val = '$o['.(sizeof($objs)-1).']';
						$val = 'unserialize(\'' . serialize($v) . '\')';
						#$val = '\The\Core\Util::makeCachedObject( \''.get_class($v).'\', '.$makePHPValue($makePHPValue, (array)$v, $objs, $level + 1).' )';
						break;

					case 'array':
						$tb  = str_repeat("\t", $level);
						$tb2 = str_repeat("\t", $level + 1);

						if (isset($v[0]))
						{
							$val = 'array(' . $br;
							foreach ($v as $kx => $vx)
							{
								$val .= $tb2 . $makePHPValue($makePHPValue, $vx, $objs, $level + 1) . ',' . $br;
							}
							$val .= $tb . ')';
						} else
						{
							$val = 'array(' . $br;
							foreach ($v as $kx => $vx)
							{
								$val .= $tb2 . '\'' . $repl($kx) . '\' => ' . $makePHPValue($makePHPValue, $vx, $objs, $level + 1) . ',' . $br;
							}
							$val .= $tb . ')';
						}
						break;

					case 'resource':
						$val = 'null';
						break;
				}

				return $val;
			};

			$phpToString = function ($value) use ($makePHPValue)
			{
				$objs = array();

				return '<?php return ' . $makePHPValue($makePHPValue, $value, $objs) . ';';
			};

			return $withReturn ? $phpToString($php) : $makePHPValue($makePHPValue, $php, $objs);

			$cSet = function ($name, $value) use ($dir, $phpToString)
			{
				return file_put_contents($dir . md5($name) . '.php', $phpToString($value));
			};

			$cGet = function ($name) use ($dir)
			{
				return require($dir . md5($name) . '.php');
			};
		}

		public static function escapeRegexp($pat)
		{
			$pat = str_replace('/', '\\/', $pat);
			$pat = str_replace('.', '\\.', $pat);
			$pat = str_replace('+', '\\+', $pat);
			$pat = str_replace('-', '\\-', $pat);
			$pat = str_replace('(', '\\(', $pat);
			$pat = str_replace(')', '\\)', $pat);
			$pat = str_replace('[', '\\[', $pat);
			$pat = str_replace(']', '\\]', $pat);
			$pat = str_replace('?', '\\?', $pat);
			$pat = str_replace('&', '\\&', $pat);
			$pat = str_replace('*', '\\*', $pat);
			$pat = str_replace(':', '\\:', $pat);

			return $pat;
		}

		public static function isArrayAssoc($array)
		{
			return array_keys($array) !== range(0, count($array) - 1);
		}

		public static function escapeHtml($str)
		{
			if (!is_scalar($str))
			{
				$str = '';
			}

			return htmlspecialchars($str);
		}

		public static function escapeSql($str)
		{
			return $str;
		}

		public static function underscoreConvert($camelCasedField)
		{
			return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $camelCasedField));
		}

		public static function time()
		{
			return time();
		}

		public static function date($format, $time = null)
		{
			return date($format, self::ifNull($time, self::time()));
		}

		public static function arrayMapByKey($array, $key)
		{
			$res = array();

			foreach($array as $item)
			{
				$res[$item[$key]] = $item;
			}

			return $res;
		}
	}