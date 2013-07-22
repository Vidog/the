<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\HTMLElementImplant;
	use The\Core\Implant\TemplatingImplant;
	use The\Core\Implant\ItemsImplant;

	class HTMLElement
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
				->setTemplatingFileName('@:HTMLElement:default')
				->setTagName('div')
			;

			$this->addTemplatingVariable('element', $this);
		}
	}