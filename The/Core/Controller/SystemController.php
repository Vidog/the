<?php
	namespace The\Core\Controller;

	use The\Core\Controller;
	use The\Core\Request;
	use The\Core\Util;

	class SystemController extends Controller
	{
		public function getForm($formName, &$fieldName, $data = array(), &$isFilter, &$filterProperty, &$isModule)
		{
			$isFilter = (bool)preg_match('/(.*)TableFilter$/', $formName, $m);

			if($isFilter)
			{
				$tableClassName = $this->getApplication()->getClassName($m[1], 'Table');
				$table = new $tableClassName();
				$table->init();
				$filterProperty = $table->getPropertyName('filter');
				$table->loadFilters($data[$filterProperty]);
				$form = $table->buildFilterForm();

				if(preg_match('/'.$filterProperty.'\[(\w+)\]/i', $fieldName, $m))
				{
					$fieldName = Util::arrayGet($m, 1, $fieldName);
				}
			}else
			{
				$formClassName = $this->getApplication()->getClassName(preg_replace('/(\w+)Form$/i', '$1', $formName), 'Form');
				$form = new $formClassName();
				unset($data['form_id']);
				$form->load($data);
			}

			$form->init();

			if(preg_match('/(\w+)\[/i', $fieldName, $m))
			{
				$fieldName = Util::arrayGet($m, 1, $fieldName);
			}

			return $form;
		}

		public function dependencyAction()
		{
			$request = $this->getApplication()->getRequest();

			$formName = $request->post('form', null, Request::TYPE_STRING);
			$fieldName = $request->post('field', null, Request::TYPE_STRING);
			parse_str( $request->post('data'), $data );

			$form = $this->getForm($formName, $fieldName, $data, $isFilter, $filterProperty, $isModule);

			$allDependencies = $form->getDependencies();
			$dependencies = Util::arrayGet($allDependencies, $fieldName);
			$fieldGroupsToUpdate = array();

			if(is_array($dependencies))
			{
				foreach($dependencies as $dependencyName => $dependency)
				{
					if($isFilter)
					{
						if(preg_match('/'.$filterProperty.'\[(\w+)\]/i', $dependencyName, $m))
						{
							$dependencyName = Util::arrayGet($m, 1, $dependencyName);
						}
					}

					if(preg_match('/(\w+)\[/i', $dependencyName, $m))
					{
						$dependencyName = Util::arrayGet($m, 1, $dependencyName);
					}
					
					$fieldGroup = $form->getFields()[$dependencyName];
					$fieldGroupsToUpdate[$fieldGroup->getName()] = (string)$fieldGroup;
				}
			}else
			{
				$fieldGroup = $form->getFields()[$fieldName];
				$fieldGroupsToUpdate[$fieldGroup->getName()] = (string)$fieldGroup;
			}

			return array(
				'field_groups' => $fieldGroupsToUpdate,
			);
		}

		public function autocompleteAction()
		{
			$request = $this->getApplication()->getRequest();

			$formName = $request->post('form', null, Request::TYPE_STRING);
			$fieldName = $request->post('field', null, Request::TYPE_STRING);
			parse_str( $request->post('data'), $data );
			$value = $request->post('value', null, Request::TYPE_STRING);

			$data[$fieldName] = $value;

			$form = $this->getForm($formName, $fieldName, $data, $isFilter, $filterProperty, $isModule);

			$field = $form->getField($fieldName);

			return array(
				'values' => $field->getValues(),
				'value' => $value,
			);
		}

		public function uploadAction($strategy)
		{
			$params = $this->getApplication()->getRequest()->request('params');

			$this->getApplication()->setIsAjaxResponse(true);

			$file = Util::arrayGet($_FILES, 'file');

			if(!$file)
			{
				return array('value' => '', 'error' => '');
			}

			$dir = $this->getApplication()->getDir('../web/upload');
			$pi = pathinfo($file['name']);
			$ext = Util::arrayGet($pi, 'extension');
			$fileName = md5( mt_rand(100000, 999999) . uniqid() ).($ext ? '.'.$ext : '');

			$r = move_uploaded_file($_FILES['file']['tmp_name'], $dir.$fileName);

			if(!$r)
			{
				return array('value' => '', 'error' => '');
			}

			return array('value' => '/upload/'.$fileName, 'error' => '');
		}
	}