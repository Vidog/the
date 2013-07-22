<?php
	namespace The\Core\Field;

	use The\Core\Field;

	class FileField extends Field
	{
		protected $uploaderName;

		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setTemplatingFileName('@:Field:file')
			;
		}

		public function setUploaderName($uploaderName)
		{
			$this->uploaderName = $uploaderName;
		
			return $this;
		}
		
		public function getUploaderName()
		{
			return $this->uploaderName;
		}
	}