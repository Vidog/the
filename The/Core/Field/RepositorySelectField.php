<?php
	namespace The\Core\Field;

	use The\Core\Util;
	use The\Core\Field\SelectField;

	class RepositorySelectField extends SelectField
	{
		protected $repository;
		protected $method;
		protected $fields = array();
		protected $params = array();
		protected $doLoad = true;
		protected $valueData = array();
		protected $isFirstValueEmpty = false;

		protected $autocomplete = false;
		protected $autocompleteTemplate = '@:Field:autocomplete';

		public function applyParams(array $params)
		{
			parent::applyParams($params);
		}

		public function init()
		{
			/**
			@TODO kostyle
			*/
			if($this->isInitialized)
			{
				return $this;
			}
			$this->isInitialized = true;

			$values = array();

			if($this->getIsFirstValueEmpty())
			{
				$values = array(
					'0' => ' -- Нет -- ',
				);
			}

			$params = $this->getParams();
			foreach($params as $paramKey => $paramValue)
			{
				if(is_a($paramValue, '\The\Core\FieldDependency'))
				{
					$params[$paramKey] = $paramValue->execute($this->getForm(), $this);
				}
			}

			if($this->getIsDependencyPassed())
			{
				if($this->getDoLoad())
				{
					$repositoryName = $this->getRepository();
					if($repositoryName)
					{
						$repository = $this->getApplication()->getRepository( $repositoryName );

						$methodName = $this->getMethod();

						$hash = md5( serialize(array($repositoryName, $methodName, $params)) );
						$key = 'repository_field_storage.'.$hash;
						$result = $this->getApplication()->getRegistry($key);
						if(!$result)
						{
							if($this->getAutocomplete())
							{
								array_unshift($params, $this->getValue());
							}
							$result = call_user_func_array(array($repository, $methodName), $params);
							if(!$this->getAutocomplete())
							{
								$this->getApplication()->setRegistry($key, $result);
							}
						}

						$fields = $this->getFields();
						$valueField = Util::arrayGet($fields, 'value', 'id');
						$captionField = Util::arrayGet($fields, 'caption', 'title');

						foreach($result as $res)
						{
							$value = Util::arrayGet($res, $valueField);
							$caption = Util::arrayGet($res, $captionField);
							$values[$value] = $caption;

							$this->setValueData($value, $res);
						}
					}
				}
			}

			$this->setValues($values);

			$key = $this->getValue();
			if(!is_numeric($key) || !is_string($key))
			{
				$key = (string)$key;
			}

			if (!array_key_exists($key, $values)){
				$keys = array_keys($values);
				$this->setValue(reset($keys));
			}

			return $this;
		}

		public function getDisplayValue()
		{
			$this->init();
			
			$values = $this->getValues();

			$value = $this->getValue();

			return Util::arrayGet($values, $value, $value);
		}

		public function setRepository($repository)
		{
			$this->repository = $repository;

			return $this;
		}

		public function getRepository()
		{
			return $this->repository;
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

		public function setFields($fields)
		{
			$this->fields = $fields;

			return $this;
		}

		public function getFields()
		{
			return $this->fields;
		}

		public function setParams($params)
		{
			$this->params = $params;

			return $this;
		}

		public function getParams()
		{
			return $this->params;
		}

		public function setAutocomplete($autocomplete)
		{
			$this->autocomplete = $autocomplete;

			return $this;
		}

		public function getAutocomplete()
		{
			return $this->autocomplete;
		}

		public function setAutocompleteTemplate($autocompleteTemplate)
		{
			$this->autocompleteTemplate = $autocompleteTemplate;

			return $this;
		}

		public function getAutocompleteTemplate()
		{
			return $this->autocompleteTemplate;
		}

		/*public function onToString()
		{
			$res = parent::onToString();

			if($this->getAutocomplete())
			{
				$hiddenField = new \The\Core\Field\HiddenField($this->getName(), $this->getCaption(), $this->getDefaultValue());
				$hiddenField->setApplication($this->getApplication());
				$hiddenField->setValue( $this->getValue() );

				$this
					->setTemplatingFileName( $this->getAutocompleteTemplate() )
					->addTemplatingVariable('hidden_field', $hiddenField)
				;
			}

			return $res;
		}*/

		public function setDoLoad($doLoad)
		{
			$this->doLoad = $doLoad;

			return $this;
		}

		public function getDoLoad()
		{
			return $this->doLoad;
		}

		public function setValueData($value, $data)
		{
			$this->valueData[$value] = $data;

			return $this;
		}

		public function getValueData($value)
		{
			return Util::arrayGet($this->valueData, $value);
		}

		public function getValueDatas()
		{
			return $this->valueData;
		}

		public function setIsFirstValueEmpty($isFirstValueEmpty)
		{
			$this->isFirstValueEmpty = $isFirstValueEmpty;
		
			return $this;
		}
		
		public function getIsFirstValueEmpty()
		{
			return $this->isFirstValueEmpty;
		}
	}