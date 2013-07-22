<?php
	namespace The\Core\Exception;

	use The\Core\Util;
	use The\Core\Exception;

	class DBException extends Exception
	{
		/*public function __toString()
		{
            Util::getApplication()->addError('db', $this->getCode(), $this->getMessage(), '', 0);
		}*/
	}