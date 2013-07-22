<?php
	namespace The\Core\TemplateEngine;

	use The\Core\Util;
	use The\Core\TemplateEngine\TemplateAdapter;

	class TemplateCompiledAdapter extends TemplateAdapter
	{
		function run($template, $variables)
		{
			$this->template = $template;
			$this->variables = array_merge($this->env->globalVariables, $variables);

			try
			{
				ob_start();
				eval('?>' . $this->template);
				$tpl = ob_get_clean();
			} catch(\Exception $e)
			{
				/**
				@TODO kostyle
				*/
				Util::getApplication()->onException($e);
				$tpl = '';
			}

			return $tpl;
		}
	}