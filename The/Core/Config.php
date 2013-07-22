<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;

	class Config
	{
		use ApplicationImplant;

		protected $data = array();

		public function __construct()
		{

		}

		public function loadFromArray(array $data)
		{
			$this->setData( $data );
			return true;
		}

		public function loadFromFile($fileName)
		{
			$yaml = $this->getApplication()->getFileReader()->read($fileName);

			$data = $yaml ? Yaml::decode( $yaml ) : array();

			return $this->loadFromArray($data);
		}

		public function merge(Config $config)
		{
			$data1 = $this->getData();
			$data2 = $config->getData();

			$data = array_merge($data1, $data2);

			return $this->setData($data);
		}

		public function setData($data)
		{
			$this->data = $data;

			return $this;
		}

		public function getData()
		{
			return $this->data;
		}

		public function get($name, $default = null)
		{
			$ev = "\$this->data['".str_replace('.', "']['", $name)."']";
			return eval('return isset('.$ev.') ? '.$ev.' : $default;');
		}
	}