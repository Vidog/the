<?php
namespace The\Core;

use The\Core\Util;
use The\Core\Implant\ApplicationImplant;
use The\Core\Implant\HTMLElementImplant;
use The\Core\Implant\TemplatingImplant;

class FieldGroup
{
	use ApplicationImplant;
	use HTMLElementImplant;
	use TemplatingImplant;

	protected $name;
	protected $caption;
	protected $form;

		public $fields = array();
		protected $isInitialized = false;

		public function __construct($name = null, $caption = null)
		{
			/**
			@TODO kostyle
			 */
			$this->setApplication(Util::getApplication());
		
			$this
				->setName($name)
				->setCaption($caption)
				->setTemplatingFileName('@:FieldGroup:default')
				->setTagName('fieldset')
			;

			$this->addTemplatingVariable('fieldGroup', $this);
		}

		public function setName($name)
		{
			$this->name = $name;

			return $this;
		}

		public function getName()
		{
			return $this->name;
		}

		public function setCaption($caption)
		{
			$this->caption = $caption;

			return $this;
		}

		public function getCaption()
		{
			return $this->caption;
		}

		public function addField(Field $field)
		{
			$this->fields[$field->getName()] = $field;
			return $field;
		}

		public function getField($name)
		{
			return Util::arrayGet($this->fields, $name);
		}

		public function removeField($name)
		{
			unset($this->fields[$name]);
		}

		public function getFields()
		{
			return $this->fields;
		}

		public function setForm($form)
		{
			$this->form = $form;

			return $this;
		}

		public function getForm()
		{
			return $this->form;
		}

	public function onToString()
	{
		$this->init();

		$this->setAttribute('id', 'THE_field_group_'.$this->getName());
	}

	public function init()
	{
		if ($this->isInitialized){
			return $this;
		}

		$fields = $this->getFields();
		foreach ($fields as $fieldName => $field)
		{

			if( sizeof($dependencies = $field->getDependencies()) > 0 )
			{
				foreach($dependencies as $dependencyFieldName => $dependency)
				{
					if( ($xfield = $this->getField($dependencyFieldName)) )
					{
						$xfield->addClass('THE_dependency');
					}
				}
			}

			if ($field->getIsRepeated())
			{
				$value = $field->getValue();
				if (!is_array($value)) {
					$value = array($value);
				}
				$this->fields[$fieldName] = array();
				$i = 0;
				foreach ($value as $val) {
					$newField = clone $field;
					$newField->setValue($val);
					$newField->addClass('THE_field_' . $field->getInputName());
					$newField->setName($field->getName() . '[' . $i . ']');
					$newField->setIndex($i);
					#$newField->setIsRepeated(false);
					$newField->setIsInitialized(false);
					$newField->init();
					$this->fields[$fieldName][$i] = $newField;
					$i++;
				}
			}
		}
		$this->isInitialized = true;
	}

}