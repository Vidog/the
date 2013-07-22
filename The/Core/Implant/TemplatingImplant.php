<?php
	namespace The\Core\Implant;

	use The\Core\Lib\Dumphper;
	use The\Core\Util;
	use The\Core\TemplateEngine\TemplateRawInput;

	trait TemplatingImplant
	{
		protected $templatingFileName;
		protected $templatingVariables = array();

		public function setTemplatingFileName($templatingFileName)
		{
			$this->templatingFileName = $templatingFileName;

			return $this;
		}

		public function getTemplatingFileName()
		{
			return $this->templatingFileName;
		}

		protected function setTemplatingVariables($templatingVariables)
		{
			$this->templatingVariables = $templatingVariables;

			return $this;
		}

		public function getTemplatingVariables()
		{
			return $this->templatingVariables;
		}

		public function removeTemplatingVariable($name)
		{
			unset($this->templatingVariables[$name]);

			return $this;
		}

		public function addTemplatingVariable($name, $value)
		{
			$this->templatingVariables[$name] = $value;

			return $this;
		}

		public function getTemplatingVariable($name)
		{
			return Util::arrayGet($this->templatingVariables, $name);
		}

		public function makeUrl($params)
		{
			return $this->getApplication()->makeUrl($params);
		}

		public function onToString()
		{

		}

		public function __toString()
		{
			try
			{
				$res = $this->onToString();
				if($res !== null)
				{
					return (string)$res;
				}

				$template = $this->getApplication()->render($this->getTemplatingFileName(), $this->getTemplatingVariables());

			} catch (\Exception $e)
			{
				$template = '';
			}

			return (string)$template;
		}
	}