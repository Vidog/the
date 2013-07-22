<?php
namespace The\Core\Field;

use The\Core\DynamicForm;
use The\Core\Field;
use The\Core\Lib\Dumphper;
use The\Core\Table;
use The\Core\Util;

class CollectionField extends Field
{
	protected $fields = array();
	protected $dataForm;
	protected $formFields = array();
	protected $data = array();

	public function beforeConstruct()
	{
		$this->dataForm = new DynamicForm();
	}

	public function afterConstruct()
	{
		parent::afterConstruct();

		$this
			->setTemplatingFileName('@:Field:collection')
			->removeTemplatingVariable('field')
			->setBaseName($this->getName());
		;
	}

	public function setFields($fields)
	{
		$this->fields = $fields;

		foreach ($fields as $subFieldName => $subField) {
			$fieldClassName = $this->getApplication()->getClassName(Util::arrayGet($subField, 'type', 'String'), 'Field');
			$field = new $fieldClassName($subFieldName, Util::arrayGet($subField, 'caption'), Util::arrayGet($subField, 'default_value'));
			$this->dataForm->addField($field);
			$field->applyParams(Util::arrayGet($subField, 'params', array()));
			$field->setIsRequired((bool)Util::arrayGet($subField, 'required', false));
			$attributes = Util::arrayGet($subField, 'attributes', array());
			foreach ($attributes as $attributeName => $attributeValue) {
				$field->setAttribute($attributeName, $attributeValue);
			}

			/*
			$validators = Util::arrayGet($subField, 'validators', array());
			foreach($validators as $validatorData)
			{
				$pars = Util::arrayGet($validatorData, 'params');
				if(!is_array($pars))
				{
					$pars = array();
				}
				$params = array();
				foreach($pars as $param)
				{
					$params[] = utiL::phptoString($param);
				}
				$params = implode(', ', $params);
				$tx = '$validatorClassName = $'.$varName.'->getApplication()->getClassName(\''.(string)Util::arrayGet($validatorData, 'type').'\', \'Validator\');'.$br;
				$tx .= '$field->addValidator( new $validatorClassName('.$params.') );';
				$data .= preg_replace('/((.*)+)/i', "\t\t".'$1', $tx).$br;
			}
			*/

			$this->formFields[$subFieldName] = $field;
		}
	}

	public function getFields()
	{
		return $this->formFields;
	}

	public function getValue()
	{
		if ($this->getIsRepeated()) {
			return $this->data;
		}

		$data = array();

		$fields = $this->getFields();

		foreach ($fields as $subFieldName => $subField) {
			$data[$subField->getName()] = $subField->getValue();
		}

		if ($this->getIsRepeated()) {
			$data = array($data);
		}

		return $data;
	}

	public function getDependencies()
	{
		$dependencies = array();

		foreach ($this->formFields as $fieldName => $field)
		{
			$dependency = $field->getDependencies();

			foreach($dependency as $dname => $d)
			{
				$dependencies[$dname] = array($this->getName() => $this);
			}
		}

		return $dependencies;
	}

	public function setValue($value)
	{
		if (!is_array($value)) {
			$value = array($value);
		}

		$fields = $this->getFields();

		foreach ($fields as $subFieldName => $subField)
		{
			$subField->setValue( Util::arrayGet($value, $subFieldName, $subField->getDefaultValue()) );
		}

		$res = parent::setValue($value);
		$this->data = $value;
		return $res;
	}

	public function validate()
	{
		$res = true;

		$fields = $this->getFields();

		foreach ($fields as $subFieldName => $subField) {
			$res = $res && $subField->validate();
		}

		return $res;
	}

	public function init()
	{
		$res = parent::init();

		$fields = $this->getFields();
		foreach ($fields as $fieldName => $field) {
			$field->init();
		}

		return $res;
	}

	public function onToString()
	{
		$fields = $this->getFields();

		foreach ($fields as $fieldName => $field)
		{
			$newField = clone($this->formFields[$fieldName]);
			$value = Util::arrayGet($this->data, $fieldName, $newField->getDefaultValue());
			$newField->setBaseName($fieldName);
			$newField->setValue($value);
			$newName = $this->getName() . '[' . $fieldName . ']';
			$newField->setName($newName);
			$newField->setAttribute('id', $newField->getInputname());
			$newField->setDataAttribute('index', $this->getIndex());
			$newField->setIndex($this->getIndex());
			$newField->addClass('THE_collection_' . $this->getInputName());
			$newField->setIsInitialized(false);
			$this->dataForm->getField($fieldName)->setValue($value);
			$newField->init();

			if( sizeof($dependencies = $field->getDependencies()) > 0 )
			{
				foreach($dependencies as $dependencyFieldName => $dependency)
				{
					$fname = $this->getBaseName();
					if($this->getIsRepeated())
					{
						$fname .= '['.$this->getIndex().']';
					}
					$fname .= '['.$dependencyFieldName.']';

					$xfield = Util::arrayGet($this->formFields, $fname);
					if($xfield)
					{
						$xfield->addClass('THE_dependency');
					}
				}
			}

			$this->formFields[$newName] = $newField;
			unset($this->formFields[$fieldName]);
		}

		$this->addTemplatingVariable('collection', $this);

		return parent::onToString();
	}
}