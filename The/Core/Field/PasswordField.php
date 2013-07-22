<?php
	namespace The\Core\Field;

	use The\Core\Query;

	class PasswordField extends StringField
	{
		public function onToString()
		{
			parent::onToString();

			$this->setAttribute('type', 'password');
		}
	}