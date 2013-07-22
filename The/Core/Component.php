<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\HTMLElementImplant;
	use The\Core\Implant\TemplatingImplant;
	use The\Core\Implant\ItemsImplant;

	class Component
	{
		use ApplicationImplant;
		use HTMLElementImplant;
		use TemplatingImplant;
		use ItemsImplant;
		
		public function __construct()
		{
			/**
			@TODO kostyle
			*/
			$this->setApplication( Util::getApplication() );

			$this
				->setTemplatingFileName('@:Component:default')
			;
		}

		public function init()
		{
			$this->addTemplatingVariable('component', $this);
		}

		public function load(array $data)
		{
			return $this->setItems($data);
		}
	}