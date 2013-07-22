<?php
	namespace The\Core;

	use The\Core\Util;
	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\SettingsImplant;
	use The\Core\User;

	class ACL
	{
		use ApplicationImplant;
		use SettingsImplant;

		public function getUser($userType, $settings)
		{
			return $this->createUser(false, array(), $userType, $settings);
		}

		public function createUser($isAuthorized, $data, $userType, $settings)
		{
			$config = $this->getApplication()->getConfig();

			$provider = $config->get('acl.providers.'.$settings['provider']);

			if($isAuthorized)
			{
				$userSettings = $config->get('acl.users.'.Util::arrayGet($provider, 'user'));
			}else
			{
				$userSettings = $config->get('acl.users.'.Util::arrayGet($provider, 'guest_user', 'guest'));
			}

			$loginRoute = Util::arrayGet($settings, 'login_route');
			$loginRouteParams = Util::arrayGet($settings, 'login_route_params', array());
			
			$userModel = $this->getApplication()->createModel($userSettings['class']);
			$userModel->setACL($this);
			$userModel->setIsAuthorized($isAuthorized);
			$userModel->setLoginRoute($loginRoute);
			$userModel->setLoginRouteParams($loginRouteParams);
			if(is_object($data))
			{
				$userModel->loadFromModel($data);
			}else
			{
				$userModel->loadFromArray($data);
			}
			#$userModel->setParams($data);
			$userModel->init();

			return $userModel;
		}

		public function loginUser(User $user)
		{

		}

		public function logoutUser()
		{

		}

		public function makeAuth(User $user)
		{
			$loginRoute = $user->getLoginRoute();
			$loginRouteParams = $user->getLoginRouteParams();
			$loginRouteParams = array_merge($loginRouteParams, array('backurl' => urlencode($_SERVER['REQUEST_URI'])));
			return $this->getApplication()->redirect($loginRoute, $loginRouteParams);
		}
	}