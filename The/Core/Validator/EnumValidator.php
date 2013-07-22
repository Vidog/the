<?php
	namespace The\Core\Validator;

	use The\Core\Validator;

	class EnumValidator extends Validator
	{
		protected $values;

		public function __construct($values)
		{
			parent::__construct();

			$this
				->setValues($values)
			;
		}

		public function makeValidation($value)
		{
			$values = $this->getValues();
			
			return in_array($value, $values);
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
	}