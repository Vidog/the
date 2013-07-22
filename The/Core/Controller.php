<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;

	class Controller
	{
		use ApplicationImplant;
		
		protected $pageTitle;

		public function __construct()
		{
			$this->beforeConstruct();
			$this->afterConstruct();
		}

		protected function beforeConstruct()
		{
			
		}

		protected function afterConstruct()
		{

		}
		
		protected function setPageTitle($pageTitle)
		{
			$this->pageTitle = $pageTitle;

			return $this;
		}

		public function getPageTitle()
		{
			return $this->pageTitle;
		}

		public function render($templateFileName, array $variables = array())
		{
			return $this->getApplication()->render($templateFileName, $variables);
		}
	}