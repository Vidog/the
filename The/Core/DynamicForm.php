<?php
	namespace The\Core;

	use The\Core\Form;
	use The\Core\FieldGroup;
	use The\Core\Field;

	class DynamicForm extends Form
	{
		public function addFieldGroup($name = null, $caption = null)
		{
			return parent::addFieldGroup($name, $caption);
		}

		public function addField(Field $field, FieldGroup $fieldGroup = null)
		{
			return parent::addField($field, $fieldGroup);
		}
	}