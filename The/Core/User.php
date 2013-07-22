<?php
	namespace The\Core;

	use The\Core\Implant\ACLImplant;
	use The\Core\Model;

	class User extends Model
	{
		use ACLImplant;

		protected $isAuthorized;
		protected $loginRoute;
		protected $loginRouteParams = array();

		public function hasRole($role)
		{

		}

		public function isGranted($grant)
		{

		}

		public function init()
		{
			
		}

		public function setParams($params)
		{
			return $this->loadFromArray($params);
		}
		
		public function getParams()
		{
			return $this->getFieldsData();
		}

		public function setLoginRoute($loginRoute)
		{
			$this->loginRoute = $loginRoute;
		
			return $this;
		}
		
		public function getLoginRoute()
		{
			return $this->loginRoute;
		}

		public function setLoginRouteParams($loginRouteParams)
		{
			$this->loginRouteParams = $loginRouteParams;
		
			return $this;
		}
		
		public function getLoginRouteParams()
		{
			return $this->loginRouteParams;
		}

		public function setIsAuthorized($isAuthorized)
		{
			$this->isAuthorized = $isAuthorized;

			return $this;
		}

		public function getIsAuthorized()
		{
			return $this->isAuthorized;
		}
	}