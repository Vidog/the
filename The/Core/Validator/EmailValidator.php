<?php
	namespace The\Core\Validator;

	use The\Core\Validator;

	class EmailValidator extends Validator
	{
		public function __construct()
		{
			parent::__construct('(.*)\@(.*)\.(.*)');
		}
	}