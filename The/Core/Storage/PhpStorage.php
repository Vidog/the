<?php
	namespace The\Core\Storage;

	use The\Core\Storage;

	class PHPStorage extends Storage
	{
		protected $directory;

		function __construct($directory)
		{
			$this->directory = $directory;
		}

		public function init()
		{

		}

		public function getFileName($collection, $name)
		{
			return $this->directory.$collection.'_'.$name.'.php';
		}

		public function get($collection, $name, $default = null)
		{
			$fname = $this->getFileName($collection, $name);
			$r = include $fname;
			return (bool)$r ? $r : $default;
		}

		public function set($collection, $name, $value, $ttl = 0, $tags = array())
		{
			$fname = $this->getFileName($collection, $name);
			return (bool)file_put_contents($fname, '<?php return '.\The\Core\Util::phpToString($value).';');
		}

		public function exists($collection, $name)
		{
			$fname = $this->getFileName($collection, $name);
			return file_exists($fname);
		}

		public function delete($collection, $name)
		{
			$fname = $this->getFileName($collection, $name);
			return unlink($fname);
		}
	}