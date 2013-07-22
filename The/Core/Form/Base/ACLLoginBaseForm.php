<?php
namespace The\Core\Form\Base;

use The\Core\Form;

class ACLLoginBaseForm extends Form
{
	public function afterConstruct()
	{
		parent::afterConstruct();

		$this
			->setName('ACLLoginForm')
			->setAction(null)
			->setMethod('POST')
		;

		$fieldGroup = $this->addFieldGroup( '_default', 'Авторизация' );		
			$fieldClass = $this->getApplication()->getClassName('String', 'Field');		
			$fieldObject = new $fieldClass('_login', 'Логин', null);		
			$field = $this->addField($fieldObject, $fieldGroup);		
			$field->applyParams(array(			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
			$fieldClass = $this->getApplication()->getClassName('Password', 'Field');		
			$fieldObject = new $fieldClass('_password', 'Пароль', null);		
			$field = $this->addField($fieldObject, $fieldGroup);		
			$field->applyParams(array(			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
			$fieldClass = $this->getApplication()->getClassName('Boolean', 'Field');		
			$fieldObject = new $fieldClass('_remember_me', 'Запомнить меня', null);		
			$field = $this->addField($fieldObject, $fieldGroup);		
			$field->applyParams(array(			
			));			
			$field->setIsRequired(false);			
			$field->setIsRepeated(false);			
					
		
		
		
	}
}