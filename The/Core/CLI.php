<?php
	namespace The\Core;

	use The\Core\Util;

	class CLI
	{
		private $foreground_colors = array();
		private $background_colors = array();
		public static $instance;
		private static $arguments = false;

		const OUT_TEXT = 'text';
		const OUT_ERROR = 'error';
		const OUT_WARNING = 'warning';
		const OUT_INFORMATION = 'information';
		const OUT_SUCCESS = 'success';
 
		public function __construct()
		{
			// Set up shell colors
			$this->foreground_colors['black'] = '0;30';
			$this->foreground_colors['dark_gray'] = '1;30';
			$this->foreground_colors['blue'] = '0;34';
			$this->foreground_colors['light_blue'] = '1;34';
			$this->foreground_colors['green'] = '0;32';
			$this->foreground_colors['light_green'] = '1;32';
			$this->foreground_colors['cyan'] = '0;36';
			$this->foreground_colors['light_cyan'] = '1;36';
			$this->foreground_colors['red'] = '0;31';
			$this->foreground_colors['light_red'] = '1;31';
			$this->foreground_colors['purple'] = '0;35';
			$this->foreground_colors['light_purple'] = '1;35';
			$this->foreground_colors['brown'] = '0;33';
			$this->foreground_colors['yellow'] = '1;33';
			$this->foreground_colors['light_gray'] = '0;37';
			$this->foreground_colors['white'] = '1;37';
 
			$this->background_colors['black'] = '40';
			$this->background_colors['red'] = '41';
			$this->background_colors['green'] = '42';
			$this->background_colors['yellow'] = '43';
			$this->background_colors['blue'] = '44';
			$this->background_colors['magenta'] = '45';
			$this->background_colors['cyan'] = '46';
			$this->background_colors['light_gray'] = '47';
		}

		public static function getInstance()
		{
			if(!self::$instance)
			{
				self::$instance = new self;
			}
			return self::$instance;
		}
 
		public static function getColoredString($string, $foreground_color = null, $background_color = null)
		{
			$me = self::getInstance();

			$colored_string = "";
 
			if (isset($me->foreground_colors[$foreground_color]))
			{
				$colored_string .= "\033[" . $me->foreground_colors[$foreground_color] . "m";
			}

			if (isset($me->background_colors[$background_color]))
			{
				$colored_string .= "\033[" . $me->background_colors[$background_color] . "m";
			}
 
			$colored_string .=  $string . "\033[0m";
 
			return $colored_string;
		}

		public static function getArguments($argv = null, $noopt = array())
		{
			if(false === self::$arguments)
			{
				if($argv === null)
				{
					$argv = $GLOBALS['argv'];
				}

				$result = array();
		        $params = $argv;
		        // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
		        reset($params);
		        while (list($tmp, $p) = each($params))
		        {
		            if ($p{0} == '-')
		            {
		                $pname = substr($p, 1);
		                $value = true;
		                if ($pname{0} == '-')
		                {
		                    // long-opt (--<param>)
		                    $pname = substr($pname, 1);
		                    if (strpos($p, '=') !== false)
		                    {
		                        // value specified inline (--<param>=<value>)
		                        list($pname, $value) = explode('=', substr($p, 2), 2);
		                    }
		                }else
		                {
		                	if(strlen($pname) > 1)
		                	{
		                		$value = substr($pname, 1);
		                		$pname = substr($pname, 0, 1);
		                	}
		                }
		                // check if next parameter is a descriptor or a value
		                $nextparm = current($params);
		                if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
		                if(isset($result[$pname]))
		                {
		                	if(is_array($result[$pname]))
		                	{
		                		$result[$pname][] = $value;
		                	}else
		                	{
		                		$result[$pname] = array($result[$pname], $value);
		                	}
		                }else
		                {
		                	$result[$pname] = $value;
		                }
		            }else
		            {
		                // param doesn't belong to any option
		                $result[] = $p;
		            }
		        }
		        self::$arguments = $result;
			}
	        return self::$arguments;
	    }

	    public static function getArgument($name, $default = null)
	    {
	    	$arguments = self::getArguments();
	    	return isset($arguments[$name]) ? $arguments[$name] : $default;
	    }

	    public static function out($s, $type = null)
	    {
	    	$indent = true;
	    	$fc = null;
	    	$bc = null;
	    	switch($type)
	    	{
	    		case self::OUT_TEXT:

	    		break;

				case self::OUT_ERROR:
					$fc = 'red';
					$bc = 'light_gray';
	    		break;
	    		
				case self::OUT_WARNING:
					$fc = 'purple';
					$bc = 'light_gray';
	    		break;
	    		
				case self::OUT_INFORMATION:
					$fc = 'blue';
					$bc = 'light_gray';
	    		break;
	    		
				case self::OUT_SUCCESS:
					$fc = 'green';
					$bc = 'light_gray';
	    		break;

	    		default:
	    			$indent = false;
	    		break;
	    	}
	    	return self::getColoredString(($indent ? Util::br() : '').$s.($indent ? Util::br() : ''), $fc, $bc).Util::br();
	    }

	    public static function outCentered($s, $type = null)
	    {
			$len = strlen($s);
			#@TODO length > 80 and explode by \r\n
			if($len > 80)
			{

			}else
			{
				$s = str_repeat(' ', floor((80 - $len) / 2)).$s;
			}
			return self::out($s, $type);
	    }

	    public static function outRight($s, $type = null)
	    {
			$len = strlen($s);
			#@TODO length > 80 and explode by \r\n
			if($len > 80)
			{

			}else
			{
				$s = str_repeat(' ', floor((80 - $len))).$s;
			}
			return self::out($s, $type);
	    }
	}