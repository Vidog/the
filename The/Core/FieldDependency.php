<?php
	namespace The\Core;

	use The\Core\Form;
	use The\Core\Field;
	use The\Core\Util;

	class FieldDependency
	{
		protected $fieldName;

		public function __construct($fieldName)
		{
			$this->setFieldName($fieldName);
		}

		protected function getDependencyField(Form $form, Field $field)
		{
			$dependencyField = null;
			if (preg_match('/^([^\.]+)\.(.*)$/', $this->getFieldName(), $match)){
				$dependencyField = $form->getField($match[2]);
				Util::pr($dependencyField->getValue());
			} else {
				$dependencyField = $form->getField($this->getFieldName());
			}

			return $dependencyField;
		}

		public function execute(Form $form, Field $field)
		{

			$dependencyField = $this->getDependencyField($form, $field);

			if($dependencyField)
			{
				$fieldName = $field->getName();
				if(preg_match('/(\w+)\[(\d+|\w+)\](\[(\w+)\]|)/i', $fieldName, $m))
				{
					$m2 = Util::arrayGet($m, 2);
					$ind = is_numeric($m2) ? 4 : 2;
					$fieldName = Util::arrayGet($m, $ind, $fieldName);
				}

				#var_dump('Dependency execute for '.$fieldName.' with '.$dependencyField->getName());

				$value = $dependencyField->getValue();

				#var_dump($value);

				$isValid = $dependencyField->validate();

				$isPassed = (bool)$value && $isValid;
				$field->setIsDependencyPassed( $field->getIsDependencyPassed() && $isPassed );

				return $value;
			}

			return 0;
		}

		protected function setFieldName($fieldName)
		{
			$this->fieldName = $fieldName;

			return $this;
		}

		public function getFieldName()
		{
			return $this->fieldName;
		}
	}