<?php
	namespace The\Core\Field;

	use The\Core\Field\RepositorySelectField;

	class RadioSelectField extends RepositorySelectField
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setTagName('radio')
				->setWithoutCloseTag(false)
				->setTemplatingFileName('@:Field:radio')
				->setIsWithoutCaption(true);
		}
	}