<?php
	namespace The\Core\Command;

	use The\Core\Util;
	use The\Core\Yaml;
	use The\Core\Form;
	use The\Core\Command;
	use The\Core\CLI;

	class SystemCommand extends Command
	{
		protected function getFormData($fieldGroups, $varName = 'this', $witoutFieldGroup = false)
		{
			$data = '';

			$br = "\n";
			$tab = "\t";

			foreach($fieldGroups as $fieldGroupName => $fieldGroupData)
			{
				if(!$witoutFieldGroup)
				{
					$data .= '$fieldGroup = $'.$varName.'->addFieldGroup( '.utiL::phptoString($fieldGroupName).', '.utiL::phptoString( Util::arrayGet($fieldGroupData, 'caption') ).' );'.$br;
					
					if(isset($fieldGroupData['templating_file_name']))
					{
						$data .= '$fieldGroup->setTemplatingFileName( '.UtiL::phptoString($fieldGroupData['templating_file_name']).' );'.$br.$br;
					}
				}
				if(isset($fieldGroupData['fields']))
				{
					foreach($fieldGroupData['fields'] as $fieldName => $fieldData)
					{
						$data .= str_repeat($tab, 1).'$fieldClass = $'.$varName.'->getApplication()->getClassName(\''.(string)Util::arrayGet($fieldData, 'type', 'string').'\', \'Field\');'.$br;
						$data .= str_repeat($tab, 1).'$fieldObject = new $fieldClass('.utiL::phptoString($fieldName).', '.UtiL::phptoString( Util::arrayGet($fieldData, 'caption') ).', '.utiL::phptoString( Util::arrayGet($fieldData, 'default_value') ).');'.$br;
						if(!$witoutFieldGroup)
						{
							$data .= str_repeat($tab, 1).'$field = $'.$varName.'->addField($fieldObject, $fieldGroup);'.$br;
						}else
						{
							$data .= str_repeat($tab, 1).'$field = $'.$varName.'->addField($fieldObject);'.$br;
						}
						$tx = '$field->applyParams('.UtiL::phptoString( Util::arrayGet($fieldData, 'params', array()) ).');'.$br;
						#$tx .= '$field->setApplication( $'.$varName.'->getApplication() );'.$br;
						$tx .= '$field->setIsRequired('.UtiL::phptoString( (bool)Util::arrayGet($fieldData, 'required', false) ).');'.$br;
						$repeated = (bool)Util::arrayGet($fieldData, 'repeated', false);
						$tx .= '$field->setIsRepeated('.UtiL::phptoString($repeated).');'.$br;

						if ($repeated){
							$tx .= '$field->setIsRemoveAllowed('.UtiL::phptoString( (bool)Util::arrayGet($fieldData, 'remove_allowed', true) ).');'.$br;
							$tx .= '$field->setIsAddAllowed('.UtiL::phptoString( (bool)Util::arrayGet($fieldData, 'add_allowed', true) ).');'.$br;
						}

						if(sizeof( ($attributes = Util::arrayGet($fieldData, 'attributes', array())) ) > 0)
						{
							foreach($attributes as $attributeName => $attributeValue)
							{
								$tx .= '$field->setAttribute(\''.$attributeName.'\', '.Util::phptoString($attributeValue).');'.$br;
							}
						}
						if(sizeof( ($datas = Util::arrayGet($fieldData, 'datas', array())) ) > 0)
						{
							foreach($datas as $attributeName => $attributeValue)
							{
								$tx .= '$field->setDataAttribute(\''.$attributeName.'\', '.Util::phptoString($attributeValue).');'.$br;
							}
						}
						if(sizeof( ($classes = Util::arrayGet($fieldData, 'classes', array())) ) > 0)
						{
							foreach($classes as $className)
							{
								$tx .= '$field->addClass(\''.$className.'\');'.$br;
							}
						}
						if(sizeof( ($styles = Util::arrayGet($fieldData, 'styles', array())) ) > 0)
						{
							foreach($styles as $styleName => $styleValue)
							{
								$tx .= '$field->addStyle(\''.$styleName.'\', '.Util::phptoString($styleValue).');'.$br;
							}
						}
						$data .= preg_replace('/((.*)+)/i', "\t".'$1', $tx).$br;
						if(isset($fieldData['validators']))
						{
							foreach($fieldData['validators'] as $validatorData)
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
						}
						$data .= $br;
					}
				}
				$data .= $br;
			}

			return $data;
		}

		public function buildFormAction()
		{
			$formName = $this->getArgument(1);

			$appName = $this->getApplication()->getApplicationName();
			
			list($x1, $x2) = explode(':', $formName.':');
			$isCore = (strToLower($x1) == '@core');
			if($isCore)
			{
				$formName = $x2;

				$gDir = $this->getApplication()->getDir('Core/Config/Form');
				$formDir = $this->getApplication()->getDir('Core/Form');
				$formBaseDir = $this->getApplication()->getDir('Core/Form/Base');
				$nmSpace1 = 'The\Core\Form\Base';
				$nmSpace2 = 'The\Core\Form';
			}else
			{
				$gDir = $this->getApplication()->getApplicationDir('Config/Form');
				$formDir = $this->getApplication()->getApplicationDir('Form');
				$formBaseDir = $this->getApplication()->getApplicationDir('Form/Base');
				$nmSpace1 = 'The\App\\'.$appName.'\Form\Base';
				$nmSpace2 = 'The\App\\'.$appName.'\Form';
				$nmSpace3 = 'The\App\\'.$appName.'\Module\\$className';
			}

			$fileReader = $this->getApplication()->getFileReader();		

			$fileReader->createDir($formDir);
			$fileReader->createDir($formBaseDir);

			$yaml = $fileReader->read( $gDir.$formName.'.yml' );

			$ar = Yaml::decode( $yaml );

			$name = Util::arrayGet($ar, 'name');
			$className = $name;

			#$x_extends = Util::arrayGet()
			$x_name = UtiL::phptoString($name);
			$x_id = UtiL::phptoString( Util::arrayGet($ar, 'id', $name) );
			$x_action = UtiL::phptoString( Util::arrayGet($ar, 'action') );
			$x_method = UtiL::phptoString( Util::arrayGet($ar, 'method', Form::METHOD_POST) );

			$data = '';

			$br = "\n";
			$tab = "\t";

			if(isset($ar['templating_file_name']))
			{
				$data .= '$this->setTemplatingFileName( '.UtiL::phptoString($ar['templating_file_name']).' );'.$br.$br;
			}

			if(isset($ar['submit_button_caption']))
			{
				$data .= '$this->setSubmitButtonCaption( '.UtiL::phptoString($ar['submit_button_caption']).' );'.$br.$br;
			}

			if(sizeof( ($attributes = Util::arrayGet($ar, 'attributes', array())) ) > 0)
			{
				foreach($attributes as $attributeName => $attributeValue)
				{
					$data .= '$this->setAttribute(\''.$attributeName.'\', '.Util::phptoString($attributeValue).');'.$br;
				}
				$data .= $br;
			}

			$data .= $this->getFormData($ar['field_groups']);

			$data = preg_replace('/((.*)+)/i', "\t\t".'$1', $data);

			preg_match('/^(\w+)Form$/i', $formName, $m);
			$formSmallName = ucfirst($m[1]);
			$baseFormClassName = $formSmallName.'BaseForm';

			$nmSpace3 = $isCore ? 'The\Core\Form\Base\\'.$baseFormClassName : 'The\App\\'.$appName.'\Form\Base\\'.$baseFormClassName;

			$res = <<<EOD
<?php
namespace $nmSpace1;

use The\Core\Form;

class $baseFormClassName extends Form
{
	public function afterConstruct()
	{
		parent::afterConstruct();

		\$this
			->setName($x_name)
			->setAction($x_action)
			->setMethod($x_method)
		;

$data
	}
}
EOD;

			$fileReader->write( $formBaseDir.$baseFormClassName.'.php', $res );

			$formFile = $formDir.$formName.'.php';

			if(!$fileReader->exists($formFile))
			{


				$formRes = <<<EOD
<?php
namespace $nmSpace2;

use $nmSpace3;

class $formName extends $baseFormClassName
{

}
EOD;

				$fileReader->write($formFile, $formRes);
			}

			return 1;
		}


		public function buildTableAction()
		{
			$tableName = $this->getArgument(1);

			$appName = $this->getApplication()->getApplicationName();
			
			list($x1, $x2) = explode(':', $tableName.':');
			$isCore = (strToLower($x1) == '@core');
			if($isCore)
			{
				$tableName = $x2;

				$gDir = $this->getApplication()->getDir('Core/Config/Table');
				$tableDir = $this->getApplication()->getDir('Core/Table');
				$tableBaseDir = $this->getApplication()->getDir('Core/Table/Base');
				$nmSpace1 = 'The\Core\Table\Base';
				$nmSpace2 = 'The\Core\Table';
			}else
			{
				$gDir = $this->getApplication()->getApplicationDir('Config/Table');
				$tableDir = $this->getApplication()->getApplicationDir('Table');
				$tableBaseDir = $this->getApplication()->getApplicationDir('Table/Base');
				$nmSpace1 = 'The\App\\'.$appName.'\Table\Base';
				$nmSpace2 = 'The\App\\'.$appName.'\Table';
			}

			$fileReader = $this->getApplication()->getFileReader();		

			$fileReader->createDir($tableDir);
			$fileReader->createDir($tableBaseDir);

			$yaml = $fileReader->read( $gDir.$tableName.'.yml' );

			$ar = Yaml::decode( $yaml );

			$name = Util::arrayGet($ar, 'name');
			$chunked = Util::arrayGet($ar, 'chunked', false);
			$className = $name;

			$x_name = UtiL::phptoString($name);

			$data = '';

			$br = "\n";
			$tab = "\t";

			$listActions = Util::arrayGet($ar, 'list_actions', array());
			$elementActions = Util::arrayGet($ar, 'element_actions', array());
			$elementMultipleActions = Util::arrayGet($ar, 'element_multiple_actions', array());

			if(sizeof($listActions) > 0)
			{
				$data .= '$this->setListActions('.Util::phptoString($listActions).');'.$br;
			}

			if(sizeof($elementActions) > 0)
			{
				$data .= '$this->setElementActions('.Util::phptoString($elementActions).');'.$br;
			}

			if(sizeof($elementMultipleActions) > 0)
			{
				$data .= '$this->setElementMultipleActions('.Util::phptoString($elementMultipleActions).');'.$br;
			}

			$data .= '$this->setChunked('. Util::phptoString($chunked) .');'.$br.$br;

			if(isset($ar['templating_file_name']))
			{
				$data .= '$this->setTemplatingFileName( '.UtiL::phptoString($ar['templating_file_name']).' );'.$br.$br;
			}

			if(isset($ar['by_page']))
			{
				$data .= '$this->setByPage(' . Util::phpToString($ar['by_page']) . ');'.$br.$br;
			}

			if(sizeof( ($attributes = Util::arrayGet($ar, 'attributes', array())) ) > 0)
			{
				foreach($attributes as $attributeName => $attributeValue)
				{
					$data .= '$this->setAttribute(\''.$attributeName.'\', '.Util::phptoString($attributeValue).');'.$br;
				}
				$data .= $br;
			}

			if(sizeof( ($sortingFields = Util::arrayGet($ar, 'sorting_fields', array())) ) > 0)
			{
				foreach($sortingFields as $sortingFieldName)
				{
					$data .= '$this->addSortingField(\''.$sortingFieldName.'\');'.$br;
				}
				$data .= $br;
			}

			if(sizeof( ($filterFields = Util::arrayGet($ar, 'filter_fields', array())) ) > 0)
			{
				$data .= $this->getFormData( array(
					'default' => array(
						'caption' => 'Default',
						'fields' => $ar['filter_fields'],
					)
				), 'this->getFilterForm()', true);
				$data .= $br;
			}

			$data .= $this->getFormData( array(
				'default' => array(
					'caption' => 'Default',
					'fields' => $ar['fields'],
				)
			), 'this', true);

			$data = preg_replace('/((.*)+)/i', "\t\t".'$1', $data);

			preg_match('/^(\w+)Table$/i', $tableName, $m);
			$tableSmallName = ucfirst($m[1]);
			$baseTableClassName = $tableSmallName.'BaseTable';

			$nmSpace3 = $isCore ? 'The\Core\Table\Base\\'.$baseTableClassName : 'The\App\\'.$appName.'\Table\Base\\'.$baseTableClassName;

			$res = <<<EOD
<?php
namespace $nmSpace1;

use The\Core\Table;

class $baseTableClassName extends Table
{
	public function afterConstruct()
	{
		parent::afterConstruct();

		\$this
			->setName($x_name)
		;

$data
	}
}
EOD;

			$fileReader->write( $tableBaseDir.$baseTableClassName.'.php', $res );

			$tableFile = $tableDir.$tableName.'.php';

			if(!$fileReader->exists($tableFile))
			{
				

				$tableRes = <<<EOD
<?php
namespace $nmSpace2;

use $nmSpace3;

class $tableName extends $baseTableClassName
{

}
EOD;

				$fileReader->write($tableFile, $tableRes);
			}

			return 1;
		}


		public function buildModuleAction()
		{
			$moduleName = $this->getArgument(1);

			$appName = $this->getApplication()->getApplicationName();
			
			list($x1, $x2) = explode(':', $moduleName.':');
			$isCore = (strToLower($x1) == '@core');
			if($isCore)
			{
				$moduleName = $x2;

				$gDir = $this->getApplication()->getDir('Core/Config/Module');
				$moduleDir = $this->getApplication()->getDir('Core/Module');
				$controllerDir = $this->getApplication()->getDir('Core/Controller');
				$nmSpace1 = 'The\Core\Module';
				$nmSpace2 = 'The\Core\Controller';
			}else
			{
				$gDir = $this->getApplication()->getApplicationDir('Config/Module');
				$moduleDir = $this->getApplication()->getApplicationDir('Module');
				$controllerDir = $this->getApplication()->getApplicationDir('Controller');
				$nmSpace1 = 'The\App\\'.$appName.'\Module';
				$nmSpace2 = 'The\App\\'.$appName.'\Controller';
			}

			$fileReader = $this->getApplication()->getFileReader();			
			
			$fileReader->createDir($moduleDir);
			$fileReader->createDir($controllerDir);
			
			$yaml = $fileReader->read( $gDir.$moduleName.'.yml' );

			$ar = Yaml::decode( $yaml );

			$br = "\n";
			$tab = "\t";

			$name = Util::arrayGet($ar, 'name');
			$fields = Util::arrayGet($ar, 'fields', array());
			$actions = Util::arrayGet($ar, 'actions', array());
			$sortingFields = Util::arrayGet($ar, 'sorting_fields', array());
			$listActions = Util::arrayGet($ar, 'list_actions', array());
			$elementActions = Util::arrayGet($ar, 'element_actions', array());
			$elementMultipleActions = Util::arrayGet($ar, 'element_multiple_actions', array());
			
			$tableName = Util::arrayGet($ar, 'table_name');
			if($tableName)
			{
				$tableName = 'protected $tableName = '.Util::phptoString($tableName).';';
			}else
			{
				$tableName = '';
			}

			$modelName = Util::arrayGet($ar, 'model_name');
			if($modelName)
			{
				$modelName = 'protected $modelName = '.Util::phptoString($modelName).';';
			}else
			{
				echo CLI::out('[model_name] is required field', CLI::OUT_ERROR);
				die;
			}

			$byPage = (int)Util::arrayGet($ar, 'by_page', array());
			if($byPage)
			{
				$byPage = 'protected $byPage = '.$byPage.';';
			}else
			{
				$byPage = '';
			}

			$className = $name;

			$nmSpace3 = $isCore ? 'The\Core\Module\\'.$className : 'The\App\\'.$appName.'\Module\\'.$className;

			$data = $this->getFormData(array(
				'default' => array(
					'caption' => 'Default',
					'fields' => $fields,
				)
			), 'this->getForm()');

			$data = preg_replace('/((.*)+)/i', "\t\t".'$1', $data);

			foreach($sortingFields as $fieldName)
			{
				$data .= '$this->addSortingField('.Util::phptoString($fieldName).');'.$br;
			}

			if(sizeof($listActions) > 0)
			{
				$data .= '$this->setListActions('.Util::phptoString($listActions).');'.$br;
			}

			if(sizeof($elementActions) > 0)
			{
				$data .= '$this->setElementActions('.Util::phptoString($elementActions).');'.$br;
			}

			if(sizeof($elementMultipleActions) > 0)
			{
				$data .= '$this->setElementMultipleActions('.Util::phptoString($elementMultipleActions).');'.$br;
			}

			foreach($actions as $actionName => $actionData)
			{
				$fields = Util::arrayGet($actionData, 'fields', array());
				$autoValue = Util::arrayGet($actionData, 'auto_value', array());
				$params = Util::arrayGet($actionData, 'params', array());
				$data .= '$this->addAction('.Util::phptoString($actionName).', '.Util::phptoString($params).', '.Util::phptoString($fields).', '.Util::phptoString($autoValue).');'.$br;
			}

			$res = <<<EOD
<?php
namespace $nmSpace1;

use The\Core\Module;

class $className extends Module
{
	$tableName
	$modelName
	$byPage

	public function afterConstruct()
	{
		parent::afterConstruct();

		\$this->setName('$moduleName');

$data
	}
}
EOD;

			$fileReader->write( $moduleDir.$moduleName.'.php', $res );

			$controllerClassName = $moduleName.'Controller';

			$controllerFile = $controllerDir.$controllerClassName.'.php';

			if(!$fileReader->exists($controllerFile))
			{

				$controllerRes = <<<EOD
<?php
namespace $nmSpace2;

use $nmSpace3;

class $controllerClassName extends $className
{

}
EOD;

				$fileReader->write($controllerFile, $controllerRes);
			}

			preg_match('/^(\w+)Module$/i', $moduleName, $m);
			$moduleSmallName = strtolower($m[1]);
			$moduleRouteName = 'module_'.$moduleSmallName;

			$route = <<<EOD
		$moduleRouteName:
			pattern: /$moduleSmallName/{action}/{elementID}
			callback: [$moduleName, route]
			requirements:
				action: \w+
				elementID: \d+
			defaults:
				action: list
				elementID: 0
EOD;

			echo $route.Util::br();

			return 1;
		}

		public function buildAction()
		{
			$app = $this->getApplication();

			$br = "\n";
			$comm = function($s) use ($br)
			{
				return $br.' /********************** ['.$s.'] **********************/ '.$br.$br;
			};

			$makeFile = function($fn, $fileName, $dirName) use ($comm, $br)
			{
				$r = '';

				$fp = file_get_contents($fn);

				$fp = trim($fp);

				if($fp[0] != '<' && $fp[1] != '?')
				{
					return;
				}

				$fp = str_replace(array("\r\n", "\r"), "\n", $fp);

				$ex = explode("\n", $fp, 2);
				$ex[0] = str_replace( array('<?php', '<?'), '', $ex[0] );
				$fp = implode("\n", $ex);

				if( preg_match('/namespace\s+(.*);/i', $fp, $m) )
				{
					#var_dump($fileName.' has "'.$m[1].'" namespace');
					$fp = preg_replace('/namespace\s+(.*);/i', 'namespace $1{', $fp);
					$fp .= "\n".'}';
				}else
				{
					$fp = 'namespace The{'.$fp;
					$fp .= '}';
					#var_dump($fileName.' has no namespace');
				}

				$r .= $comm('File "'.$fileName.'" in "'.$dirName.'"');
				$r .= $fp.$br.$br;

				return $r;
			};

			$r = '';

			$coreDir = $app->getDir('Core');
			$appDir = $app->getApplicationDir();

			Util::pr($coreDir);
			Util::pr($appDir);

			$fx = function($fx, $dir, $subDir = '')
			{
				$res = array();
				$dh = scandir($dir.$subDir);
				foreach($dh as $f)
				{
					if($f[0] == '.' || $f[0] == '_' || $f[strlen($f)-1] == '~') continue;
					$fl = $dir.$subDir.$f;

					if(is_file($fl))
					{
						$pi = pathinfo($fl);
						if(!isset($pi['extension']) || $pi['extension'] != 'php')
						{
							continue;
						}
						$res[] = $subDir.$f;
					}else
					{
						$r = $fx($fx, $dir, $subDir.$f.'/');
						$res = array_merge($r, $res);
					}
				}

				return $res;
			};

			$coreRes = $fx($fx, $coreDir);
			$appRes = $fx($fx, $appDir);

			$r .= '<?php'."\n";

			$r .= $makeFile($app->getDir('').'bootstrap.php', 'bootstrap.php', 'The');

			foreach($coreRes as $file)
			{
				$r .= $makeFile($coreDir.$file, $file, 'Core');
			}

			foreach($appRes as $file)
			{
				$r .= $makeFile($appDir.$file, $file, 'Core');
			}

			$app->getFileReader()->write($appDir.'builded_bootstrap.php', $r);

			return;

			$r = '';

			$r .= '<?php'.$br;

			{#base bootstrap
				$r .= $makeFile($app->getDir('Core').'_base_bootstrap.php', '_base_bootstrap.php', 'Core');
			}

			{#core
				$dr = $app->getDir('Core');
				$dh = scandir($dr);
				foreach($dh as $f)
				{
					if($f[0] == '.' || $f[0] == '_' || $f[strlen($f)-1] == '~' || !is_file($dr.$f)) continue;

					$r .= $makeFile($dr.$f, $f, 'Core');
				}
			}

			{#app
				#./Application.php
				$r .= $makeFile($app->getDir('App').'Application.php', 'Application.php', 'App');

				#./Schema/*
				$dr = $app->getDir('App/Schema');
				$dh = scandir($dr);
				foreach($dh as $f)
				{
					if($f[0] == '.' || $f[0] == '_' || $f[strlen($f)-1] == '~' || !is_file($dr.$f)) continue;

					$r .= $makeFile($dr.$f, $f, 'App/Schema');
				}

				#./Model/*
				$dr = $app->getDir('App/Model');
				$dh = scandir($dr);
				foreach($dh as $f)
				{
					if($f[0] == '.' || $f[0] == '_' || $f[strlen($f)-1] == '~' || !is_file($dr.$f)) continue;

					$r .= $makeFile($dr.$f, $f, 'App/Model');
				}

				#./Repository/*
				$dr = $app->getDir('App/Repository');
				$dh = scandir($dr);
				foreach($dh as $f)
				{
					if($f[0] == '.' || $f[0] == '_' || $f[strlen($f)-1] == '~' || !is_file($dr.$f)) continue;

					$r .= $makeFile($dr.$f, $f, 'App/Repository');
				}

				#./Controller/*
				$dr = $app->getDir('App/Controller');
				$dh = scandir($dr);
				foreach($dh as $f)
				{
					if($f[0] == '.' || $f[0] == '_' || $f[strlen($f)-1] == '~') continue;
					if(!is_file($dr.$f))
					{
						$dr2 = $dr.$f.'/';
						$dh2 = scandir($dr2);
						foreach($dh2 as $f2)
						{
							if($f2[0] == '.' || $f2[0] == '_' || $f2[strlen($f2)-1] == '~' || !is_file($dr2.$f2)) continue;

							$r .= $makeFile($dr2.$f2, $f2, 'App/Controller/'.$f);
						}
					}else
					{
						$r .= $makeFile($dr.$f, $f, 'App/Controller');
					}
				}
			}

			file_put_contents($app->getDir('App').'bootstrap.php', $r);
		}

		public function createModuleAction()
		{
			$tableName = $this->getArgument(1);
			$moduleName = $this->getArgument(2);
			$needBuild = (bool)$this->getArgument('build');
			$xModuleName = $moduleName.'Module';

			$app = $this->getApplication();

			$fileReader = $app->getFileReader();	

			$appName = $app->getApplicationName();
			
			list($x1, $x2) = explode(':', $moduleName.':');
			$isCore = (strToLower($x1) == '@core');
			if($isCore)
			{
				$moduleName = $x2;
				$gDir = $app->getDir('Core/Config/Module');
				$gBaseModelDir = $app->getDir('Core/Model/Base');
				$gModelDir = $app->getDir('Core/Model');
				$gBaseRepositoryDir = $app->getDir('Core/Repository/Base');
				$gRepositoryDir = $app->getDir('Core/Repository');
				$nmSpace1 = 'The\Core\Model';
				$nmSpaceRepBase = 'The\Core\Repository\Base';
				$nmSpaceRep = 'The\Core\Repository';
			}else
			{
				$gDir = $app->getApplicationDir('Config/Module');
				$gBaseModelDir = $app->getApplicationDir('Model/Base');
				$gModelDir = $app->getApplicationDir('Model');
				$gRepositoryDir = $app->getApplicationDir('Repository');
				$gBaseRepositoryDir = $app->getApplicationDir('Repository/Base');
				$nmSpace1 = 'The\App\\'.$appName.'\Model';
				$nmSpaceRepBase = 'The\App\\'.$appName.'\Repository\Base';
				$nmSpaceRep = 'The\App\\'.$appName.'\Repository';
			}

			$db = $app->getDB();

			$r = $db->describeTable($tableName);

			$equalArguments = array(
				'id' => array(
					'type' => 'Integer',
				),
				'active' => array(
					'type' => 'Boolean',
				),
				'enable' => array(
					'type' => 'Boolean',
				),
				'enabled' => array(
					'type' => 'Boolean',
				),
				'password' => array(
					'type' => 'Password',
				),
				'text' => array(
					'type' => 'Editor',
				),
				'description' => array(
					'type' => 'Editor',
				),
				'html' => array(
					'type' => 'Editor',
				),
				'comment' => array(
					'type' => 'Text',
				),
				'data' => array(
					'type' => 'Text',
				),
			);

			$prefixArguments = array(
				'is' => array(
					'type' => 'Boolean',
				),
				'need' => array(
					'type' => 'Boolean',
				),
				'has' => array(
					'type' => 'Boolean',
				),
			);

			$postfixArguments = array(
				'at' => array(
					'type' => 'DateTime',
				),
				'id' =>function($field)
				{
					return array(
						'type' => 'RepositorySelect',
						'params' => array(
							'repository' => '',
							'method' => '',
							'fields' => array(
								'value' => 'id',
								'caption' => 'title',
							),
						),
					);
				},
			);

			$anyArguments = array(
				'time' => array(
					'type' => 'DateTime',
				),
				'date' => array(
					'type' => 'DateTime',
				),
			);

			$mysqlTypes = array(
				'int' => array(
					'type' => 'Integer',
				),
				'tinyint' => array(
					'type' => 'Integer',
				),
				'decimal' => array(
					'type' => 'Float',
				),
				'float' => array(
					'type' => 'Float',
				),
				'double' => array(
					'type' => 'Float',
				),
				'char' => array(
					'type' => 'String',
				),
				'varchar' => array(
					'type' => 'String',
				),
				'text' => array(
					'type' => 'Text',
				),
				'datetime' => array(
					'type' => 'DateTime',
				),
				'date' => array(
					'type' => 'Date',
				),
			);

			$mysqlTypesAuto = array(
				'datetime' => '^now',
				'date' => '^now',
			);

			$fields = array();

			$fx = function($field, $tx)
			{
				if(is_callable($tx))
				{
					return $tx($field);
				}else
				{
					return $tx;
				}
			};

			$fieldsList = array();
			$createAutoValues = array();
			$updateAutoValues = array();

			foreach($r as $field)
			{
				$name = $field->getField();
				$type = $field->getType();

				if($name != 'id')
				{

				}
				$fieldsList[] = $name;

				$ex = explode('_', $name);
				$c1 = reset($ex);
				$c2 = $ex[sizeof($ex)-1];

				preg_match('/(\w+)(\((.*)\)|)(\s?(.*)|)/i', $type, $m);
				list(,$tp,,$len,,$add) = $m;
				$tp = strTolower($tp);

				$tx = false;

				if(isset($equalArguments[$name]))
				{
					$tx = $equalArguments[$name];
				}elseif(isset($prefixArguments[$c1]))
				{
					$tx = $prefixArguments[$c1];
				}elseif(isset($postfixArguments[$c2]))
				{
					$tx = $postfixArguments[$c2];
				}else
				{
					$ar1 = array_keys($anyArguments);
					$ar2 = $ex;
					$int = array_intersect($ar1, $ar2);
					if(sizeof($int) > 0)
					{
						$key = reset($int);
						$tx = $anyArguments[$key];
					}else
					{
						if(isset($mysqlTypes[$tp]))
						{
							$tx = $mysqlTypes[$tp];
						}
					}
				}

				if(!$tx)
				{
					print 'Can\'t detect field "'.$name.'" type, use String type'.PHP_EOL;
					$tx = array(
						'type' => 'String',
					);
				}

				if(in_array($tp, $mysqlTypesAuto))
				{
					$createAutoValues[$name] = $mysqlTypesAuto[$tp];
					$updateAutoValues[$name] = $mysqlTypesAuto[$tp];
				}

				$fields[$name] = $fx($field, $tx);
				$fields[$name]['caption'] = $name;
			}

			$moduleFullName = ucfirst($moduleName).'Module';

			$modelName = $moduleName;
			$modelFullName = $modelName.'Model';
			$repositoryFullName = $modelName . 'Repository';
			$baseRepositoryFullName = $modelName . 'BaseRepository';
			$modelData = '';


			$fn = function($s)
			{
				return implode('', array_map(function($x){ return ucfirst($x); }, explode('_', $s)));
			};
			$fn2 = function($s)
			{
				return implode('', array_map(function($x){ static $i=0; $i++; return ($i == 1) ? strtolower($x) : ucfirst($x); }, explode('_', $s)));
			};

			$repDoc = "/**"."\n";
			$repDoc .= " * Class $repositoryFullName". "\n";
			$repDoc .= " * @package $nmSpaceRep". "\n";

			foreach($fieldsList as $fieldName)
			{
				$funcName = 'set'.$fn($fieldName);
				$funcName2 = 'get'.$fn($fieldName);
				$varName = '$'.$fn2($fieldName);

				$modelData .= "\t\t/**"."\n";
				$modelData .= "\t\t * @param $varName"."\n";
				$modelData .= "\t\t * @return $modelFullName"."\n";
				$modelData .= "\t\t */"."\n";
				$modelData .= "\t\tpublic function ".$funcName.'('.$varName.')'."\n";
				$modelData .= "\t\t".'{'."\n";
				$modelData .= "\t\t\t".'return parent::'.$funcName.'('.$varName.');'."\n";
				$modelData .= "\t\t".'}'."\n";

				$modelData .= "\n";

				$modelData .= "\t\tpublic function ".$funcName2.'()'."\n";
				$modelData .= "\t\t".'{'."\n";
				$modelData .= "\t\t\t".'return parent::'.$funcName2.'();'."\n";
				$modelData .= "\t\t".'}'."\n";

				$modelData .= "\n";

				$repDoc .= " * \n";
				$repDoc .= " * @method {$nmSpace1}\\{$modelFullName}[] findBy" . $fn($fieldName) . "(\\mixed \$value, \\int \$limit = null)" . "\n";
				$repDoc .= " * @method {$nmSpace1}\\{$modelFullName} findOneBy" . $fn($fieldName) . "(\\mixed \$value)" . "\n";

			}

			$repDoc .= " */";
			$modelRes = <<<EOD
<?php
	namespace $nmSpace1;

	use The\Core\Util;
	use The\Core\Model;

	class $modelFullName extends Model
	{
		const TABLE_NAME = '$tableName';

$modelData
	}
EOD;

			$baseRepositoryRes = <<<BREP
<?php

namespace $nmSpaceRepBase;

use The\Core\Repository;

$repDoc
class $baseRepositoryFullName extends Repository
{
	protected \$modelName = '$modelName';
}

BREP;

			$repositoryRes = <<<REP
<?php

namespace $nmSpaceRep;

use $nmSpaceRepBase\\$baseRepositoryFullName;

class $repositoryFullName extends $baseRepositoryFullName
{
	public function testMethod()
	{
		\$db = \$this->getDB();

		/** @var \$query \The\Core\Query */
		\$query = \$db->createQueryFromModel(\$this->getModelName(), 't');

		\$query->select('*');

		/** @var \$res \\$nmSpace1\\{$modelFullName}[] */
		\$res = \$db->fetchObjects(\$query);

		return \$res;
	}
}

REP;

			$fileReader->write($gBaseRepositoryDir.$baseRepositoryFullName . '.php', $baseRepositoryRes);

			if (!$fileReader->exists($gRepositoryDir.$repositoryFullName . '.php')){
				$fileReader->write($gRepositoryDir.$repositoryFullName . '.php', $repositoryRes);
			}

			$x = Yaml::encode( array(
				'name' => $moduleFullName,
				'table_name' => $tableName,
				'model_name' => $modelName,
				'fields' => $fields,
				'list_actions' => array(),
				'element_actions' => array(),
				'element_multiple_actions' => array(),
				'sorting_fields' => $fieldsList,
				'actions' => array(
					'list' => array(
						'fields' => $fieldsList,
					),
					'element' => array(
						'fields' => $fieldsList,
					),
					'create' => array(
						'fields' => $fieldsList,
						'auto_value' => $createAutoValues,
					),
					'update' => array(
						'fields' => $fieldsList,
						'auto_value' => $updateAutoValues,
					),
				),
			) );

			$fileReader->write( $gModelDir.$modelFullName.'.php', $modelRes );

			if(!$fileReader->exists($gDir.$moduleFullName.'.yml'))
			{
				$fileReader->write( $gDir.$moduleFullName.'.yml', $x );
			}

			if($needBuild)
			{
				$this->setArgument(1, $xModuleName);
				$r = $this->buildModuleAction();
			}

			return 1;
			#Util::pr($fields);
		}
	}