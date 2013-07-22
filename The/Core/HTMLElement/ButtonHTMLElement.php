<?php
	namespace The\Core\HTMLElement;

	use The\Core\HTMLElement;

	class ButtonHTMLElement extends HTMLElement
	{
		public function __construct()
		{
			parent::__construct();

			$this
				->setTagName('button')
			;
		}
	}