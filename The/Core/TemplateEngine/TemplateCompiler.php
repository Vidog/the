<?php
	namespace The\Core\TemplateEngine;

	class TemplateCompiler
	{
		public function makeStatement($expr)
		{
			return '<?'.$expr.'?>';
		}

		public function makeEcho($expr)
		{
			return '<?=$this->makeEcho('.$expr.')?>';
		}

		public function makeIf($blockName, $expr, $data)
		{				
			$tpl = '';
			$tpl .= '<? '.$blockName.'('.$expr.'): ?>';
			$tpl .= $data;
			$tpl .= '<? endif; ?>';

			return $tpl;
		}

		public function makeForeach($expData, $exp, $kName, $kVar, $vName, $vVar, $data)
		{
			$tpl = '';
			$tpl .= '<? $'.$vName.'_i = 0; $'.$vName.'_size = sizeof('.$expData.'); foreach('.$exp.'): $'.$vName.'_i++; ?>';
			$tpl .= '<? $this->setVariable(\'loop\', array(
				\'index\' => $'.$vName.'_i,
				\'index0\' => $'.$vName.'_i - 1,
				\'revindex\' => false,
				\'revindex0\' => false,
				\'first\' => $'.$vName.'_i == 1,
				\'last\' => $'.$vName.'_i == $'.$vName.'_size,
				\'length\' => $'.$vName.'_size,
				\'parent\' => false,
			)); ?>';
			if($kName) $tpl .= '<? $this->setVariable(\''.$kName.'\', '.$kVar.'); ?>';
			if($vName) $tpl .= '<? $this->setVariable(\''.$vName.'\', '.$vVar.'); ?>';
			$tpl .= $data;
			$tpl .= '<? endforeach; ?>';

			return $tpl;
		}

		public function makeJSLink($uri)
		{
			return '<script src="'.$uri.'"></script>';
		}

		public function makeCSSLink($uri)
		{
			return '<link rel="stylesheet" type="text/css" href="'.$uri.'">';
		}

		public function makeElseif($exp)
		{
			return '<? elseif('.$exp.'): ?>';
		}

		public function makeElse()
		{
			return '<? else: ?>';
		}

		public function makeEndif()
		{
			return '<? endif; ?>';
		}

		public function getVariable($varName)
		{
			return '$this->getVariable(\''.$varName.'\')';
		}

		public function setVariable($varName, $varValue)
		{
			return '<? $this->setVariable(\''.$varName.'\', '.$varValue.') ?>';
		}

		public function getString($str)
		{
			return '$this->getString('.$str.')';
		}

		public function callFilter($filterName, $expr)
		{
			return '$this->callFilter(\''.$filterName.'\', array('.$expr.'))';
		}

		public function callFunction($filterName, $expr)
		{
			return '$this->callFunction(\''.$filterName.'\', array('.$expr.'))';
		}

		public function callObjectFunction($obj, $method, $params)
		{
			return '$this->callObjectFunction( '.$obj.', \''.$method.'\', array'.$params.' )';
		}

		public function getObjectProperty($obj, $property)
		{
			return '$this->getObjectProperty( '.$obj.', '.$property.' )';
		}

		public function setObjectProperty($obj, $property, $value)
		{
			return '<? $this->setObjectProperty( '.$obj.', '.$property.', '.$value.' ) ?>';
		}
	}