<?php
	namespace The\Core\TemplateEngine;

	use The\Core\Util;

	class Template
	{
		protected $template;
		protected $templateAdapter;
		protected $templateAdapterClass;
		protected $variables = array();

		function init($env, $template, $templateAdapterClass, $variables = array())
		{
			$this->template = $template;
			$this->templateAdapterClass = $templateAdapterClass;
			$this->variables = $variables;
			$this->templateAdapter = new $templateAdapterClass($env);
		}

		function __toString()
		{
			return $this->templateAdapter->run($this->template, $this->variables);
		}
	}