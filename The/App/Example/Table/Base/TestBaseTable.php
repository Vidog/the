<?php
namespace The\App\Example\Table\Base;

use The\Core\Table;

class TestBaseTable extends Table
{
	public function afterConstruct()
	{
		parent::afterConstruct();

		$this
			->setName('TestTabl')
		;

		$this->setElementMultipleActions(array(		
			'add_items' => array(		
				'caption' => 'Добавить',		
				'type' => 'js',		
				'url' => 'alert()',		
			),		
		));		
		$this->setChunked(false);		
		
		$this->addSortingField('id');		
		$this->addSortingField('title');		
		$this->addSortingField('date');		
		
			$fieldClass = $this->getApplication()->getClassName('String', 'Field');		
			$fieldObject = new $fieldClass('id', 'ID', null);		
			$field = $this->addField($fieldObject);		
			$field->applyParams(array(			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
			$fieldClass = $this->getApplication()->getClassName('String', 'Field');		
			$fieldObject = new $fieldClass('title', 'Title', null);		
			$field = $this->addField($fieldObject);		
			$field->applyParams(array(			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
			$fieldClass = $this->getApplication()->getClassName('String', 'Field');		
			$fieldObject = new $fieldClass('date', 'Date', null);		
			$field = $this->addField($fieldObject);		
			$field->applyParams(array(			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
		
		
	}
}