<?php
	namespace The\Core\Templating;

	use The\Core\Templating;

	class DefaultTemplating extends Templating
	{
		protected $compiler;

		protected function getCompiler()
		{
			if(!$this->compiler)
			{
				$this->compiler = new \Phalcon\Mvc\View\Engine\Volt\Compiler();

				/**
				@TODO options from config
				*/
				$this->compiler->setOptions(array(
					#Путь для записи скомпилированных шаблонов
					'compiledPath' => '/tmp/',

					#Дополнительное расширение, добавляемое к скомпилированным PHP-файлам
	        		'compiledExtension' => '.compiled',

	        		#Если Phalcon должны проверять, существуют ли различия между файлом шаблона и его скомпилированным результатом
	        		'stat' => true,

	        		#Указывает Volt, должны ли шаблоны собираться на каждый запрос, или только тогда, когда они изменяются
	        		'compileAlways' => true,

	        		#Volt заменяет разделители папок / и \ этим разделителем для создания одного файла в папке скомпилированных PHP файлов
	        		#'compiledSeparator' => '%%',

	        		#Позволяет добавлять префикс к шаблонам в папке скомпилированных PHP файлов
	        		#'prefix' => null,
	        	));

				#addFunction
				#addFilter

				#var_dump($r, $compiler->getCompiledTemplatePath());
			}
			return $this->compiler;
		}

		public function render($templateFileName, array $variables = array())
		{
			$fileName = $this->getApplication()->getApplicationDir('View').str_replace(':', '/', $templateFileName).'.html.twig';

			$template = $this->getApplication()->getFileReader()->read($fileName);

			$ii = preg_match_all('/\{\%[\s+]?extends[\s+]?(.*)[\s+]?\%\}/i', $template, $m);
			for($i=0; $i<$ii; $i++)
			{
				$statement = $m[0][$i];
				$data = $m[1][$i];
				$dataEx = trim(str_replace(':', '/', $data)).'.html.twig';
				$filePath = $this->getApplication()->getApplicationDir('View').$dataEx;
				$newStatement = '{% extends \''.$filePath.'\' %}';
				$template = str_replace($statement, $newStatement, $template);
			}

			$template = preg_replace('/\<\?.*\?\>/si', '', $template);
			$template = preg_replace('/\<\?.*/si', '', $template);

			$cachedFile = '/dev/shm/'.basename($fileName).'.php';
			$compiledTemplate = $this->getCompiler()->compileString($template);

			extract($variables);

			ob_start();
			eval('?>'.$compiledTemplate);
			#require $cachedFile;
			$ob = ob_get_clean();

			return $ob;
		}
	}