<?php
	namespace The\Core\Field;

	use The\Core\Field;

	class HiddenField extends Field
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setAttribute('type', 'hidden')
				->setTemplatingFileName('@:Field:hidden')
			;
		}
	
	}