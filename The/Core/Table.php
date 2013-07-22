<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\PaginatorImplant;
	use The\Core\Implant\HTMLElementImplant;
	use The\Core\Implant\TemplatingImplant;

	use The\Core\Request;
	use The\Core\Util;
	use The\Core\Query;
	use The\Core\Form;
	use The\Core\FilterForm;
	use The\Core\Field;
	use The\Core\Pagination;
	use The\Core\HTMLElement\AHTMLElement;

	class Table
	{
		use ApplicationImplant;
		use PaginatorImplant;
		use HTMLElementImplant;
		use TemplatingImplant;

		protected $fields = array();
		protected $rows = array();
		protected $rowData = array();
		protected $isHorizontal = false;
		protected $name;
		protected $filterForm;
		protected $sortingFields = array();
		protected $propertiesNames = array();
		protected $filterFormCaption = 'Фильтрация';
		protected $chunked = false;

		protected $listActions = array();
		protected $elementActions = array();
		protected $elementMultipleActions = array();
		protected $byPage = 20;

		public function __construct()
		{
			/**
			@TODO kostyle
			 */
			$this->setApplication(Util::getApplication());

			$this->beforeConstruct();

			$this
			->setTemplatingFileName('@:Table:default')
			->setTagName('table');

			$this->addTemplatingVariable('table', $this);

			$this->setFilterForm(new FilterForm());

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

		public function getCurrentPage()
		{
			$request = $this->getApplication()->getRequest();

			$page = $request->get($this->getPropertyName('page'), null, Request::TYPE_INTEGER);

			return $page;
		}

		public function getCurrentByPage($defaultByPage = null)
		{
			if (!$defaultByPage)
			{
				$defaultByPage = $this->getByPage();
			}
			$request = $this->getApplication()->getRequest();

			$byPage = $request->get($this->getPropertyName('by_page'), $defaultByPage, Request::TYPE_INTEGER);

			return $byPage;
		}

		public function getCurrentSortingField()
		{
			$request = $this->getApplication()->getRequest();

			$field = $request->get($this->getPropertyName('sorting_field'), null, Request::TYPE_STRING);

			$fields = $this->getSortingFields();
			if (!in_array($field, $fields))
			{
				$field = reset($fields);
			}

			return $field;
		}

		public function getCurrentSortingDirection()
		{
			$request = $this->getApplication()->getRequest();

			$direction = strtoupper($request->get($this->getPropertyName('sorting_direction'), null,
				Request::TYPE_STRING));
			if (!in_array($direction, array('ASC', 'DESC')))
			{
				$direction = 'ASC';
			}

			return $direction;
		}

		public function loadFilters($filtersData)
		{
			return $this->getFilterForm()->load($filtersData);
		}

		public function getFilters()
		{
			$filtersData = $this->getApplication()->getRequest()
			               ->get($this->getPropertyName('filter'), array(), Request::TYPE_ARRAY);

			$this->loadFilters($filtersData);

			$filters = array();

			foreach ($this->getFilterForm()->getFields() as $fieldName => $fieldGroup)
			{
				$field               = $this->getFilterForm()->getField($fieldName);
				$filters[$fieldName] = $field;
			}

			return $filters;
		}

		public function applyQuery(Query $q, $byPage = null)
		{
			$q->byPage($this->getCurrentPage(), $this->getCurrentByPage($byPage));

			foreach ($this->getFilters() as $filterName => $filter)
			{
				if ($filter->getValue())
				{
					$filter->applyFilterQuery($q);
				}
			}

			$sortingField = $this->getField($this->getCurrentSortingField());
			if ($sortingField)
			{
				$sortingField->applySortingQuery($q, $this->getCurrentSortingDirection());
			}

			return $this;
		}

		public function loadFromQuery(Query $q, $byPage = null, $dbName = null, $method = 'fetchObjects')
		{
			$this
			->applyQuery($q, $byPage)
			->load($this->getApplication()->getDB($dbName)->$method($q))
			->setPaginator($q->getPaginator());

			$this->getPaginator()->setPropertiesNames($this->getPropertiesNames());

			return $this;
		}

		public function setChunked($chunked)
		{
			$this->chunked = $chunked;

			return $this;
		}

		public function getChunked()
		{
			return $this->chunked;
		}

		public function setPropertiesNames($propertiesNames)
		{
			$this->propertiesNames = $propertiesNames;

			return $this;
		}

		public function getPropertiesNames()
		{
			return $this->propertiesNames;
		}

		public function getPropertyName($property)
		{
			return Util::arrayGet($this->propertiesNames, $property, $property);
		}

		public function getByPage()
		{
			return $this->byPage;
		}

		public function setByPage($byPage)
		{
			$this->byPage = $byPage;

			return $this;
		}

		protected function addSortingField($fieldName)
		{
			$this->sortingFields[$fieldName] = $fieldName;

			return $this;
		}

		public function getSortingFields()
		{
			return $this->sortingFields;
		}

		public function hasSortingField($fieldName)
		{
			return isset($this->sortingFields[$fieldName]);
		}

		protected function setFilterForm($filterForm)
		{
			$this->filterForm = $filterForm;

			return $this;
		}

		public function getFilterForm()
		{
			return $this->filterForm;
		}

		protected function setName($name)
		{
			$this->name = $name;

			return $this;
		}

		public function getName()
		{
			return $this->name;
		}

		protected function addField(Field $field)
		{
			$this->fields[$field->getName()] = $field;

			return $field;
		}

		public function getField($fieldName)
		{
			return Util::arrayGet($this->fields, $fieldName);
		}

		public function getFields()
		{
			return $this->fields;
		}

		public function getRows()
		{
			if (($chunks = $this->getChunked()))
			{
				return array_chunk($this->rows, $chunks);
			}
			else
			{
				return $this->rows;
			}
		}

		public function init()
		{

		}

		public function load($data)
		{
			$this->rows    = array();
			$this->rowData = array();

			if (!is_array($data))
			{
				$data = array($data);
			}

			$i = 0;

			foreach ($data as $row)
			{
				$rowData = array();
				if (is_object($row))
				{
					if (is_a($row, '\The\Core\Model'))
					{
						$row = $row->getFieldsData();
					}
					else
					{
						$row = (array)$row;
					}
				}
				if (!is_array($row))
				{
					$row = array();
				}
				foreach ($this->getFields() as $fieldName => $field)
				{
					$value = Util::arrayGet($row, $fieldName);
					if ($field)
					{
						$colField = $field->cloneObject();
						$val      = Util::ifNull($value, $colField->getDefaultValue());
						$colField->setValue($val);
						$colField->setDisplayValue($val);
						$colField->setTable($this);
						$colField->setRowIndex($i);
						$rowData[$field->getName()] = $colField;
					}
				}
				$this->rows[$i]    = $rowData;
				$this->rowData[$i] = $row;
				$i++;
			}

			return $this;
		}

		public function setFilterFormCaption($filterFormCaption)
		{
			$this->filterFormCaption = $filterFormCaption;

			return $filterFormCaption;
		}

		public function getFilterFormCaption()
		{
			return $this->filterFormCaption;
		}

		public function onToString()
		{
			$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'before', $this, array());
			if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'before', $this, array());

			$this
			->setAttribute('width', '100%')
			->setAttribute('border', '1');

			if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'after', $this, array());
			$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'after', $this, array());
		}

		public function setIsHorizontal($isHorizontal)
		{
			$this->isHorizontal = $isHorizontal;

			return $this;
		}

		public function getIsHorizontal()
		{
			return $this->isHorizontal;
		}

		public function paginationBefore()
		{
			$paginator = $this->getPaginator();
			if ($paginator)
			{
				$paginator = (string)$paginator;
			}

			return $paginator;
		}

		public function paginationAfter()
		{
			return $this->paginationBefore();
		}

		public function buildFilterForm()
		{
			$filterForm = clone($this->getFilterForm());

			$filterForm->setName($this->getName() . 'Filter');

			$prefix = $this->getPropertyName('filter');

			foreach ($filterForm->getFields() as $fieldName => $fieldGroup)
			{
				$filterForm->getField($fieldName)->setName($prefix . '[' . $fieldName . ']');
			}

			$fieldGroup = $this->getFilterForm()->getFieldGroup('_default');

			if ($fieldGroup)
			{
				$fieldGroup->setCaption($this->getFilterFormCaption());
			}

			return sizeof($filterForm->getFields()) > 0 ? $filterForm : null;
		}

		public function outputBefore()
		{
			$output = '';

			$output .= $this->paginationBefore();

			$output .= (string)$this->buildFilterForm();

			return $output;
		}

		public function outputAfter()
		{
			$output = '';

			$output .= $this->paginationAfter();

			return $output;
		}

		protected function addListAction($actionName, $action)
		{
			$this->listActions[$actionName] = $action;

			return $this;
		}

		public function getListAction($actionName)
		{
			return Util::arrayGet($this->listActions, $actionName);
		}

		public function getListActions()
		{
			return $this->listActions;
		}

		public function setListActions($listActions)
		{
			$this->listActions = $listActions;

			return $this;
		}

		public function addElementAction($actionName, $action)
		{
			$this->elementActions[$actionName] = $action;

			return $this;
		}

		public function getElementAction($actionName)
		{
			return Util::arrayGet($this->elementActions, $actionName);
		}

		public function getElementActions()
		{
			return $this->elementActions;
		}

		public function setElementActions($elementActions)
		{
			$this->elementActions = $elementActions;

			return $this;
		}

		public function addElementMultipleAction($actionName, $action)
		{
			$this->elementMultipleActions[$actionName] = $action;

			return $this;
		}

		public function getElementMultipleAction($actionName)
		{
			return Util::arrayGet($this->elementMultipleActions, $actionName);
		}

		public function getElementMultipleActions()
		{
			return $this->elementMultipleActions;
		}

		public function setElementMultipleActions($elementMultipleActions)
		{
			$this->elementMultipleActions = $elementMultipleActions;

			return $this;
		}

		public function getCheckedElementActions($index = -1)
		{
			$actions = array();
			foreach ($this->elementActions as $name => $action)
			{
				$tmp = $this->buildAction('element', $name, $action, $index);
				if ($tmp)
				{
					$actions[] = $tmp;
				}
			}

			return $actions;
		}

		public function buildAction($type, $actionName, $action, $index = -1)
		{
			$rowData = Util::arrayGet($this->rowData, $index, array());

			$url           = Util::arrayGet($action, 'url');
			$caption       = Util::arrayGet($action, 'caption', $actionName);
			$type          = Util::arrayGet($action, 'type', 'route');
			$params        = (array)Util::arrayGet($action, 'params', array());
			$jsParams      = (array)Util::arrayGet($action, 'js_params', array());
			$queryParams   = (array)Util::arrayGet($action, 'query_params', array());
			$jsCallback    = Util::arrayGet($action, 'js_callback');
			$checkerMethod = Util::arrayGet($action, 'checker_method');

			if (is_callable($checkerMethod))
			{
				$check = call_user_func_array($checkerMethod, array($this, $action, $rowData));
				if (!$check)
				{
					return;
				}
			}

			$repl = function ($s) use ($rowData)
			{
				return preg_replace_callback('/\^(\w+)\:(\w+)/i', function ($m) use ($rowData)
				{
					$block = strtolower(Util::arrayGet($m, 1));
					if ($block == 'data')
					{
						$var   = strtolower(Util::arrayGet($m, 2));
						$field = Util::arrayGet($rowData, $var);
						if ($field)
						{
							return $field;
						}
					}
				}, $s);
			};

			$url        = $repl($url);
			$jsCallback = $repl($jsCallback);

			foreach ($params as $key => $param)
			{
				$params[$key] = $repl($param);
			}

			foreach ($jsParams as $key => $param)
			{
				$jsParams[$key] = $repl($param);
			}

			foreach ($queryParams as $key => $param)
			{
				$queryParams[$key] = $repl($param);
			}

			$onClickEvent = '';
			$script       = '';
			#$href = 'javascript:void(0);';

			$uniqid = 'x_' . uniqid() . '_' . mt_rand(100000, 999999);

			switch ($type)
			{
				case 'route':
				default:
					$route        = $this->getApplication()->buildRoute($url, $params, $queryParams);
					$onClickEvent = 'document.location = \'' . $route . '\'';
					break;

				case 'js':
					$xurl = str_replace('"', '\"', $url);
					$onClickEvent .= $xurl;
					break;

				case 'ajax':
					$route = $this->getApplication()->buildRoute($url, $params, $queryParams);
					$script .=
						'
		var ' . $uniqid . '_param = ' . json_encode($jsParams) . ';
	var ' . $uniqid . '_callback = function(result){ ' . $jsCallback . ' };
					';
					$onClickEvent .= 'ajaxSend(\'' . $route . '\', ' . $uniqid . '_param, ' . $uniqid . '_callback);';
					break;
			}

			if ($script)
			{
				$script = '<script>' . $script . '</script>';
			}

			$res = array(
				'script'  => $script,
				'action'  => $onClickEvent,
				'caption' => $caption,
			);

			return $res;
		}

		public function setPropertyName($property, $value)
		{
			$this->propertiesNames[$property] = $value;

			return $this;
		}

		public function getRowData($index)
		{
			return Util::arrayGet($this->rowData, $index, array());
		}
	}