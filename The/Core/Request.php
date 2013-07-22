<?php
	namespace The\Core;

	use The\Core\Util;

	class Request
	{
		const TYPE_STRING = 'string';
		const TYPE_INTEGER = 'integer';
		const TYPE_FLOAT = 'float';
		const TYPE_DOUBLE = 'double';
		const TYPE_REAL = 'real';
		const TYPE_BOOLEAN = 'boolean';
		const TYPE_ARRAY = 'array';
		const TYPE_OBJECT = 'object';

		use Implant\ApplicationImplant;

	    protected $sessionStarted = false;

	    protected $params = array();

	    public function makeType($var, $type)
	    {
	    	switch($type)
	    	{
	    		case self::TYPE_STRING:
	    			return (string)$var;
	    		break;

	    		case self::TYPE_INTEGER:
	    			return (int)$var;
	    		break;

	    		case self::TYPE_FLOAT:
	    			return (float)$var;
	    		break;

	    		case self::TYPE_DOUBLE:
	    			return (double)$var;
	    		break;

	    		case self::TYPE_BOOLEAN:
	    			return (bool)$var;
	    		break;

	    		case self::TYPE_ARRAY:
	    			return (array)$var;
	    		break;

	    		case self::TYPE_OBJECT:
	    			return (object)$var;
	    		break;
	    	}
	    	return $var;
	    }

	    public function getIp()
	    {
	    	return $this->server('REMOTE_ADDR');
	    }

	    public function setParams($params)
	    {
	    	$this->params = $params;

	    	return $this;
	    }

	    public function getParams()
	    {
	    	return $this->params;
	    }

	    public function setParam($paramName, $value)
	    {
	    	$this->params[$paramName] = $value;

	    	return $this;
	    }

	    public function getParam($paramName, $default = null)
	    {
	    	return Util::arrayGet($this->params, $paramName, $default);
	    }

	    /**
	     * $_REQUEST
	     */
	    public function request($key = null, $default = null, $type = null)
	    {
	        return $key === null ? $_REQUEST : $this->makeType( Util::arrayGet($_REQUEST, $key, $default), $type);
	    }

		public function setRequest($key, $value)
		{
			$_REQUEST[$key] = $value;

			return $this;
		}

	    /**
	     * $_GET
	     */
	    public function get($key = null, $default = null, $type = null)
	    {
	        return $key === null ? $_GET : $this->makeType( Util::arrayGet($_GET, $key, $default), $type);
	    }

	    /**
	     * $_POST
	     */
	    public function post($key = null, $default = null, $type = null)
	    {
	        return $key === null ? $_POST : $this->makeType( Util::arrayGet($_POST, $key, $default), $type);
	    }

	    public function hasPost()
	    {
	    	return sizeof($_POST) > 0;
	    }

	    protected function initSession()
	    {
	        if(session_id())
	        {
	            return true;
	        }else
	        {
	            return ( $this->sessionStarted = session_start() );
	        }
	    }

	    /**
	     * $_SESSION
	     */
	    public function session($key, $default = null, $type = null)
	    {
	        $this->initSession();
	        return $this->makeType( Util::arrayGet($_SESSION, $key, $default), $type);
	    }

		public function setSession($key, $value)
		{
			$this->initSession();
			$_SESSION[$key] = $value;
			return $this;
		}

		public function removeSession($key)
		{
			$this->initSession();
			$_SESSION[$key] = null;
			unset($_SESSION);

			return $this;
		}

	    /**
	     * $_COOKIE
	     */
	    public function cookie($key, $default = null, $type = null)
	    {
	        return $this->makeType( Util::arrayGet($_COOKIE, $key, $default), $type);
	    }

	    public function setCookie($key, $value, $ttl = null, $path = null, $domain = null)
	    {
	    	if($ttl === null)
	    	{
	    		$ttl = time() + 365 * 24 * 60 * 60;
	    	}

	    	if($path === null)
	    	{
	    		$path = '/';
	    	}

	    	$params = array(
	    		$key,
	    		$value,
	    		$ttl,
	    		$path,
	    	);

	    	if($domain !== null)
	    	{
	    		$params[] = $domain;
	    	}

	    	call_user_func_array('setcookie', $params);

			return $this;
	    }

	    public function removeCookie($key, $path = null, $domain = null)
	    {
	    	return $this->setCookie($key, '', time() - 3600, $path, $domain);
	    }

	    /**
	     * $_SERVER
	     */
	    public function server($key, $default = null, $type = null)
	    {
	        return $this->makeType( Util::arrayGet($_SERVER, $key, $default), $type);
	    }
	}