<?php
	namespace The\Core\Implant;

	use The\Core\Util;

	define('MESSAGE_ERROR', 'error');
	define('MESSAGE_WARNING', 'warning');
	define('MESSAGE_INFO', 'info');
	define('MESSAGE_SUCCESS', 'success');

	trait MessagingImplant
	{

		protected $messages = array();

		public function addMessage($type, $message)
		{
			if(!isset($this->messages[$type]))
			{
				$this->messages[$type] = array();
			}

			$this->messages[$type][] = $message;

			return $this;
		}

		public function setMessages($messages)
		{
			$this->messages = $messages;

			return $this;
		}

		public function hasMessages($type = null)
		{
			if ($type === null){
				return (bool)(count($this->messages, COUNT_RECURSIVE) > 0);
			}

			return (bool)(sizeof(Util::arrayGet($this->messages, $type, array())) > 0);
		}

		public function addError($message)
		{
			return $this->addMessage(MESSAGE_ERROR, $message);
		}

		public function hasErrors()
		{
			return $this->hasMessages(MESSAGE_ERROR);
		}

		public function getErrors()
		{
			return $this->getMessages(MESSAGE_ERROR);
		}

		public function addWarning($message)
		{
			return $this->addMessage(MESSAGE_WARNING, $message);
		}

		public function hasWarnings()
		{
			return $this->hasMessages(MESSAGE_WARNING);
		}

		public function getWarnings()
		{
			return $this->getMessages(MESSAGE_WARNING);
		}

		public function addInfo($message)
		{
			return $this->addMessage(MESSAGE_INFO, $message);
		}

		public function addSuccess($message)
		{
			return $this->addMessage(MESSAGE_SUCCESS, $message);
		}

		public function getMessages($type = null)
		{
			return ($type === null) ? $this->messages : Util::arrayGet($this->messages, $type, array());			
		}
	}