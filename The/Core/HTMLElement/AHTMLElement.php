<?php
	namespace The\Core\HTMLElement;

	use The\Core\HTMLElement;

	class AHTMLElement extends HTMLElement
	{
		public function __construct()
		{
			parent::__construct();

			$this
				->setTagName('a')
			;
		}
	}