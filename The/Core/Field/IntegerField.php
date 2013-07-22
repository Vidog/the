<?php
	namespace The\Core\Field;

	use The\Core\Field;

	class IntegerField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this->setAttribute('type', 'number');
		}
	}