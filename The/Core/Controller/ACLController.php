<?php
	namespace The\Core\Controller;

	use The\Core\Controller;
	use The\Core\Request;
	use The\Core\Util;
	use The\Core\Form\ACLLoginForm;

	class ACLController extends Controller
	{
		public function adminLoginAction()
		{
			$request = $this->getApplication()->getRequest();

			$authForm = new ACLLoginForm();
			if($authForm->isSubmitted() && $authForm->isValid())
			{
				$login = $authForm->getField('_login')->getValue();
				$password = $authForm->getField('_password')->getValue();
				$rememberMe = $authForm->getField('_remember_me')->getValue();
			}

			return $this->render('@:Controller:ACL:login', array('authForm' => $authForm));
		}

		public function adminLogoutAction()
		{
			$this->getApplication()->getUserACL('admin')->logoutUser();

			$this->getApplication()->redirect('_index');
		}

		public function loginAction($backurl)
		{
			$this->getApplication()->redirectUrl('/admin/login.php?url=' . urlencode($backurl));
		}

		public function logoutAction()
		{
			
		}
	}