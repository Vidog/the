<?php
	namespace The\Core;
	
	use The\Core\Exception\ValidatorException;
	use The\Core\Implant\ApplicationImplant;

	class Validator
	{
		use ApplicationImplant;

		protected $errorMessage;

		public function __construct()
		{
			$this->setErrorMessage('Поле заполнено неверно');
		}

		protected function makeValidation($value)
		{
			return true;
		}

		public function validate($value)
		{
			$res = $this->makeValidation($value);

			if(!$res)
			{
				throw new ValidatorException( $this->getErrorMessage() );
			}

			return $res;
		}

		public function setErrorMessage($errorMessage)
		{
			$this->errorMessage = $errorMessage;

			return $this;
		}

		public function getErrorMessage()
		{
			return $this->errorMessage;
		}
	}