<?php
	namespace The\Core;

	use The\Core\Table;
	use The\Core\Field;

	class DynamicTable extends Table
	{
		public function addField(Field $field)
		{
			return parent::addField($field);
		}
		
		public function addSortingField($sortingField)
		{
			return parent::addSortingField($sortingField);
		}

		public function setName($name)
		{
			return parent::setName($name);
		}
	}