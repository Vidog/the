<?php
	namespace The\Core;

	class Response
	{
		private $statusCode;
		private $data;

		public function __construct($data)
		{
			$this->data = $data;
		}

		public function setStatusCode($statusCode)
		{
			$this->statusCode = $statusCode;
		}

		public function getStatusCode()
		{
			return $this->statusCode;
		}

		public function __toString()
		{
			return (string)$this->data;
		}
	}