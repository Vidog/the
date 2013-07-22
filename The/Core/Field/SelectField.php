<?php
	namespace The\Core\Field;

	use The\Core\Util;
	use The\Core\Field;

	abstract class SelectField extends Field
	{
		protected $values = array();
		
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setTagName('select')
				->setWithoutCloseTag(false)
				->setTemplatingFileName('@:Field:select')
			;
		}

		public function validate()
		{
			$this->init();

			$val = $this->getValue();
			if(!is_scalar($val))
			{
				return false;
			}

			$values = $this->getValues();

			if($values)
			{
				if(!isset($values[$val]))
				{
					return false;
				}
			}

			return parent::validate();
		}

		public function setValues($values)
		{
			$this->values = $values;

			return $this;
		}

		public function getValues()
		{
			return $this->values;
		}

		public function getValue()
		{
			$values = $this->getValues();

			if(parent::getValue() === null && sizeof($values) > 0)
			{
				reset( $values );
				$value = key($values);
				$this->setValue( $value );
			}

			return parent::getValue();
		}

		public function getValueTitle($value = null)
		{
			return Util::arrayGet($this->getValues(), $value === null ? $this->getValue() : $value);
		}

		public function getDisplayValue()
		{
			return $this->getValueTitle();
		}

		public function getAutocomplete()
		{
			return false;
		}
	}