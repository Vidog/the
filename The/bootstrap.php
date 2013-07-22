<?php

	spl_autoload_register(function ($className)
	{
		static $namespaceMappings = array(
			#'Doctrine\\' => 'vendor\\Doctrine\\',
			#'Zend\\' => 'vendor\\Zend\\'
		);

		foreach ($namespaceMappings as $from => $to)
		{
			$className = str_replace($from, $to, $className);
		}

		$fileName = dirname(__FILE__) . '/../' . str_replace('\\', '/', $className) . '.php';
		if (file_exists($fileName))
		{
			require $fileName;
		}
	});