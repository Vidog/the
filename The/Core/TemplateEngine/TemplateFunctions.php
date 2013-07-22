<?php
	namespace The\Core\TemplateEngine;

	class TemplateFunctions
	{
		public static function test($a)
		{
			return $a * 2;
		}

		public static function raw($s)
		{
			return new TemplateRawInput($s);
		}

		public static function range($a, $b)
		{
			$arr = array();
			for($i=$a; $i<=$b; $i++)
			{
				$arr[] = $i;
			}
			return $arr;
		}
	}