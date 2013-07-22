<?php
	namespace The\Core\Field;

	use The\Core\Field\RepositorySelectField;
	use The\Core\Util;

	class BooleanField extends RepositorySelectField
	{
		public function init()
		{
			$this->setRepository('Boolean');
			$this->setMethod('getValues');
			$this->setFields('getValues');

			return parent::init();
		}
	}
/*
	namespace The\Core\Field;

	use The\Core\Field;

	class BooleanField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setIsWithoutCaption(true)
				->setIsInnerCaption(true)
				#->setTemplatingFileName('@Core:Field:select')
				->setAttribute('type', 'checkbox')
			;
		}

		public function getValue()
		{
			$val = parent::getValue();

			var_dump($val);

			if($val != '1')
			{
				$val = '0';
			}

			return $val;
		}

		public function onToString()
		{
			$res = parent::onToString();
			
			$val = $this->getValue();

			if($val == '1')
			{
				$this->setAttribute('checked', 'checked');
			}

			$this->setAttribute('value', '1');

			return $res;
		}
	}

	<?php*/