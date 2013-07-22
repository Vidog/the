<?php
	namespace The\Core;

	use The\Core\Util;
	use The\Core\Validator;
	use The\Core\Query;
	use The\Core\Exception\ValidatorException;

	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\HTMLElementImplant;
	use The\Core\Implant\TemplatingImplant;
	use The\Core\Implant\MessagingImplant;
	use The\Core\Implant\EventsImplant;
	use The\Core\Implant\RepeatedFieldImplant;

	class Field
	{
		use ApplicationImplant;
		use HTMLElementImplant;
		use TemplatingImplant;
		use MessagingImplant;
		use EventsImplant;
		use RepeatedFieldImplant;

		protected $name;
		protected $queryName;
		protected $caption;
		protected $value;
		protected $displayValue;
		protected $isDisplayValueRaw = false;
		protected $defaultValue;
		protected $form;
		protected $table;
		protected $rowIndex;

		protected $isRequired = false;

		protected $isInitialized = false;

		protected $isDependencyPassed = true;

		protected $validators = array();

		protected $isWithoutCaption = false;
		protected $isInnerCaption = false;
		protected $beforeText;
		protected $afterText;
		protected $helpText;

		const EVENT_APPLY_FILTER_QUERY  = 'apply_filter_query';
		const EVENT_APPLY_SORTING_QUERY = 'apply_sorting_query';
		const EVENT_APPLY_QUERY         = 'apply_query';
		const EVENT_SET_VALUE           = 'set_value';
		const EVENT_GET_VALUE           = 'get_value';
		const EVENT_GET_DISPLAY_VALUE   = 'get_display_value';
		const EVENT_SET_DISPLAY_VALUE   = 'set_display_value';

		public function __construct($name, $caption = null, $defaultValue = null)
		{
			/**
			@TODO kostyle
			 */
			$this->setApplication(Util::getApplication());

			$this->beforeConstruct();

			$this
			->setName($name)
			->setCaption($caption)
			->setDefaultValue($defaultValue)
			->setValue($defaultValue)
			->setTemplatingFileName('@:Field:default')
			->setWithoutCloseTag(true)
			->setAttribute('type', 'text')
			->setTagName('input');

			$this->afterConstruct();
		}

		public function cloneObject()
		{
			$new = clone $this;

			$new->setEvents($this->getEvents());

			return $new;
		}

		public function applyParams(array $params)
		{
			foreach ($params as $paramName => $paramValue)
			{
				$methodName = 'set' . ucfirst($paramName);
				$this->$methodName($paramValue);
			}
		}

		public function applyFilterQuery(Query $q)
		{
			if (($res = $this->callEvent(self::EVENT_APPLY_FILTER_QUERY, $q)))
			{
				return $res;
			}

			$name = $this->getQueryName();
			$val  = $this->getValue();
			if (is_array($val))
			{
				$q->where($name, Query::IN, ':filter_' . $name)->setParameter('filter_' . $name, $val);
			}
			else
			{
				$q->where($name, ':filter_' . $name)->setParameter('filter_' . $name, $val);
			}

			return $this;
		}

		public function applySortingQuery(Query $q, $direction = 'asc')
		{
			if (($res = $this->callEvent(self::EVENT_APPLY_SORTING_QUERY, $q, $direction)))
			{
				return $res;
			}

			$name = $this->getQueryName();
			$q->order(array($this->getQueryName() => $direction));

			return $this;
		}

		public function applyQuery(Query $q)
		{
			if (($res = $this->callEvent(self::EVENT_APPLY_QUERY, $q)))
			{
				return $res;
			}

			return $this;
		}

		protected function beforeConstruct()
		{

		}

		protected function afterConstruct()
		{

		}

		public function init()
		{
			if ($this->isInitialized)
			{
				return $this;
			}

			$this->isInitialized = true;

			return $this;
		}

		public function validateValue($value)
		{
			$res = true;

			if ($this->getIsRequired())
			{
				$isValid = (bool)$value;
				if (!$isValid)
				{
					$this->addError('Поле обязательно для заполнения');
				}
				$res = $res && $isValid;
			}

			if ($res)
			{
				$validators = $this->getValidators();

				foreach ($validators as $validator)
				{
					try
					{
						$validatorValid = $validator->validate($value);
					}
					catch (ValidatorException $e)
					{
						$validatorValid = false;
						$this->addError($e->getMessage());
					}
					$res = $res && $validatorValid;
				}
			}

			return $res;
		}

		public function validate()
		{
			$res = true;

			$value = $this->getValue();

			if ($this->getIsRepeated())
			{
				foreach ($value as $val)
				{
					$res = $res && $this->validateValue($val);
				}
			}
			else
			{
				$res = $res && $this->validateValue($value);
			}

			return $res;
		}

		public function getDependencies()
		{
			$fieldDependencies = array();

			foreach ($this->getParams() as $paramName => $paramValue)
			{
				if (is_a($paramValue, '\The\Core\FieldDependency'))
				{
					if (!isset($fieldDependencies[$paramValue->getFieldName()]))
					{
						$fieldDependencies[$paramValue->getFieldName()] = array();
					}
					$fieldDependencies[$paramValue->getFieldName()][$this->getName()] = $this;
				}
			}

			return $fieldDependencies;
		}

		public function addValidator(Validator $validator)
		{
			$validator->setApplication($this->getApplication());

			$this->validators[] = $validator;

			return $this;
		}

		protected function getValidators()
		{
			return $this->validators;
		}

		public function getParams()
		{
			return array();
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

		public function setIsDisplayValueRaw($isDisplayValueRaw)
		{
			$this->isDisplayValueRaw = $isDisplayValueRaw;

			return $this;
		}

		public function getIsDisplayValueRaw()
		{
			return $this->isDisplayValueRaw;
		}

		public function setQueryName($queryName)
		{
			$this->queryName = $queryName;

			return $this;
		}

		public function getQueryName()
		{
			return $this->queryName ? $this->queryName : $this->getInputName();
		}

		public function getInputName()
		{
			return preg_replace('/[\[\]]/', '_', $this->getName());
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

		public function setValue($value)
		{
			if (($val = $this->callEvent(self::EVENT_SET_VALUE, $value)))
			{
				$value = $val;
			}

			$this->value = $value;

			return $this;
		}

		public function getValue()
		{
			if (($val = $this->callEvent(self::EVENT_GET_VALUE)))
			{
				return $val;
			}

			return $this->value;
		}

		public function setDisplayValue($displayValue)
		{
			if (($val = $this->callEvent(self::EVENT_SET_DISPLAY_VALUE, $displayValue)))
			{
				return $val;
			}

			$this->displayValue = $displayValue;

			return $this;
		}

		public function getDisplayValue()
		{
			if (($val = $this->callEvent(self::EVENT_GET_DISPLAY_VALUE, $this->displayValue)))
			{
				return $val;
			}

			return $this->displayValue;
		}

		public function setDefaultValue($defaultValue)
		{
			$this->defaultValue = $defaultValue;

			return $this;
		}

		public function getDefaultValue()
		{
			return $this->defaultValue;
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

		public function setIsRequired($isRequired)
		{
			$this->isRequired = $isRequired;

			return $this;
		}

		public function getIsRequired()
		{
			return $this->isRequired;
		}

		public function setIsDependencyPassed($isDependencyPassed)
		{
			$this->isDependencyPassed = $isDependencyPassed;

			return $this;
		}

		public function getIsDependencyPassed()
		{
			return $this->isDependencyPassed;
		}

		public function onToString()
		{
			$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'before', $this, array());
			if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'before', $this, array());

			$value = $this->getValue();

			$this
			->setAttribute('name', $this->getName())
			->setAttribute('value', Util::escapeHtml($value))
			->setDataAttribute('value', Util::escapeHtml($value))
			->setDataAttribute('default-value', $this->getDefaultValue());

			if (!$this->getAttribute('id'))
			{
				$this->setAttribute('id', $this->getInputName());
			}

			if ($this->getIsRequired())
			{
				$this->setAttribute('required', 'required');
			}

			$this->addTemplatingVariable('field', $this);

			if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'after', $this, array());
			$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'after', $this, array());
		}

		public function getIdAttribute()
		{
			if (!($attr = $this->getAttribute('id')))
			{
				return $this->getInputName();
			}
			else
			{
				return $attr;
			}
		}

		public function setIsInitialized($isInitialized)
		{
			$this->isInitialized = $isInitialized;

			return $this;
		}

		public function setIsWithoutCaption($isWithoutCaption)
		{
			$this->isWithoutCaption = $isWithoutCaption;

			return $this;
		}

		public function getIsWithoutCaption()
		{
			return $this->isWithoutCaption;
		}

		public function setIsInnerCaption($isInnerCaption)
		{
			$this->isInnerCaption = $isInnerCaption;

			return $this;
		}

		public function getIsInnerCaption()
		{
			return $this->isInnerCaption;
		}

		public function setBeforeText($beforeText)
		{
			$this->beforeText = $beforeText;

			return $this;
		}

		public function getBeforeText()
		{
			return $this->beforeText;
		}

		public function setAfterText($afterText)
		{
			$this->afterText = $afterText;

			return $this;
		}

		public function getAfterText()
		{
			return $this->afterText;
		}

		public function setHelpText($helpText)
		{
			$this->helpText = $helpText;

			return $this;
		}

		public function getHelpText()
		{
			return $this->helpText;
		}

		public function setTable($table)
		{
			$this->table = $table;
		}

		public function getTable()
		{
			return $this->table;
		}

		public function setRowIndex($rowIndex)
		{
			$this->rowIndex = $rowIndex;
		}

		public function getRowIndex()
		{
			return $this->rowIndex;
		}

		public function getRowData($index = null)
		{
			if($index === null)
			{
				$index = $this->getRowIndex();
			}

			return $this->getTable()->getRowData($index);
		}
	}