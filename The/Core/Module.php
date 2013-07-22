<?php
	namespace The\Core;

	use The\Core\Controller;
	use The\Core\DynamicForm;
	use The\Core\DynamicTable;
	use The\Core\Util;
	use The\Core\Exception\NotFoundException;

	class Module extends Controller
	{
		protected $name;
		protected $tableName;
		protected $modelName;
		protected $byPage = 20;
		protected $elementId = 0;

		protected $propertiesNames = array(
			'page' => 'page',
			'by_page' => 'by_page',
			'filter' => 'filter',
			'sorting_field' => 'sorting_field',
			'sorting_direction' => 'sorting_direction',
		);

		protected $form;
		protected $actions = array();
		protected $currentAction;

		protected $sortingFields = array();
		protected $listActions = array();
		protected $elementActions = array();
		protected $elementMultipleActions = array();

		public function __construct()
		{
			$this->setForm( new DynamicForm() );

			/**
			@TODO kostyle
			*/
			$this->setApplication( Util::getApplication() );

			parent::__construct();
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

		protected function setForm($form)
		{
			$this->form = $form;

			return $this;
		}

		public function getForm()
		{
			return $this->form;
		}

		protected function setTableName($tableName)
		{
			$this->tableName = $tableName;

			return $this;
		}

		public function getTableName()
		{
			return $this->tableName;
		}

		protected function setCurrentAction($currentAction)
		{
			$this->currentAction = $currentAction;

			return $this;
		}

		public function getCurrentAction()
		{
			return $this->currentAction;
		}

		protected function addTemplatingVariable($name, $value)
		{
			$this->currentAction['templating_variables'][$name] = $value;

			return $this;
		}

		protected function setByPage($byPage)
		{
			$this->byPage = $byPage;

			return $this;
		}

		public function getByPage()
		{
			return $this->byPage;
		}

		protected function setElementId($elementId)
		{
			$this->elementId = $elementId;

			return $this;
		}

		public function getElementId()
		{
			return $this->elementId;
		}

		protected function addAction($actionName, $params = array(), $fields = array(), $autoValue = array())
		{
			$actionName = strtolower($actionName);
			$systemActions = array(
				'list' => array(
					'title' => 'Список',
					'add_action' => false,
					'args' => array(

					),
					'templating_variables' => array(

					),
					'checker_method' => false,
				),
				'element' => array(
					'title' => 'Просмотр',
					'add_action' => array(
						'method' => 'addElementAction',
						'icon' => '',
						'title' => 'Подробнее',
					),
					'args' => array(

					),
					'templating_variables' => array(

					),
					'checker_method' => false,
				),
				'create' => array(
					'title' => 'Добавление',
					'add_action' => array(
						'method' => 'addListAction',
						'icon' => '',
						'title' => 'Добавить',
					),
					'args' => array(

					),
					'templating_variables' => array(

					),
					'checker_method' => false,
				),
				'update' => array(
					'title' => 'Редактирование',
					'add_action' => array(
						'method' => 'addElementAction',
						'icon' => '',
						'title' => 'Редактировать',
					),
					'args' => array(

					),
					'templating_variables' => array(

					),
					'checker_method' => false,
				),
				'remove' => array(
					'title' => 'Удаление',
					'add_action' => array(
						'method' => 'addElementAction',
						'icon' => '',
						'title' => 'Удалить',
					),
					'args' => array(

					),
					'templating_variables' => array(

					),
					'checker_method' => false,
				),
			);
			$isSystemAction = in_array($actionName, array_keys($systemActions));

			if($isSystemAction)
			{
				$sysAction = $systemActions[$actionName];
				$addAction = Util::arrayGet($sysAction, 'add_action');
				if($addAction)
				{
					$method = Util::arrayGet($addAction, 'method');
					$addActionTitle = Util::arrayGet($addAction, 'title', $sysAction['title']);
					$addActionIcon = Util::arrayGet($addAction, 'icon');
					if($method)
					{
						$this->$method('_'.$actionName, array(
							'caption' => $addActionTitle,
							'icon' => $addActionIcon,
							'type' => 'route',
							'url' => $this->getApplication()->getRouteName(),
							'params' => array(
								'action' => $actionName,
								'elementID' => '^DATA:id',
							),
						));
					}
				}
			}
			
			$this->actions[$actionName] = array_merge(array(
				'name' => $actionName,
				'title' =>  $isSystemAction ? $systemActions[$actionName]['title'] : '',
				'templating_file_name' => $isSystemAction ? '@Core:Module:'.$actionName : '@Core:Module:default',
				'args' => $isSystemAction ? $systemActions[$actionName]['args'] : array(),
				'templating_variables' => $isSystemAction ? $systemActions[$actionName]['templating_variables'] : array(),
				'fields' => $fields,
				'auto_value' => $autoValue,
				'checker_method' => $isSystemAction ? $systemActions[$actionName]['checker_method'] : false,
			), $params);

			return $this;
		}

		protected function getPropertyName($property)
		{
			return Util::arrayGet($this->propertiesNames, $property, $property);
		}

		protected function getAction($actionName)
		{
			return Util::arrayGet($this->actions, $actionName);
		}

		public function getModelName()
		{
			return $this->modelName;
		}

		public function handleList($query)
		{

		}

		public function handleElement($query, $elementId)
		{

		}

		public function handleRemove($model, $query, $elementId)
		{

		}

		public function handleCreate($model, $query, array &$data)
		{

		}

		public function handleUpdate($model, $query, array &$data, $elementId)
		{

		}

		protected function getList()
		{
			$db = $this->getApplication()->getDB();
			if( ($modelName = $this->getModelName()) )
			{
				$q = $db->createQueryFromModel($this->getModelName(), 'tbl');
			}else
			{
				$q = $db->createQuery($this->getTableName(), 'tbl');
			}
			$q
				->select('tbl.*')
			;

			$this->handleList($q);

			return $q;
		}

		protected function getElement($elementId)
		{
			$db = $this->getApplication()->getDB();
			$modelName = $this->getModelName();
			if( ($modelName) && ($modelName != '\The\Core\Model') )
			{
				$q = $db->createQueryFromModel($this->getModelName(), 'tbl');
			}else
			{
				$q = $db->createQuery($this->getTableName(), 'tbl');
			}
			$q
				->select('tbl.*')
				->where('id', ':element_id')
				->setParameter('element_id', $elementId)
			;

			$this->handleElement($q, $elementId);

			return $q;
		}

		protected function parseFormData($data, $action)
		{
			$autoValue = $action['auto_value'];

			$autoValueData = array();

			$autoValueVariables = array(
				'^now' => Util::date('Y-m-d H:i:s'),
			);

			foreach($autoValue as $key => $value)
			{
				$autoValueData[$key] = Util::arrayGet($autoValueVariables, $value);
			}

			$result = array_merge($autoValueData, $data);

			return $result;
		}

		protected function createForm($fields, $data = array())
		{
			$form = new DynamicForm();
			$form->setMethod(DynamicForm::METHOD_POST);

			$fieldGroup = $form->addFieldGroup('_default', '');

			foreach($fields as $fieldName)
			{
				$field = $this->getForm()->getField($fieldName);
				$form->addField( $field, $fieldGroup );
			}

			$form->load($data);

			return $form;
		}

		protected function createBaseTable($fields, &$query)
		{
			$table = new DynamicTable();

			foreach($fields as $field)
			{
				$f =  $this->getForm()->getField($field);
				$f->applyQuery($query);
				$table->addField( $f );
			}

			foreach($this->getSortingFields() as $sortingField)
			{
				$table->addSortingField($sortingField);
			}

			$table->setName( $this->getName().'Table' );

			$table->setPropertiesNames( $this->getPropertiesNames() );

			return $table;
		}

		protected function loadTableValues($table, Query &$query, $dbName = null, $method = 'fetchObjects')
		{
			$table->loadFromQuery($query, $this->getByPage(), $dbName, $method);

			return $this;
		}

		protected function createTable($fields, Query $query, $dbName = null, $method = 'fetchObjects')
		{
			$table = $this->createBaseTable($fields, $query);

			$this->loadTableValues($table, $query, $dbName, $method);

			return $table;
		}

		public function listAction()
		{
			$action = $this->getCurrentAction();

			$table = $this->createTable($action['fields'], $this->getList());

			$table->setListActions( $this->getListActions() );
			$table->setElementActions( $this->getElementActions() );
			$table->setElementMultipleActions( $this->getElementMultipleActions() );

			$this->addTemplatingVariable('table', $table);

			return false;
		}

		public function elementAction()
		{
			$action = $this->getCurrentAction();

			$table = $this->createTable($action['fields'], $this->getElement($this->getElementId()), null, 'fetchObject');
			$table->setIsHorizontal(true);

			$table->setListActions( $this->getElementListActions() );

			$this->addTemplatingVariable('table', $table);

			return false;
		}

		public function createAction()
		{
			$action = $this->getCurrentAction();

			$form = $this->createForm($action['fields'], array());

			$form->setSubmitButtonCaption('Добавить');

			if($form->isSubmitted())
			{
				if($form->isValid())
				{
					$data = $this->parseFormData($form->getData(), $action);

					$model = $this->getApplication()->createModel($this->getModelName());

					$model->setOnCreate(array($this, 'handleCreate'));

					$res = $model->loadFromArray($data)->save();

					#Util::pr('Saved');
				}else
				{
					#Util::pr('Invalid');
				}
			}

			$this->addTemplatingVariable('form', $form);

			return false;
		}

		public function handleUpdateData(array &$elementData)
		{

		}

		public function updateAction()
		{
			$action = $this->getCurrentAction();

			$elementId = $this->getElementId();
			$elementModel = $this->getElementModel();

			if(!$elementModel)
			{
				throw new NotFoundException;
			}

			$elementData = $elementModel->getFieldsData();

			$this->handleUpdateData($elementData);

			$form = $this->createForm($action['fields'], $elementData);

			$form->setSubmitButtonCaption('Сохранить');

			if($form->isSubmitted())
			{
				if($form->isValid())
				{
					$data = $this->parseFormData($form->getData(), $action);

					#$diff = array_diff($data, $elementData);
					$diff = array();
					foreach($elementData as $key => $val)
					{
						$val2 = Util::arrayGet($data, $key);
						if($val2 != $val && $val2 !== null)
						{
							$diff[$key] = $val2;
						}
					}

					#Util::pr($elementData, $data, $diff);

					$model = $this->getApplication()->createModel($this->getModelName())->setId($elementId);

					$model->setOnUpdate(array($this, 'handleUpdate'));

					$res = $model->loadFromArray($diff)->save();

					#Util::pr('Saved');
				}else
				{
					#Util::pr('Invalid');
				}
			}

			$this->addTemplatingVariable('form', $form);

			return false;
		}

		public function removeAction()
		{
			$form = new DynamicForm();
			$form->setMethod(DynamicForm::METHOD_POST);

			$form->setSubmitButtonCaption('Удалить');

			$fieldGroup = $form->addFieldGroup('_default', 'Подтверждение удаления');

			if($form->isSubmitted())
			{
				$elementModel = $this->getElementModel();

				if($elementModel)
				{
					$elementModel->remove();

					$this->getApplication()->getFlash()->addSuccess('Элемент удалён');
				}

				$routeName = $this->getApplication()->getRouteName();

				return $this->getApplication()->redirect($routeName);
			}else
			{
				$this->addTemplatingVariable('form', $form);
			}

			return false;
		}

		public function getElementModel()
		{
			$elementId = $this->getElementId();
			$elementQuery = $this->getElement($elementId);

			return $this->getApplication()->getDB()->fetchObject($elementQuery);
		}

		public function checkAction($action)
		{
			$checkerMethod = Util::arrayGet($action, 'checker_method');

			if(is_callable($checkerMethod))
			{
				#$rowData = $this->getElementModel();

				#Util::pr($rowData);

				#$check = call_user_func_array($checkerMethod, array(null, $action, $rowData));
				$check = true;

				if(!$check)
				{
					return false;
				}
			}

			return true;
		}

		public function routeAction($action, $elementID)
		{
			$actionName = $action;
			$action = $this->getAction($action);

			$this->setElementId($elementID);

			if(!$action)
			{
				throw new \Exception('Action "'.$actionName.'" doesn\'t exists in '.__CLASS__);
				return;
			}

			if(!$this->checkAction($action))
			{
				return;
			}

			$this->setCurrentAction($action);

			$methodName = $actionName.'Action';

			$res = false;

			if(method_exists($this, $methodName))
			{
				$res = call_user_func_array(array($this, $methodName), $action['args']);
			}

			if($res !== false)
			{
				return $res;
			}

			$action = $this->getCurrentAction();

			return is_array($res) ? $res : $this->render($action['templating_file_name'], array_merge(array(
				'action_title' => $action['title'],
			), $action['templating_variables']));
		}

		public function getPropertiesNames()
		{
			return $this->propertiesNames;
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

		public function getElementListActions()
		{
			$app = $this->getApplication();
			$route = $app->getRouteName();

			$canUpdate = Util::arrayGet($this->getActions(), 'update');
			$canRemove = Util::arrayGet($this->getActions(), 'remove');

			$actions = array();

			if($canUpdate)
			{
				$actions['update'] = array(
					'caption' => 'Редактировать',
					'type' => 'js',
					'url' => 'document.location = \''.$app->buildRoute($route, array('action' => 'update', 'elementID' => $this->getElementId())).'\'',
				);
			}

			if($canRemove)
			{
				$actions['remove'] = array(
					'caption' => 'Удалить',
					'type' => 'route',
					'url' => 'document.location = \''.$app->buildRoute($route, array('action' => 'remove', 'elementID' => $this->getElementId())).'\'',
				);
			}

			return $actions;
		}

		public function setListActions($listActions)
		{
			$this->listActions = $listActions;

			return $this;
		}

		protected function addElementAction($actionName, $action)
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

		public function getActions()
		{
			return $this->actions;
		}

		public function setActions($actions)
		{
			$this->actions = $actions;

			return $this;
		}

		protected function addElementMultipleAction($actionName, $action)
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

		public function addSortingField($sortingField)
		{
			$this->sortingFields[$sortingField] = $sortingField;

			return $this;
		}

		public function getSortingFields()
		{
			return $this->sortingFields;
		}

		public function setElementMultipleActions($elementMultipleActions)
		{
			$this->elementMultipleActions = $elementMultipleActions;

			return $this;
		}
	}