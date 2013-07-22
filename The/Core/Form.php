<?php
namespace The\Core;

use The\Core\Util;
use The\Core\Implant\ApplicationImplant;
use The\Core\Implant\HTMLElementImplant;
use The\Core\Implant\TemplatingImplant;
use The\Core\Implant\MessagingImplant;

class Form
{
	use ApplicationImplant;
	use HTMLElementImplant;
	use TemplatingImplant;
	use MessagingImplant;

	const METHOD_GET = 'get';
	const METHOD_POST = 'post';

	protected $name;
	protected $action;
	protected $method;
	protected $onSubmit;

	protected $uniqueName;
	protected $isFirstLoad = true;
	protected $changedFields = array();

	protected $submitButtonCaption;

	protected $errorMessage = 'Обнаружены ошибки в форме';
	protected $successMessage = 'Ура!';

	protected $isSubmitted;
	protected $isValid;

	protected $fieldGroups = array();
	protected $fields = array();

	public function __construct($onSubmit = null)
	{
		/**
		@TODO kostyle
		 */
		$this->setApplication(Util::getApplication());

		$this->beforeConstruct();

		$this
			->setOnSubmit($onSubmit)
			->setTemplatingFileName('@:Form:default')
			->setTagName('form')
			->setSubmitButtonCaption('Go');

		$this->afterConstruct();
	}

	protected function beforeConstruct()
	{
		$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'main', $this, array());
		if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'main', $this, array());
	}

	protected function afterConstruct()
	{
		$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'main', $this, array());
		if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'main', $this, array());
	}


	public function init()
	{
		foreach ($this->fields as $fieldName => $fieldGroup) {
			$this->getField($fieldName)->init();
		}
		foreach ($this->fieldGroups as $fieldGroupName => $fieldGroup) {
			$this->getFieldGroup($fieldGroupName)->init();
		}

		$action = $this->getAction();

		if (is_array($action)) {
			$action = $this->getApplication()->buildRoute($action[0], $action[1]);
		}

		$this
			->setAttribute('name', $this->getName())
			->setAttribute('action', $action)
			->setAttribute('method', $this->getMethod());

		if (!$this->getAttribute('id')) {
			$this->setAttribute('id', $this->getName());
		}

	}

	public function validate()
	{
		$res = true;

		foreach ($this->getFields() as $fieldName => $fieldGroup) {
			$field = $this->getField($fieldName);
			if (is_array($field)) {
				foreach ($field as $subField) {
					$fieldValid = $subField->validate();
					$res = $res && $fieldValid;
				}
			} else {
				$fieldValid = $field->validate();
				$res = $res && $fieldValid;
			}
		}

		return $res;
	}


	public function isSubmitted()
	{
		$this->isSubmitted = false;

		$request = $this->getApplication()->getRequest();

		if ($request->hasPost()) {
			$formId = $request->post('form_id');
			if ($formId == $this->getUniqueId()) {
				$postData = $request->post();
				$this->load($postData);
				$this->isValid = $this->validate();
				return ($this->isSubmitted = true);
			}
		}
	}

	public function isValid()
	{
		return $this->isValid;
	}

	public function getUniqueId()
	{
		return $this->name;
	}

	public function load($data)
	{
		$isFirstLoad = $this->getIsFirstLoad();
		if ($isFirstLoad) {
			$this->setIsFirstLoad(false);
			$this->changedFields = array();
		}
		if (is_object($data)) {
			if (is_a($data, '\The\Core\Model')) {
				$data = $data->getFieldsData();
			} else {
				$data = (array)$data;
			}
		} elseif (!is_array($data)) {
			$data = array();
		}

		foreach ($data as $fieldName => $value) {
			$field = $this->getField($fieldName);
			if ($field) {
				if (is_array($field) && is_array($value)) {
					foreach ($field as $index => $subfield) {
						$subvalue = Util::arrayGet($value, $index);
						$oldValue = $subfield->getValue();
						$subfield->setValue(Util::ifNull($subvalue, $subfield->getDefaultValue()));
						/*if (!$isFirstLoad) {
							$newValue = $subfield->getValue();
							if ($newValue != $oldValue) {
								$this->changedFields[$field->getName()] = array('old' => $oldValue, 'new' => $newValue);
							}
						}*/
					}
				} else {
					$oldValue = $field->getValue();
					$field->setValue(Util::ifNull($value, $field->getDefaultValue()));
					if (!$isFirstLoad) {
						$newValue = $field->getValue();
						if ($newValue != $oldValue) {
							$this->changedFields[$field->getName()] = array('old' => $oldValue, 'new' => $newValue);
						}
					}
				}
			}
		}
	}

	public function getData()
	{
		$data = array();

		foreach ($this->getFields() as $fieldName => $fieldGroup) {
			$field = $this->getField($fieldName);
			$data[$fieldName] = $field->getValue();
		}

		return $data;
	}

	public function getChangedFields()
	{
		return $this->changedFields;
	}

	protected function addFieldGroup($name = null, $caption = null)
	{
		$fg = Util::arrayGet($this->fieldGroups, $name);
		if ($fg) {
			return $fg;
		}
		$fg = new FieldGroup($name, $caption);

		#$fg->setApplication($this->getApplication());
		$fg->setForm($this);

		$this->fieldGroups[$name] = $fg;

		return $fg;
	}

	public function getFieldGroup($name)
	{
		return Util::arrayGet($this->fieldGroups, $name);
	}

	public function getFieldGroups()
	{
		return $this->fieldGroups;
	}

	public function addField(Field $field, FieldGroup $fieldGroup = null)
	{
		#$field->setApplication($this->getApplication());
		$field->setForm($this);

		if ($fieldGroup === null) {
			$fieldGroup = $this->addFieldGroup('_default', 'Default field group');
		}

		$fieldGroup->addField($field);

		$this->fields[$field->getName()] = $fieldGroup;

		return $field;
	}

	/**
	 * @param $name
	 * @return Field
	 */
	public function getField($name)
	{
		$fieldGroup = Util::arrayGet($this->fields, $name);
		return $fieldGroup ? $fieldGroup->getField($name) : null;
	}

	public function removeField($name)
	{
		$fieldGroup = Util::arrayGet($this->fields, $name);
		if ($fieldGroup) {
			$fieldGroup->removeField($name);
			unset($this->fields[$name]);
		}
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function setErrorMessage($errorMessage)
	{
		$this->errorMessage = $errorMessage;

		return $this;
	}

	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	public function setSuccessMessage($successMessage)
	{
		$this->successMessage = $successMessage;

		return $this;
	}

	public function getSuccessMessage()
	{
		return $this->successMessage;
	}

	public function setAction($action)
	{
		$this->action = $action;

		return $this;
	}

	public function getAction()
	{
		return $this->action;
	}

	public function setMethod($method)
	{
		$this->method = $method;

		return $this;
	}

	public function getMethod()
	{
		return $this->method;
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

	public function setIsFirstLoad($isFirstLoad)
	{
		$this->isFirstLoad = $isFirstLoad;

		return $this;
	}

	public function getIsFirstLoad()
	{
		return $this->isFirstLoad;
	}

	public function setOnSubmit($onSubmit)
	{
		$this->onSubmit = $onSubmit;

		return $this;
	}

	public function getOnSubmit()
	{
		return $this->onSubmit;
	}

	public function setSubmitButtonCaption($submitButtonCaption)
	{
		$this->submitButtonCaption = $submitButtonCaption;

		return $this;
	}

	public function getSubmitButtonCaption()
	{
		return $this->submitButtonCaption;
	}

	public function callOnSubmit()
	{

	}

	public function getDependencies()
	{
		$fieldDependencies = array();
		foreach ($this->getFields() as $fieldName => $fieldGroupName) {
			$field = $this->getField($fieldName);
			if (!is_object($field)) {
				if (is_array($field)) {
					$fieldXDependencies = $field[0]->getDependencies();
				} else {
					break;
				}
			} else {
				$fieldXDependencies = $field->getDependencies();
			}
			foreach ($fieldXDependencies as $fieldDependencyName => $dependencyFields) {
				if (!isset($fieldDependencies[$fieldDependencyName])) {
					$fieldDependencies[$fieldDependencyName] = $dependencyFields;
				} else {
					$fieldDependencies[$fieldDependencyName] = array_merge($fieldDependencies[$fieldDependencyName], $dependencyFields);
				}
			}
		}

		return $fieldDependencies;
	}

	public function onToString()
	{
		$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'before', $this, array());
		if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'before', $this, array());

		$this->init();

		if ($this->getIsSubmitted()) {
			if (!$this->getIsValid()) {
				if (($errorMessage = $this->getErrorMessage())) {
					$this->addError($errorMessage);
				}
			} else {
				if (($successMessage = $this->getSuccessMessage())) {
					$this->addSuccess($successMessage);
				}
			}
		}

		$this->setTemplatingVariables(array('form' => $this));

		if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'after', $this, array());
		$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'after', $this, array());
	}

	public function getIsValid()
	{
		return $this->isValid;
	}

	public function setIsValid($isValid)
	{
		$this->isValid = $isValid;

		return $this;
	}

	public function getIsSubmitted()
	{
		return $this->isSubmitted;
	}

	public function setIsSubmitted($isSubmitted)
	{
		$this->isSubmitted = $isSubmitted;

		return $this;
	}
}