<?php
	namespace The\Core;

	use The\Core\Paginator;
	use The\Core\Util;

	class SimplePaginator extends Paginator
	{
		public function __construct()
		{
			$this->setApplication( Util::getApplication() );

			$this
				->setTemplatingFileName('@:Paginator:default')
				->setTagName('ul')
			;
		}

		public function execute()
		{
            
		}
	}