<?php
	namespace The\Core\Implant;

	trait ApplicationImplant
	{
		/**
		 * @var \The\Core\Application
		 */
		protected $application;

		public function setApplication($application)
		{
			$this->application = $application;

			return $this;
		}

		public function getApplication()
		{
			return $this->application;
		}
	}