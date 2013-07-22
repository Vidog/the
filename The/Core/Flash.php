<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\MessagingImplant;

	class Flash
	{
		use ApplicationImplant;
		use MessagingImplant;

		public function getMessages($type = null)
		{
			if ($type === null)
			{
				$res            = $this->messages;
				$this->messages = array();
			}
			else
			{
				$res = Util::arrayGet($this->messages, $type, array());
				unset($this->messages[$type]);
			}

			return $res;
		}
	}