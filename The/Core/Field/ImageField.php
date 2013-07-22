<?php
	namespace The\Core\Field;

	use The\Core\Field;
	use The\Core\HTMLElement\ImgHTMLElement;

	class ImageField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setTagName('img')
				->setWithoutCloseTag(true)
				->setTemplatingFileName('@:Field:image')
				->setIsDisplayValueRaw(true)
			;
		}

		public function getDisplayValue()
		{
			$img = new ImgHTMLElement();
			$img->setAttribute('src', parent::getDisplayValue());
			$img->setAttribute('width', 100);

			return (string)$img;
		}
	}