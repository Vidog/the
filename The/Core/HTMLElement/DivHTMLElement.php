<?php
	namespace The\Core\HTMLElement;

	use The\Core\HTMLElement;

	class DivHTMLElement extends HTMLElement
	{
		public function __construct()
		{
			parent::__construct();

			$this
				->setTagName('div')
			;
		}
	}