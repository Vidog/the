<?php
namespace The\App\Example\Form\Base;

use The\Core\Form;

class TestBaseForm extends Form
{
	public function afterConstruct()
	{
		parent::afterConstruct();

		$this
			->setName('TestForm')
			->setAction(null)
			->setMethod('POST')
		;

		$fieldGroup = $this->addFieldGroup( 'field_group_1', 'My field group 1' );		
			$fieldClass = $this->getApplication()->getClassName('String', 'Field');		
			$fieldObject = new $fieldClass('a', 'Field A', 'aaa');		
			$field = $this->addField($fieldObject, $fieldGroup);		
			$field->applyParams(array(			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
			$fieldClass = $this->getApplication()->getClassName('RepositorySelect', 'Field');		
			$fieldObject = new $fieldClass('b', 'Mobile Vendors', null);		
			$field = $this->addField($fieldObject, $fieldGroup);		
			$field->applyParams(array(			
				'repository' => 'Test',			
				'method' => 'getMobileVendors',			
				'fields' => array(			
					'value' => 'id',			
					'caption' => 'title',			
				),			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
		
		
	}
}