<?php
	namespace The\Core\Field;

	use The\Core\Field;

	class FloatField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this->setAttribute('type', 'number');
		}
	}