<?php
	namespace The\Core\TemplateEngine;

	class TemplateRawInput
	{
		private $data;

		function __construct($data)
		{
			$this->data = $data;
		}

		function __toString()
		{
			return ''.$this->data;
		}
	}