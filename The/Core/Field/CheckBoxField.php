<?php
	namespace The\Core\Field;

	use The\Core\Field;
	use The\Core\Util;

	class CheckBoxField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setAttribute('type', 'checkbox')
				->setIsWithoutCaption(true)
				->setIsInnerCaption(true)
				#->setTemplatingFileName('@:Field:checkbox')
			;
		}

		public function getValue()
		{
			return parent::getValue() == 'yes';
		}

		public function onToString()
		{
			if($this->getValue())
			{
				$this->setAttribute('checked', 'checked');
			}

			$this->setValue('yes');

			return parent::onToString();
		}
	}