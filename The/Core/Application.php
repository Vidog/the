<?php
	namespace The\Core;

	use The\Core\Query;
    use The\Core\Util;
	use The\Core\Yaml;
	use The\Core\DB;
	use The\Core\Config;
	use The\Core\Request;
	use The\Core\Flash;
	use The\Core\Exception\ValidatorException;
	use The\Core\Exception\ClassException;
	use The\Core\Exception\TemplateEngineException;
	use The\Core\Exception\NotFoundException;
	use The\Core\Lib\Dumphper;

	class Application
	{
		protected $applicationName;
		protected $env;
		protected $isDebug;
		protected $rootDir;

		protected $request;
		protected $config;
		protected $flash;

		protected $output;

		protected $controller;

		protected $errors = array();

		protected $url;
		protected $db = array();
		protected $service = array();
		protected $storage = array();
		protected $mailSender = array();
		protected $fileReader = array();
		protected $templating = array();
		protected $repository = array();
		protected $acl = array();
		protected $users = array();
		protected $registry = array();
		protected $headers = array();
	    protected $interruptRender = false;

		protected $routeName;
		protected $route;

		protected $injections = array();

		protected $isAjaxResponse = false;
		protected $isJsonEncodeDisabled = false;

		public function __construct($applicationName, $env = null, $isDebug = null, $rootDir = null)
		{
			/**
			@TODO kostyle
			*/
			Util::setApplication($this);

			$this->setApplicationName( Util::ifNull($applicationName) );
			$this->setEnv( Util::ifNull($env) );
			$this->setIsDebug( Util::ifNull($isDebug, false) );
			$this->setRootDir( Util::ifNull($rootDir) );

			$this->init();

		}

		public function addInjection($className, $method, $event, $injection)
		{
			if(!isset($this->injections[$className]))
			{
				$this->injections[$className] = array();
			}
			if(!isset($this->injections[$className][$method]))
			{
				$this->injections[$className][$method] = array();
			}
			if(!isset($this->injections[$className][$method][$event]))
			{
				$this->injections[$className][$method][$event] = array();
			}
			$this->injections[$className][$method][$event][] = $injection;

			return $this;
		}

		public function getInjection($className, $method, $event)
		{
			$class = Util::arrayGet($this->injections, '\\'.$className);
			if($class)
			{
				$method = Util::arrayGet($class, $method);
				if($method)
				{
					$event = Util::arrayGet($method, $event);
					if($event)
					{
						return $event;
					}
				}
			}

			return false;
		}

		public function callInjection($className, $method, $event, $injector, $params = array())
		{
			$res = array();

			$events = $this->getInjection($className, $method, $event);
			if($events)
			{
				array_unshift($params, $injector);
				foreach($events as $event)
				{
					$res[] = call_user_func_array($event, $params);
				}
			}

			return $res;
		}

		protected function init()
		{
			Util::timer('APP:INIT');

			if($this->getIsDebug())
	        {
				Util::timer($this->getApplicationName().'::'.'Init');

	            /*error_reporting(E_ALL);
	            ini_set('display_errors', 'On');
	            ini_set('html_errors', 'Off');

	            set_time_limit(0);
	            ignore_user_abort();*/
	        }else
	        {
	            /*error_reporting(0);
	            ini_set('display_errors', 'Off');*/
	        }

	        error_reporting(E_ALL);
            ini_set('display_errors', 'On');
            ini_set('html_errors', 'Off');

            set_time_limit(0);

	        register_shutdown_function(array($this, 'onShutdown'));

	        set_error_handler(array($this, 'onError'));

	        set_exception_handler(array($this, 'onException'));

	        ob_start();

	        $envConfigDir = $this->getApplicationDir('Config');
	        $envConfigFile = $envConfigDir.'config_'.$this->getEnv().'.php';

	        $config = new Config();
	        $config->setApplication($this);

	        $storageKey = 'config_'.$this->getApplicationName().'_'.$this->getEnv();

	        if(!$this->getIsDebug() && apc_exists($storageKey) && ($config = apc_fetch($storageKey)))
	        {

	        }else
	        {
	        	$config->loadFromFile( $this->getDir('Config').'config.yml' );

	        	$configApp = new Config();
		        $configApp->setApplication($this);
		        $configApp->loadFromFile( $envConfigDir.'config.yml' );

		        $configEnv = new Config();
		        $configEnv->setApplication($this);
		        $configEnv->loadFromFile( $envConfigDir.'config_'.$this->getEnv().'.yml' );

		        $config->merge($configApp);
		        $config->merge($configEnv);

		        apc_add($storageKey, $config);
	        }

	        $this->setConfig( $config );

	        /** Request **/
	        $request = new Request();
	        $request->setApplication($this);

	        $this->setRequest($request);

	        /** Flash **/
	        $flash = new Flash();
	        $flash->setApplication($this);
			$messages = unserialize( $this->getRequest()->session('THE_FLASH_BAGS') );
			$flash->setMessages(!is_array($messages) ? array() : $messages);

	        $this->setFlash($flash);

			Util::data('TIMERS', array('APP:INIT', Util::timer('APP:INIT')));
		}

		public function run()
		{
			/*
			$this->getRequest()->removeCookie('PHPSESSID');
			$this->getRequest()->removeCookie('admin_id');
			$this->getRequest()->removeCookie('admin_key');
			*/
			Util::timer('APP:ROUTE');

			$url = $this->getUrl();
			$fullUrl = $url;

			$removeDoubleSlashes = function($s)
			{
				$s = str_replace('\\', '/', $s);
				while(substr_count($s, '//'))
				{
					$s = str_replace('//', '/', $s);
				}
				return $s;
			};

            list($url, $queryString) = explode('?', $url.'?');

			$prefixPath = $this->getConfig()->get('routing.prefix_path');
			$url = $removeDoubleSlashes('/'.$url.'/');
			$url = $removeDoubleSlashes( '/'.preg_replace('/^' . Util::escapeRegexp( $removeDoubleSlashes('/'.$prefixPath.'/') ) . '/i', '', $url) );
			if ($url != '/'){
				$url = preg_replace('/\/$/', '', $url);
			}

			$route = $this->findUrl($url);

			try
			{
				if($route !== false)
				{
					$routeAcl = Util::arrayGet($route['config'], 'acl');

					if($routeAcl)
					{
						$aclUser = Util::arrayGet($routeAcl, 'user');
						$aclRoles = Util::arrayGet($routeAcl, 'roles', array());

						if($aclUser)
						{
							$user = $this->getUser($aclUser);
							if(is_object($user) && $user->getIsAuthorized())
							{
								/**
								@TODO roles
								*/
							}else
							{
								$this->getRequest()->setSession('_acl_back_to', $fullUrl);
								if(is_object($user))
								{
									$user->getACL()->makeAuth($user);
								}else
								{
									throw new NotFoundException;
								}
							}
						}
					}

					$this->setRouteName( $route['name'] );
					$this->setRoute($route);

					$request = $this->getRequest();

					$paramsConfig = Util::arrayGet($route['config'], 'params', array());
					$params = array();
					foreach($paramsConfig as $paramName => $paramData)
					{
						$defaultValue = Util::arrayGet($paramData, 'default_value');

						$value = $request->get($paramName, $defaultValue);

						$type = Util::arrayGet($paramData, 'type', 'String');
						$validators = Util::arrayGet($paramData, 'validators', array());

						$b = true;
						foreach($validators as $validatorData)
						{
							$validatorClassName = $this->getClassName($validatorData['type'], 'Validator');
							$validatorParams = $validatorData['params'];

							$validatorReflection = new \ReflectionClass($validatorClassName);
							$validator = $validatorReflection->newInstanceArgs( $validatorParams );

							try
							{
								$check = $validator->validate($value);
							}catch(ValidatorException $e)
							{
								$check = false;
							}

							$b = $b && $check;

							if(!$check)
							{
								break;
							}
						}

						if(!$b)
						{
							$value = $defaultValue;
						}

						$request->setParam($paramName, $value);
					}
					Util::data('TIMERS', array('APP:ROUTE', Util::timer('APP:ROUTE')));

					Util::timer('APP:CONTROLLER');
					$controller = null;
					$output = $this->callControllerAction($route['callback'][0], $route['callback'][1], $route['params'], $route['defaults'], $controller);
					$this->setController($controller);
					$this->setOutput($output);
					Util::data('TIMERS', array('APP:CONTROLLER', Util::timer('APP:CONTROLLER')));
				}else
				{
					throw new NotFoundException;
				}
			} catch(\Exception $e)
			{
				if($e instanceof NotFoundException)
				{
					$this->setOutput( $this->return404() );
				}else
				{
					$this->setOutput( $this->return503() );
				}
			}
		}

		public function addHeader($name, $value = null)
		{
			$this->headers[$name] = $value;

			return $this;
		}

		public function isAjax()
		{
			return ($this->getRequest()->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest');
		}

		public function makeUrl(array $params)
		{
			$request = $this->getRequest();
			$get = $request->get();

			if(!($firstParam = reset($get)))
			{
				array_shift($get);
			}
			$query = array_merge($get, $params);

			$uri = '?'.http_build_query($query);

			return $uri;
		}

		public function buildRoutePattern($route)
		{
			$pat = isset($route['pattern']) ? $route['pattern'] : '';
			$pat = Util::escapeRegexp($pat);
			$params = isset($route['requirements']) && is_array($route['requirements']) ? $route['requirements'] : array();
			$paramIndex = array();
			$i = 0;
			preg_match_all('/\/\{(\w+)\}/iu', $pat, $m);
			#preg_match_all('/\{(\w+)\}/iu', $pat, $m);
			$defaults = Util::arrayGet($route, 'defaults', array());
			foreach($m[1] as $v)
			{
				$key = $v;
				$paramIndex[$key] = ++$i;
				$def = Util::arrayGet($defaults, $key);
				$rPat = isset($params[$key]) ? '(\/'.$params[$key].'|)' : '(\/.*|)';
				#$rPat = isset($params[$key]) ? '('.$params[$key].'|)' : '(\/.*|)';
				$pat = str_replace('\/{'.$key.'}', $rPat, $pat);
				#$pat = str_replace('{'.$key.'}', $rPat, $pat);
			}
			return array($pat, $paramIndex, $defaults);
		}

		public function redirect($route, array $params = array(), array $getParams = array(), $doExit = false)
		{
			$url = $this->buildRoute($route, $params, $getParams);

			return $this->redirectUrl($url, $doExit);
		}

		public function redirectUrl($url, $doExit = false)
		{
			$this->setInterruptRender(true);

			header('Location: '.$url);
			if($doExit)
			{
				die;
			}
			return true;
		}

		/**
		 * @return mixed
		 */
		public function getUrl()
		{
			if(!$this->url)
			{
				$this->url = $this->getRequest()->server('REQUEST_URI');
			}

			return $this->url;
		}

		/**
		 * @param mixed $url
		 */
		public function setUrl($url)
		{
			$this->url = $url;
		}

		protected function return500($doExit = true)
		{
			if($doExit)
			{
				die;
			}
			return true;
		}

		protected function return503($doExit = true)
		{
			if($doExit)
			{
				die;
			}
			return true;
		}

		protected function return404($doExit = true)
		{
			if($doExit)
			{
				die;
			}
			return true;
		}

		protected function return403($doExit = true)
		{
			if($doExit)
			{
				die;
			}
			return true;
		}

		public function isCurrentRoute($routeName, array $params = array())
		{
			$paramsSame = true;

			$route        = $this->getRoute();
			$nowRouteName = Util::arrayGet($route, 'name');
			$routeParams  = Util::arrayGet($route, 'params');

			if ($routeParams && $params)
			{
				foreach ($routeParams as $paramKey => $paramValue)
				{
					if ($paramValue)
					{
						$routeParamValue = Util::arrayGet($params, $paramKey);


						if ($routeParamValue != $paramValue)
						{
							$paramsSame = false;
						}
					}
				}
			}

			return $routeName == $nowRouteName && $paramsSame;
		}

		public function buildRoute($routeName, array $params = array(), array $getParams = array())
		{
			$routes = $this->getRoutes();
			$route = Util::arrayGet($routes, $routeName);
			if($route)
			{
				$removeDoubleSlashes = function($s)
				{
					$s = str_replace('\\', '/', $s);
					while(substr_count($s, '//'))
					{
						$s = str_replace('//', '/', $s);
					}
					return $s;
				};

				$defaults = Util::arrayGet($route, 'defaults');
				$url = $removeDoubleSlashes('/'.$this->getConfig()->get('routing.prefix_path').'/'.preg_replace_callback('/\{(\w+)\}/i', function($m) use ($defaults, $params)
				{
					$param = Util::arrayGet($params, $m[1], Util::arrayGet($defaults, $m[1]));
					return $param;
				}, Util::arrayGet($route, 'pattern')).'/');

				$url = ( $url =='/' ? $url : rtrim($url, '/') );

				$getParams = http_build_query($getParams);
				if($getParams)
				{
					$url .= '?'.$getParams;
				}
			}else
			{
				$url = '';
			}
			return $url;
		}

		protected function getRoutes()
		{
			$routes = $this->getConfig()->get('routing.routes');

			$routes = array_merge(array(
				'_system_dependency' => array(
					'pattern' => '/_system/dependency',
					'callback' => array('System', 'dependency'),
				),
				'_system_autocomplete' => array(
					'pattern' => '/_system/autocomplete',
					'callback' => array('System', 'autocomplete'),
				),
				'_system_upload' => array(
					'pattern' => '/_system/upload/{strategy}',
					'requirements' => array(
						'strategy' => '[\w_]+',
					),
					'callback' => array('System', 'upload'),
				),
			), $routes);

			return $routes;
		}

		protected function findUrl($url)
		{
			$routes = $this->getRoutes();

			$foundedRoute = false;

			foreach($routes as $routeName => $route)
			{
				list($pat, $paramIndex, $defaults) = $this->buildRoutePattern($route);

				if(preg_match('/^'.$pat.'$/iu', $url, $m))
				{
					$par = array();
					foreach($paramIndex as $paramName => $ind)
					{
						$par[$paramName] = substr($m[$ind], 1);
					}
					$foundedRoute = array(
						'name' => $routeName,
						'config' => $route,
						'pattern' => $pat,
						'params' => $par,
						'defaults' => $defaults,
						'callback' => Util::arrayGet($route, 'callback'),
					);
					break;
				}
			}

			if($foundedRoute !== false)
			{
				return $foundedRoute;
			}else
			{
				return false;
			}
		}

		public function callControllerAction($controllerName, $action, $params = array(), $defaults = array(), &$controller = null)
		{
			$className = $this->getClassName($controllerName, 'Controller');
			$method = $action.'Action';

			$reflection = new \ReflectionClass($className);
			$rMethod = $reflection->getMethod($method);

			$pars = array();
			$rParams = $rMethod->getParameters();
			foreach($rParams as $p)
			{
				$nm = $p->getName();
				$pars[$nm] = isset($params[$nm]) && $params[$nm] ? $params[$nm] : (isset($defaults[$nm]) ? $defaults[$nm] : ($p->isOptional() ? $p->getDefaultValue() : null));
			}

			$controller = new $className();
			$controller->setApplication($this);

			return call_user_func_array(array($controller, $method), $pars);
		}

		public function renderPartial($templateName, array $variables = array(), $templateAdapter = null)
		{
			#return 'partial render for "'.$templateName.'" with "'.print_r($variables, true).'" :)';
			return $this->getTemplating()->render($this->getTemplateFileName($templateName, 'render_partial'), $variables, $templateAdapter);
		}

		public function render($templateName, array $variables = array(), $templateAdapter = null)
		{
			if($this->getInterruptRender())
			{
				return;
			}

			$key = md5( json_encode( func_get_args() ) );

			Util::timer('RENDER:'.$key);

			$templateFileName = $this->getTemplateFileName($templateName, 'render');

			Util::data('Templating', array('base_name' => $templateName, 'file_name' => $templateFileName));

			$res = $this->getTemplating()->render($templateFileName, $variables, $templateAdapter);

			Util::data('TIMERS', array('RENDER:'.$templateFileName, Util::timer('RENDER:'.$key)));

			return $res;
		}

		public function getClassNameClasses($class)
		{
			return array();
		}

		public function getClassName($classFileName, $classDirectory = null, $applicationName = null)
		{
			if($classFileName[0] == '@')
			{
				$ex = explode(':', $classFileName.':');
				$ex2 = explode('#', $ex[0]);
				if(sizeof($ex2) > 1 && $ex2[0] == '@App')
				{
					$applicationName = $ex2[1];
					$classFileName = $ex[1];
				}
			}

			$applicationName = Util::ifNull($applicationName, $this->getApplicationName());

			if($classDirectory !== null)
			{
				$classDirectory = $classDirectory.'\\';
			}
			$class = $classDirectory.ucfirst($classFileName).implode('', array_reverse(explode('\\', $classDirectory)));

			$classes = array(
				'\The\App\\'.$applicationName.'\\'.$class,
			);

			$classes = array_merge($classes, $this->getClassNameClasses($class));

			$classes[] = '\The\Core\\'.$class;

			foreach($classes as $className)
			{
				if( class_exists($className) )
				{

					return $className;
				}
			}
			return $class;
		}

		public function getDir($dir = null)
		{
			return $this->getRootDir().($dir ? $dir.'/' : '');
		}

		public function getApplicationDir($dir = null, $applicationName = null)
		{
			return $this->getDir('App').Util::ifNull($applicationName, $this->getApplicationName()).'/'.($dir ? $dir.'/' : '');
		}

		public function onError($errorCode, $errorMessage, $errorFile, $errorLine, $errorEnv)
	    {
	        $this->addError('php', $errorCode, $errorMessage, $errorFile, $errorLine, $errorEnv);
	    }

	    public function onException(\Exception $exception)
	    {
	        $this->addError('exception', $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), array(), $exception);
	    }

	    public function addError($errorGroup, $errorCode, $errorMessage, $errorFile, $errorLine, $errorEnv = array(), $data = null)
	    {
			if($errorGroup == 'exception' && $data instanceof NotFoundException)
			{

			}else
			{
				error_log('[THE:'.$errorGroup.($errorGroup == 'exception' ? get_class($data) : '').' #'.$errorCode.'] :: ['.$this->getRequest()->server('HTTP_HOST').' -- '.$this->getRequest()->server('REQUEST_URI').'] :: ['.$errorMessage.'] :: ['.$errorFile.':'.$errorLine.']');
			}

	        $this->errors[] = array(
	            'group' => $errorGroup,
	            'code' => $errorCode,
	            'message' => $errorMessage,
	            'file' => $errorFile,
	            'line' => $errorLine,
	            'env' => $errorEnv,
	            'data' => $data,
	            #'backtrace' => debug_backtrace(),
	        );
	    }

	    public function getErrors()
	    {
	    	return $this->errors;
	    }

	    public function getDBRealQuery($sql, $params)
	    {
	    	$real_query = preg_replace_callback('/(\:\w+)/i', function($m) use ($params)
			{
				$param = Util::arrayGet($params, substr($m[1], 1));
				$fx = function($fx, $s)
				{
					if(is_array($s))
					{
						$x = array();
						foreach($s as $v)
						{
							$x[] = $fx($v);
						}
						return implode(',', $x);
					}else
					{
						return is_numeric($s) ? $s : '\''.Query::sql($s).'\'';
					}
				};
				return $fx($fx, $param);
			}, $sql);

			return $real_query;
	    }

	    public function onShutdown()
	    {
	    	Util::timer('APP:SHUTDOWN');

			$isDebug = $this->getIsDebug();

	    	$buffer = ob_get_clean();

	    	$fatalPattern = '/Fatal error\: (.*?) in (.*?)\.php on line (\d+)/i';
	    	if(preg_match($fatalPattern, $buffer, $m))
	        {
	            $this->addError('fatal', 0, $m[1], $m[2], $m[3]);
	            $buffer = preg_replace($fatalPattern, '', $buffer);
	        }

	        $outputData = $this->getOutput();
            $isAjax = ($this->isAjax() || $this->getIsAjaxResponse()) && !$this->isJsonEncodeDisabled;
            if($isAjax)
            {
                if(!is_array($outputData))
                {
                    $outputData = array('data' => (string)$outputData);
                }
            }else {
                $outputData = (string)$outputData;
            }

		    $this->getRequest()->setSession('THE_FLASH_BAGS', serialize($this->getFlash()->getMessages()));

			Util::data('TIMERS', array('APP:SHUTDOWN', Util::timer('APP:SHUTDOWN')));

			if($isDebug)
	        {
                $timeDone = Util::timer($this->getApplicationName().'::'.'Init');
                $memory = round(memory_get_peak_usage() / 1024 / 1024, 2);
	        	$_THE_debug = '';

	        	$isCLI = php_sapi_name() == 'cli';

	        	$pr = Util::pr();
	        	$prNum = sizeof($pr);

	        	$errors = $this->getErrors();
	        	$errorsNum = sizeof($errors);

	        	$queries = Util::data('DB');
	        	$queriesNum = sizeof($queries);

	        	$includes = get_included_files();
	        	$includesNum = sizeof($includes);

	        	$templates = Util::data('Templating');
	        	$templatesNum = sizeof($templates);

	        	$debugDatas = array(
	        		'pr' => array(
	        			'size' => $prNum,
	        			'color' => 'blue',
	        		),
	        		'errors' => array(
	        			'size' => $errorsNum,
	        			'color' => 'red',
	        		),
	        		'queries' => array(
	        			'size' => $queriesNum,
	        			'color' => 'green',
	        		),
	        	);

	        	$showBlock = ($errorsNum > 0) || ($prNum > 0) || (bool)$buffer;

	        	$debugInfo = '';

	        	foreach($debugDatas as $key => $data)
	        	{
	        		if($data['size'] > 0)
	        		{
	        			$debugInfo .= '<span style="color: '.$data['color'].'; font-weight: bold; margin-left: 10px;">'.$key.': '.$data['size'].'</span>';
	        		}
	        	}

	        	$timeRPS = round(1 / $timeDone);
	        	$timeDone = round($timeDone, 6);

	        	$ind = 2;
	        	$timeDoneSmall = round($timeDone, $ind);
	        	while(($timeDoneSmall <= 0))
	        	{
	        		$timeDoneSmall = round($timeDone, ++$ind);
	        	}

	        	if($timeDone > 5)
	        	{
	        		$timeDone = '<span style="color: red; font-weight: bold;">CRITICAL '.$timeDone.' S</span>';
	        		$timeDoneSmall = '<span style="color: red; font-weight: bold;">CRITICAL '.$timeDoneSmall.' S</span>';
	        	}elseif($timeDone > 1)
	        	{
	        		$timeDone = '<span style="color: maroon;">'.$timeDone.' S</span>';
	        		$timeDoneSmall = '<span style="color: maroon;">'.$timeDoneSmall.' S</span>';
	        	}elseif($timeDone > 0.5)
	        	{
	        		$timeDone = '<span style="color: brown;">'.$timeDone.' S</span>';
	        		$timeDoneSmall = '<span style="color: brown;">'.$timeDoneSmall.' S</span>';
	        	}elseif($timeDone > 0.1)
	        	{
	        		$timeDone = '<span style="color: orange;">'.$timeDone.' S</span>';
	        		$timeDoneSmall = '<span style="color: orange;">'.$timeDoneSmall.' S</span>';
	        	}else
	        	{
	        		$timeDone = '<span style="color: green;">'.$timeDone.' S</span>';
	        		$timeDoneSmall = '<span style="color: green;">'.$timeDoneSmall.' S</span>';
	        	}

	        	if($memory > 50)
	        	{
	        		$memory = '<span style="color: red; font-weight: bold;">CRITICAL '.$memory.' MB</span>';
	        	}elseif($memory > 15)
	        	{
	        		$memory = '<span style="color: maroon;">'.$memory.' MB</span>';
	        	}elseif($memory > 10)
	        	{
	        		$memory = '<span style="color: brown;">'.$memory.' MB</span>';
	        	}elseif($memory > 5)
	        	{
	        		$memory = '<span style="color: orange;">'.$memory.' MB</span>';
	        	}else
	        	{
	        		$memory = '<span style="color: green;">'.$memory.' MB</span>';
	        	}

	        	$dbTime = 0;

	        	if($queriesNum)
	        	{
        			foreach($queries as $query)
        			{
        				$dbTime += $query['time_execute'];
        			}
        		}

	        	if(!$isCLI)
	        	{
	        		$reqKeys = array_keys($_REQUEST);
        			$route = array_shift( $reqKeys );
        			$keys = $_REQUEST;
        			array_shift($keys);
        			$keys = http_build_query($keys);
        			$subInfo = ' '.$timeDoneSmall.' <small>[db: '.round($dbTime, 4).' s]</small> with '.$memory.' '.date('H:i:s').' ';
        			$extraTitle = ' <small>'.$subInfo.'<u>'.$route.'</u> :: '.$keys.'</small>';
        			$blockType = ($showBlock ? 'block' : 'none');
	        		if($isAjax)
	        		{
	        			$randId = uniqid().mt_rand(1000000, 9999999);
	        			$_THE_debug .= '<script>$(\'#THE_debug_data\').append( $(\'#THE_debug_'.$randId.'\').html() ); $(\'#THE_debug_'.$randId.'\').remove(); '.($blockType == 'block' ? '$(\'#THE_debug_data_head_'.$randId.'\').click(); $(\'#THE_debug_data\').slideDown();' : '').'</script>';
		        		$_THE_debug .= '<div id="THE_debug_'.$randId.'">';
		        			$_THE_debug .= '<div id="THE_debug_data_head_'.$randId.'" style="font-size: 15px; font-weight: bold; cursor: pointer; border: 1px dotted black;" onclick="$(\'.THE_debug_data\').hide(); var st = document.getElementById(\'THE_debug_data_'.$randId.'\').style; if(st.display == \'none\'){ st.display = \'block\'; }else{ st.display = \'none\'; }"><p style="float: left;">[AJAX]'.$extraTitle.'</p><p style="float: right;">'.$debugInfo.'</p><p style="clear: both;"></p></div>';
		        			$_THE_debug .= '<div class="THE_debug_data" id="THE_debug_data_'.$randId.'" style="display: '.$blockType.';">';
		        				$_THE_debug .= '<div>';
	        		}else
	        		{
	        			$_THE_debug .= '<!DOCTYPE html5><html><head><title>[THE Debugger]</title></head><body><style>div.THE_debug_panel p{ margin: 0px; padding: 1px 3px 0 3px; }</style>';
	        			$_THE_debug .= '<script>if (typeof(jQuery) == \'undefined\'){document.write("<scr" + "ipt type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js\"></scr" + "ipt>");} var bodyYOverflow;</script>';
	        			$_THE_debug .= '<div class="THE_debug_panel_restorer" style="display: none; position: fixed; right: 5px; bottom: 5px;"><button onclick="$(\'div.THE_debug_panel\').fadeIn(); $(\'div.THE_debug_panel_restorer\').fadeOut();">Restore me!</button></div>';
	        			$_THE_debug .= '<div class="THE_debug_panel" style="position: fixed; width: 400px; z-index: 100000; right: 0; bottom: 0; line-height: 15px; border: 1px solid black; background: #fff0f5; color: black;">';
		        			$_THE_debug .= '<div style="font-size: 12px;cursor: pointer;" onclick="var st = document.getElementById(\'THE_debug_data\').style; if(st.display == \'none\'){ $(this).parents(\'.THE_debug_panel\').animate({width: 1000}); $(\'#THE_debug_data\').slideDown(); bodyYOverflow = $(\'body\').css(\'overflow-y\'); $(\'body\').css(\'overflow-y\', \'hidden\'); }else{ $(\'#THE_debug_data\').slideUp(); $(this).parents(\'.THE_debug_panel\').animate({width: 400}); $(\'body\').css(\'overflow-y\', bodyYOverflow); }"><p style="float: left;">THE Debug Panel (v 1.0 beta)'.$subInfo.'</p><p style="float: right;">'.$debugInfo.'<span style="display: inline-block;"><span style="cursor: pointer;" onclick="$(\'div.THE_debug_panel\').fadeOut(); $(\'div.THE_debug_panel_restorer\').fadeIn();">X</span></span></p><p style="clear: both;"></p></div>';
		        			$_THE_debug .= '<div id="THE_debug_data" style="display: '.$blockType.'; max-height: 700px; overflow-y: auto; padding: 5px;">';
		        				$_THE_debug .= '<div style="font-size: 15px; font-weight: bold; cursor: pointer; border: 1px dotted black;" onclick="$(\'.THE_debug_data\').hide(); var st = document.getElementById(\'THE_debug_data_main\').style; if(st.display == \'none\'){ st.display = \'block\'; }else{ st.display = \'none\'; }"><p style="float: left;">[MAIN]'.$extraTitle.'</p><p style="float: right;">'.$debugInfo.'</p><p style="clear: both;"></p></div>';
		        				$_THE_debug .= '<div class="THE_debug_data" id="THE_debug_data_main" style="display: block;">';
	        		}
	        	}

	        	$_THE_debug .= Util::hr();
	        	if($isCLI)
	        	{
		        	$_THE_debug .= 'Execution time: '.$timeDone.' ('.$timeRPS.' RPS)'.Util::br();
		        	$_THE_debug .= 'Memory usage: '.$memory.' MB'.Util::br();

		        	if( ($user = $this->getUser()) )
		        	{
		        		$_THE_debug .= 'User: '.(($user->getIsAuthorized()) ? 'user' : 'Guest').Util::br();
		        	}

		        	if( ($admin = $this->getAdmin()) )
		        	{
		        		$_THE_debug .= 'Admin: '.(($admin->getIsAuthorized()) ? 'admin' : 'No').Util::br();
		        	}
	        	}else
        		{
	        		$_THE_debug .= '<ul style="margin: 0px; padding: 0px;">';

		        	$_THE_debug .= '<li style="display: inline-block; margin: 0px; padding: 0px; margin-right: 20px;" style="display: inline-block; margin: 0px; padding: 0px; margin-right: 15px;">'.'Execution time: '.$timeDone.' ('.$timeRPS.' RPS)'.'</li>';

		        	$_THE_debug .= '<li style="display: inline-block; margin: 0px; padding: 0px; margin-right: 20px;">'.'Memory usage: '.$memory.'</li>';

		        	if( ($user = $this->getUser()) && is_object($user) )
		        	{
		        		$_THE_debug .= '<li style="display: inline-block; margin: 0px; padding: 0px; margin-right: 20px;">';
		        		$_THE_debug .= 'User: '.(($user->getIsAuthorized()) ? 'user' : 'Guest');
		        		$_THE_debug .= '</li>';
		        	}

		        	if( ($admin = $this->getAdmin()) && is_object($admin) )
		        	{
		        		$_THE_debug .= '<li style="display: inline-block; margin: 0px; padding: 0px; margin-right: 20px;">';
		        		$_THE_debug .= 'Admin: '.(($admin->getIsAuthorized()) ? 'admin' : 'No');
		        		$_THE_debug .= '</li>';
		        	}

	        		$_THE_debug .= '</ul>';

	        		$timers = Util::data('TIMERS');

	        		foreach($timers as $timer)
	        		{
	        			list($key, $time) = $timer;
	        			$_THE_debug .= '<div>'.$key.' :: '.$time.'</div>';
	        		}
        		}

	        	$xID = $isAjax ? $randId : 'main';

	        	$blockType = 'block';

	        	if($buffer)
	        	{
	        		$_THE_debug .= Util::hr();
	        		$_THE_debug .= $isCLI ? '[OUTPUT:]'.Util::br() : '<h2 style="margin: 0px; padding: 0px; cursor: pointer; padding-bottom: 2px;" onclick="$(\'#output_block_'.$xID.'\').slideToggle();">OUTPUT:</h2>';
	        		if(!$isCLI)
	        		{
	        			$_THE_debug .= '<div id="output_block_'.$xID.'" style="display: '.$blockType.';">';
	        		}
	        		$_THE_debug .= $buffer;
	        		if(!$isCLI)
	        		{
	        			$_THE_debug .= '</div>';
	        		}
	        	}

	        	if($prNum)
		        {
	        		$_THE_debug .= Util::hr();
	        		$_THE_debug .= $isCLI ? 'PR ('.$prNum.'):'.Util::br() : '<h2 style="margin: 0px; padding: 0px; cursor: pointer; padding-bottom: 2px;" onclick="$(\'#pr_block_'.$xID.'\').slideToggle();">PR ('.$prNum.'):</h2>';
	        		if($isCLI)
	        		{
	        			ob_start();
		        		foreach($pr as $p)
		        		{
	        				var_dump($p);
	        			}
	        			$_THE_debug .= ob_get_clean();
	        		}else
	        		{
		        		$_THE_debug .= '<div id="pr_block_'.$xID.'" style="display: '.$blockType.';">';
		        		$_THE_debug .= '<ol>';
		        		ob_start();
		        		foreach($pr as $p)
		        		{
		        			print '<li>';
		        			Dumphper::dump($p);
		        			print '</li>';
		        		}
		        		$_THE_debug .= ob_get_clean();
		        		$_THE_debug .= '</ol>';
		        		$_THE_debug .= '</div>';
	        		}
	        	}

	        	if($errorsNum)
	        	{
	        		$_THE_debug .= Util::hr();
	        		$_THE_debug .= $isCLI ? 'Errors ('.$errorsNum.'):'.Util::br() : '<h2 style="margin: 0px; padding: 0px; cursor: pointer; padding-bottom: 2px;" onclick="$(\'#errors_block_'.$xID.'\').slideToggle();">Errors ('.$errorsNum.'):</h2>';
	        		if($isCLI)
	        		{
		        		foreach($errors as $error)
		        		{
		        			$errorType = strToUpper($error['group']);
		        			$_THE_debug .= '['.$errorType.'] '.$error['message'].($error['file'] ? ' in '.$error['file'].':'.$error['line'] : '').Util::br();
		        		}
	        		}else
	        		{
		        		$_THE_debug .= '<div id="errors_block_'.$xID.'" style="display: '.$blockType.';">';
		        		$_THE_debug .= '<ol>';
		        		foreach($errors as $error)
		        		{
		        			if($error['group'] == 'php')
		        			{
		        				$errorType = 'PHP ';
		        				switch($error['code'])
		        				{
		        					default:
		        						$errorType .= '#'.$error['code'];
		        					break;

		        					case E_ERROR:
		        						$errorType .= 'E_ERROR';
		        					break;

		        					case E_WARNING:
		        						$errorType .= 'E_WARNING';
		        					break;

		        					case E_PARSE:
		        						$errorType .= 'E_PARSE';
		        					break;

		        					case E_NOTICE:
		        						$errorType .= 'E_NOTICE';
		        					break;

		        					case E_STRICT:
		        						$errorType .= 'E_STRICT';
		        					break;

		        					case E_DEPRECATED:
		        						$errorType .= 'E_DEPRECATED';
		        					break;

		        					case E_RECOVERABLE_ERROR:
		        						$errorType .= 'E_RECOVERABLE_ERROR';
		        					break;
		        				}
		        			}else
		        			{
		        				$errorType = strToUpper($error['group']);
		        			}
		        			$_THE_debug .= '<li><b>['.$errorType.']</b> <tt>'.$error['message'].'</tt>'.($error['file'] ? '<br />'.$error['file'].':'.$error['line'] : '').'</li>';
		        		}
		        		$_THE_debug .= '</ol>';
		        		$_THE_debug .= '</div>';
		        	}
	        	}

	        	if($queriesNum)
	        	{
	        		$_THE_debug .= Util::hr();
	        		$_THE_debug .= $isCLI ? 'Queries ('.$queriesNum.') ['.round($dbTime, 4).' S]'.Util::br() : '<h2 style="margin: 0px; padding: 0px; cursor: pointer; padding-bottom: 2px;" onclick="$(\'#queries_block_'.$xID.'\').slideToggle();">Queries ('.$queriesNum.') ['.round($dbTime, 4).' S]:</h2>';
	        		if($isCLI)
	        		{
	        			foreach($queries as $query)
	        			{

	        			}
	        		}else
	        		{
		        		$_THE_debug .= '<div id="queries_block_'.$xID.'" style="display: '.$blockType.';">';
		        		$_THE_debug .= '<ol>';
	        			foreach($queries as $query)
	        			{
	        				$randId = uniqid().mt_rand(1000000, 9999999);
		        			$dump = '<table border="1" cellpadding="5" cellspacing="0" style="min-width: 300px; margin-top: 5px; margin-left: 5px;">';
		        			foreach($query['params'] as $paramKey => $paramValue)
		        			{
		        				$dump .= '<tr>';
		        				$dump .= '<td style="vertical-align: middle;">'.$paramKey.'</td>';
		        				$dump .= '<td style="vertical-align: middle;">'.(is_array($paramValue) ? '<pre>'.print_r($paramValue, true).'</pre>' : $paramValue).'</td>';
		        				$dump .= '</tr>';
		        			}
		        			$dump .= '</table>';
	        				$params = (sizeof($query['params']) > 0 ? $dump.'<p><a href="javascript:void(0);" onclick="$(\'#query_'.$randId.'\').hide(); $(\'#real_query_'.$randId.'\').fadeIn();">Apply this params to query</a></p>' : '');
	        				$real_query = $this->getDBRealQuery($query['sql'], $query['params']).';';
        					$rowsInfo = '<span>'.($query['rows'] ? $query['rows'].' row(s) at' : 'done at').' '.round($query['time_execute'], 4).'</span>';
	        				$real_query = '<p style="display: block;" id="real_query_'.$randId.'"><tt>'.$real_query.'</tt>'.($params ? '<br /><a href="javascript:void(0);" onclick="$(\'#real_query_'.$randId.'\').hide(); $(\'#query_'.$randId.'\').fadeIn();">Show prepared statement</a>' : '').'<br />'.$rowsInfo.'</p>';
	        				if($query['success'])
	        				{
	        					$_THE_debug .= '<li>'.$real_query.'<span id="query_'.$randId.'" style="display: none;"><p><tt>'.$query['sql'].'</tt></p>'.$params.'<p>'.$rowsInfo.'</p>'.'</span></li>';
	        				}else
	        				{
	        					$_THE_debug .= '<li>'.$real_query.'<span id="query_'.$randId.'"><p><tt style="color: red;">'.$query['sql'].'</tt><p style="color: maroon;">Error #'.$query['error']['id'].': '.$query['error']['message'].'</p></p>'.$params.'</span></li>';
	        				}
	        			}
		        		$_THE_debug .= '</ol>';
		        		$_THE_debug .= '</div>';
		        	}
	        	}

	        	if($templatesNum && !$isCLI)
	        	{
	        		$_THE_debug .= Util::hr();
	        		$_THE_debug .= '<h2 style="margin: 0px; padding: 0px; cursor: pointer; padding-bottom: 2px;" onclick="$(\'#templates_block_'.$xID.'\').slideToggle();">Templates ('.$templatesNum.'):</h2>';
	        		$_THE_debug .= '<div id="templates_block_'.$xID.'" style="display: block;">';
	        		$_THE_debug .= '<ol>';
	        		foreach($templates as $template)
	        		{
	        			$_THE_debug .= '<li><span style="color: green;">'.$template['base_name'].'</span> :: '.$template['file_name'].'</li>';
	        		}
	        		$_THE_debug .= '</ol>';
	        		$_THE_debug .= '</div>';
	        	}

	        	if($includesNum && !$isCLI)
	        	{
	        		$_THE_debug .= Util::hr();
	        		$_THE_debug .= '<h2 style="margin: 0px; padding: 0px; cursor: pointer; padding-bottom: 2px;" onclick="$(\'#includes_block_'.$xID.'\').slideToggle();">Includes ('.$includesNum.'):</h2>';
	        		$_THE_debug .= '<div id="includes_block_'.$xID.'" style="display: none;">';
	        		$_THE_debug .= '<ol>';
	        		foreach($includes as $include)
	        		{
	        			$_THE_debug .= '<li>'.$include.'</li>';
	        		}
	        		$_THE_debug .= '</ol>';
	        		$_THE_debug .= '</div>';
	        	}

	        	if(!$isCLI)
	        	{
	        		$_THE_debug .= '</div>';
	        		$_THE_debug .= '</div>';
	        		$_THE_debug .= '</div>';
	        		$_THE_debug .= '</body>';
	        		$_THE_debug .= '</html>';
	        	}else
	        	{
	        		$_THE_debug .= Util::br();
	        	}
	        }

	        if($isAjax)
	        {
	        	if($isDebug)
	        	{
	        		$outputData['_THE_Debug'] = $_THE_debug;
	        	}

	        	echo json_encode($outputData);
	        }else
	        {
	        	foreach($this->headers as $name => $value)
	        	{
	        		header($name.($value ? ': '.$value : ''));
	        	}

	        	echo $outputData;
	        	if($isDebug)
	        	{
	        		echo $_THE_debug;
	        	}
	        }
	    }

		/**
		 * @param null $dbName
		 * @return \The\Core\DB
		 *
		 */
		public function getDB($dbName = null)
		{
			/**
			* @TODO: default value from config
			*/
			$dbName = Util::ifNull($dbName, 'default');

			if(!isset($this->db[$dbName]))
			{
				$db = $this->getConfig()->get('db.'.$dbName);

				$link = Util::arrayGet($db, 'link');
				$userName = Util::arrayGet($db, 'username');
				$password = Util::arrayGet($db, 'password');
				$encoding = Util::arrayGet($db, 'encoding');

				$obj = new DB($link, $userName, $password, $encoding);
				$obj->setApplication($this);
				$this->db[$dbName] = $obj;
			}

			return $this->db[$dbName];
		}

		/**
		 * @param null $dbName
		 * @return \The\Core\DB
		 *
		 */
		public function getMasterDB($dbName = null)
		{
			/**
			* @TODO: default value from config
			*/
			$dbName = Util::ifNull($dbName, 'default');

			if(!isset($this->db[$dbName]))
			{
				$db = $this->getConfig()->get('db.'.$dbName);

				$link = Util::arrayGet($db, 'link');
				$userName = Util::arrayGet($db, 'username');
				$password = Util::arrayGet($db, 'password');
				$encoding = Util::arrayGet($db, 'encoding');

				$obj = new DB($link, $userName, $password, $encoding);
				$obj->setApplication($this);
				$this->db[$dbName] = $obj;
			}

			return $this->db[$dbName];
		}

		/**
		 * @param null $dbName
		 * @return \The\Core\DB
		 *
		 */
		public function getSlaveDB($dbName = null)
		{
			/**
			* @TODO: default value from config
			*/
			$dbName = Util::ifNull($dbName, 'default');

			if(!isset($this->db[$dbName]))
			{
				$db = $this->getConfig()->get('db.'.$dbName);

				$link = Util::arrayGet($db, 'link');
				$userName = Util::arrayGet($db, 'username');
				$password = Util::arrayGet($db, 'password');
				$encoding = Util::arrayGet($db, 'encoding');

				$obj = new DB($link, $userName, $password, $encoding);
				$obj->setApplication($this);
				$this->db[$dbName] = $obj;
			}

			return $this->db[$dbName];
		}


		public function getService($serviceName)
		{
			if(!isset($this->service[$serviceName]))
			{
				$service = $this->getConfig()->get('services.'.$serviceName);

				$className = Util::arrayGet($service, 'class_name');
				$params = Util::arrayGet($service, 'params', array());

				/**
				@TODO create with args
				*/
				$obj = new $className();
				if(method_exists($obj, 'setApplication'))
				{
					$obj->setApplication($this);
				}

				$this->service[$serviceName] = $obj;
			}
			return $this->service[$serviceName];
		}

		public function getStorage($storageName = null)
		{
			/**
			* @TODO: default value from config
			*/
			$storageName = Util::ifNull($storageName, 'Apc');

			if(!isset($this->storage[$storageName]))
			{
				$className = $this->getClassName($storageName, 'Storage');
				$obj = new $className(); #'/dev/shm/'
				$obj->setApplication($this);
				$this->storage[$storageName] = $obj;
			}

			return $this->storage[$storageName];
		}

		public function getMailSender($mailSenderName = null)
		{
			/**
			 * @TODO: default value from config
			 */
			$mailSenderName = Util::ifNull($mailSenderName, 'Default');

			if(!isset($this->storage[$mailSenderName]))
			{
				$className = $this->getClassName($mailSenderName, 'MailSender');
				$obj = new $className(); #'/dev/shm/'
				$obj->setApplication($this);
				$this->mailSender[$mailSenderName] = $obj;
			}

			return $this->mailSender[$mailSenderName];
		}

		public function getFileReader($fileReader = null)
		{
			/**
			* @TODO: default value from config
			*/
			$fileReader = Util::ifNull($fileReader, 'IO');

			if(!isset($this->fileReader[$fileReader]))
			{
				$className = $this->getClassName($fileReader, 'FileReader');
				$obj = new $className();
				$obj->setApplication($this);
				$this->fileReader[$fileReader] = $obj;
			}

			return $this->fileReader[$fileReader];
		}

		public function getFileUploader($fileUploader = null)
		{
			/**
			* @TODO: default value from config
			*/
			$fileUploader = Util::ifNull($fileUploader, 'Default');

			if(!isset($this->fileUploader[$fileUploader]))
			{
				$className = $this->getClassName($fileUploader, 'FileUploader');
				$obj = new $className();
				$obj->setApplication($this);
				$this->fileUploader[$fileUploader] = $obj;
			}

			return $this->fileUploader[$fileUploader];
		}

		public function getTemplating($templatingName = null)
		{
			/**
			* @TODO: default value from config
			*/
			$templatingName = Util::ifNull($templatingName, 'default');

			if(!isset($this->templating[$templatingName]))
			{
				$className = $this->getClassName($templatingName, 'Templating');
				$obj = new $className();
				$obj->setApplication($this);
				$this->templating[$templatingName] = $obj;
			}

			return $this->templating[$templatingName];
		}

		/**
		 * @param $repositoryName
		 * @return \The\Core\Repository
		 */
		public function getRepository($repositoryName)
		{
			if(!isset($this->repository[$repositoryName]))
			{
				$className = $this->getClassName($repositoryName, 'Repository');
				$obj = new $className();
				$obj->setApplication($this);
				$this->repository[$repositoryName] = $obj;
			}

			return $this->repository[$repositoryName];
		}

		public function createModel($modelName, $db = null)
		{
			$className = $this->getClassName($modelName, 'Model');

			if($db === null)
			{
				$db = $this->getDB();
			}

			$obj = $className::newInstance($className, $db, $this);
			return $obj;
		}

		public function getUserACL($userType)
		{
			$aclConfig = $this->getConfig()->get('acl.types.'.$userType);

			return $this->getACL( Util::arrayGet($aclConfig, 'provider') );
		}

		public function getACL($aclName = null)
		{
			/**
			* @TODO: default value from config
			*/
			$aclName = Util::ifNull($aclName, 'default');
			$aclConfig = $this->getConfig()->get('acl.providers.'.$aclName);
			if(!$aclConfig)
			{
				throw new ClassException('ACL "'.$aclName.'" not found.');
				return false;
			}
			$aclName = Util::arrayGet($aclConfig, 'class', $aclName);
			$settings = Util::arrayGet($aclConfig, 'settings');

			if(!isset($this->acl[$aclName]))
			{
				$className = $this->getClassName($aclName, 'ACL');
				$obj = new $className();
				$obj->setSettings($settings);
				$obj->setApplication($this);
				$this->acl[$aclName] = $obj;
			}

			return $this->acl[$aclName];
		}

		/**
		 * @param string|null $userType
		 * @return \The\Core\User
		 * @throws \Exception
		 */
		public function getUser($userType = null)
		{
			$userType = Util::ifNull($userType, 'user');

			$type = $this->getConfig()->get('acl.types.'.$userType);

			if(!$type)
			{
				#throw new ClassException('User type "'.$userType.'" not found.');
				return false;
			}


			$aclProvider = Util::arrayGet($type, 'provider');

			if(!isset($this->users[$userType]))
			{
				$this->users[$userType] = $this->getACL($aclProvider)->getUser($userType, $type);
			}

			return $this->users[$userType];
		}

		/**
		 * @return User
		 */
		public function getAdmin()
		{
			return $this->getUser('admin');
		}

		protected function setRouteName($routeName)
		{
			$this->routeName = $routeName;

			return $this;
		}

		public function getRouteName()
		{
			return $this->routeName;
		}

		protected function setRoute($route)
		{
			$this->route = $route;

			return $this;
		}

		public function getRoute()
		{
			return $this->route;
		}

		protected function setOutput($output)
		{
			$this->output = $output;

			return $this;
		}

		protected function getOutput()
		{
			return $this->output;
		}

		protected function setController($controller)
		{
			$this->controller = $controller;

			return $this;
		}

		public function getController()
		{
			return $this->controller;
		}

		protected function setApplicationName($applicationName)
		{
			$this->applicationName = $applicationName;

			return $this;
		}

		public function getApplicationName()
		{
			return  $this->applicationName;
		}

		public function setIsDebug($isDebug)
		{
			$this->isDebug = $isDebug;

			return $this;
		}

		public function getIsDebug()
		{
			return  $this->isDebug;
		}

		protected function setEnv($env)
		{
			$this->env = $env;

			return $this;
		}

		public function getEnv()
		{
			return  $this->env;
		}

		protected function setRootDir($rootDir)
		{
			$this->rootDir = $rootDir;

			return $this;
		}

		public function getRootDir()
		{
			return  $this->rootDir;
		}

		protected function setConfig($config)
		{
			$this->config = $config;

			return $this;
		}

		public function getConfig()
		{
			return $this->config;
		}

		protected function setRequest($request)
		{
			$this->request = $request;

			return $this;
		}

		public function getRequest()
		{
			return $this->request;
		}

		protected function setFlash($flash)
		{
			$this->flash = $flash;

			return $this;
		}

		public function getFlash()
		{
			return $this->flash;
		}

		public function setRegistry($key, $value)
		{
			$this->registry[$key] = $value;

			return $this;
		}

		public function getRegistry($key, $default = null)
		{
			return Util::arrayGet($this->registry, $key, $default);
		}

		public function getAppTemplateDir()
		{
			return $this->getApplicationDir('View');
		}

		public function getCoreTemplateDir()
		{
			return $this->getDir('Core/View');
		}

		public function getTemplateDirs()
		{
			$replaces = array(
				'@App' => $this->getAppTemplateDir(),
				'@Core' => $this->getCoreTemplateDir(),
			);

			return $replaces;
		}

		public function getTemplateCacheKey()
		{
			return 'template_file_name';
		}

		public function getTemplateFileName($templateFileName, $type = null)
		{
			$st = $this->getStorage();

			$storageKey = $templateFileName;

			if( ($fileName = $st->get($this->getTemplateCacheKey(), $storageKey)) )
			{
				return $fileName;
			}

			$removeDoubleSlashes = function($s)
			{
				$s = str_replace('\\', '/', $s);
				while(substr_count($s, '//'))
				{
					$s = str_replace('//', '/', $s);
				}
				return $s;
			};

			if($templateFileName[0] == '@')
			{
				$replaces = $this->getTemplateDirs();
				$app = $this;
				if($templateFileName[1] == ':')
				{
					$tf = substr($templateFileName, 2);
					foreach($replaces as $namespace)
					{
						$tfx = $namespace.':'.$tf;
						$fl = $this->getTemplateFileName($tfx, $type);
						if($this->getFileReader()->exists($fl))
						{
							return $removeDoubleSlashes( $fl );
						}
					}
				}else
				{
					$templateFileName = preg_replace_callback('/\@App\#(\w+)/i', function($m) use ($app)
					{
						return $app->getApplicationDir('View', $m[1]);
					}, $templateFileName);
					$templateFileName = str_replace(array_keys($replaces), array_values($replaces), $templateFileName);
				}
			}
			$templateFileName = str_replace(':', '/', $templateFileName).'.html.twig';

			$templateFileName = $removeDoubleSlashes( $templateFileName );

			$st->set($this->getTemplateCacheKey(), $storageKey, $templateFileName);

			return $templateFileName;
		}

		public function setIsAjaxResponse($isAjaxResponse)
		{
			$this->isAjaxResponse = $isAjaxResponse;

			return $this;
		}

		public function getIsAjaxResponse()
		{
			return $this->isAjaxResponse;
		}

		public function setInterruptRender($interruptRender)
		{
			$this->interruptRender = $interruptRender;
		}

		public function getInterruptRender()
		{
			return $this->interruptRender;
		}

		/**
		 * @param boolean $isJsonEncodeDisabled
		 */
		public function setIsJsonEncodeDisabled($isJsonEncodeDisabled)
		{
			$this->isJsonEncodeDisabled = $isJsonEncodeDisabled;
		}

		/**
		 * @return boolean
		 */
		public function getIsJsonEncodeDisabled()
		{
			return $this->isJsonEncodeDisabled;
		}
	}