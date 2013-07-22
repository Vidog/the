<?php
	namespace The\Core\Field;

	use The\Core\DateTime;
	use The\Core\Field;

	class DateTimeField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this->setAttribute('type', 'date');
		}

		public function format($format)
		{
			$datetime = new DateTime($this->getValue());

			return $datetime ? $datetime->format($format) : null;
		}
	}