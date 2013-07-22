<?php
	namespace The\Core;

	use The\Core\CLI;
	use The\Core\Implant\ApplicationImplant;

	class Command
	{
		use ApplicationImplant;

		protected $args = array();

		public function __construct()
		{
			/**
			@TODO kostyle
			*/
			$this->setApplication( Util::getApplication() );
		}

		protected function setArgument($name, $value)
		{
			if(is_numeric($name))
			{
				$name += 2;
			}
			$this->args[$name] = $value;

			return $this;
		}

		# -c or -color # --color or --color=red
		protected function getArgument($name, $defaultValue = null)
		{
			if(is_numeric($name))
			{
				$name += 2;
			}
			if(isset($this->args[$name]))
			{
				return $this->args[$name];
			}
			return CLI::getArgument($name, $defaultValue);
		}

		public function run()
		{

		}
	}