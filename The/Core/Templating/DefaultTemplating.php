<?php
	namespace The\Core\Templating;

	use The\Core\Util;
	use The\Core\Templating;
	use The\Core\TemplateEngine\TemplateEngine;

	class DefaultTemplating extends Templating
	{
		protected $compiler = array();

		protected function getCompiler($dir)
		{
			if(!isset($this->compiler[$dir]))
			{
				$this->compiler[$dir] = new TemplateEngine($dir, null);
			}
			return $this->compiler[$dir];
		}

		public function render($templateFileName, array $variables = array())
		{
			/**
			@TODO kostyle
			*/
			$templateDir = $this->getApplication()->getApplicationDir('View');

			$compiler = $this->getCompiler($templateDir);

			return $compiler->render($templateFileName, $variables);
		}
	}