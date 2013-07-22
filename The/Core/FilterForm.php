<?php
	namespace The\Core;

	use The\Core\DynamicForm;

	class FilterForm extends DynamicForm
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setName('Filters')
				->setAction(null)
				->setMethod('GET')
				->setTemplatingFileName('@:Form:filter')
			;
		}
	
	}