<?php
	namespace The\Core\Field;

	use The\Core\Field\FileField;

	class ImageFileField extends FileField
	{
		public function afterConstruct()
		{
			parent::afterConstruct();

			$this
				->setTemplatingFileName('@:Field:image_file')
			;
		}
	}