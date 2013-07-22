<?php
	namespace The\Core\FileReader;

	use The\Core\Implant\ApplicationImplant;

	class IOFileReader
	{
		use ApplicationImplant;

		public function read($fileName)
		{
			return file_get_contents($fileName);
		}

		public function write($fileName, $data)
		{
			return file_put_contents($fileName, $data);
		}

		public function exists($fileName)
		{
			return file_exists($fileName);
		}

		public function remove($fileName)
		{
			return unlink($fileName);
		}

		public function copy($src, $dst)
		{
			$r = copy($src, $dst);

			if (!$r)
			{
				die;
			}

			return $r;
		}

		public function rename($src, $dst)
		{
			return rename($src, $dst);
		}

		public function move($src, $dst)
		{
			return rename($src, $dst);
		}

		public function isDir($fileName)
		{
			return is_dir($fileName);
		}

		public function isFile($fileName)
		{
			return is_file($fileName);
		}

		public function createDir($fileName, $chmod = 0777, $recursive = true)
		{
			return !$this->exists($fileName) ? mkdir($fileName, $chmod, $recursive) : false;
		}

		public function cloneDir($src, $dst)
		{
			$dir = opendir($src);

			if (!$dir)
			{
				return false;
			}

			$this->createDir($dst);

			while (false !== ($file = readdir($dir)))
			{
				if (($file != '.') && ($file != '..'))
				{
					if ($this->isDir($src . '/' . $file))
					{
						$this->cloneDir($src . '/' . $file, $dst . '/' . $file);
					}
					else
					{
						$this->copy($src . '/' . $file, $dst . '/' . $file);
					}
				}
			}

			closedir($dir);
		}

		public function listDir($fileName)
		{
			return $this->isDir($fileName) ? array_slice(scandir($fileName), 2) : false;
		}

		public function removeDir($fileName)
		{
			$path = rtrim($fileName, '/') . '/';

			if (is_dir($path))
			{
				$handle = opendir($path);

				while (false !== ($file = readdir($handle)))
				{
					if ($file != '.' and $file != '..')
					{
						$fullPath = $path . $file;

						if (is_dir($fullPath))
						{
							$this->removeDir($fullPath);
						}

						else unlink($fullPath);
					}
				}

				closedir($handle);
				rmdir($path);
			}
		}
	}