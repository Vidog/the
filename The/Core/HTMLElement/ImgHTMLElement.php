<?php
	namespace The\Core\HTMLElement;

	use The\Core\HTMLElement;

	class ImgHTMLElement extends HTMLElement
	{
		public function __construct()
		{
			parent::__construct();

			$this
				->setTagName('img')
				->setWithoutCloseTag(true)
			;
		}
	}