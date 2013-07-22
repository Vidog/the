<?php
	namespace The\Core\Validator;

	use The\Core\Validator\RangeValidator;

	class LengthValidator extends RangeValidator
	{
		public function __construct($minLength = null, $maxLength = null)
		{
			parent::__construct($minLength, $maxLength);
		}

		public function validate($value)
		{
			return parent::validate( strlen($value) );
		}
	}