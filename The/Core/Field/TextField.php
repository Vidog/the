<?php
	namespace The\Core\Field;

	use The\Core\Field;

	class TextField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setTagName('textarea')
				->setWithoutCloseTag(false)
				->setTemplatingFileName('@:Field:text');
		}
	}