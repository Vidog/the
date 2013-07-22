<?php
	namespace The\Core\Implant;

	trait StatementImplant
	{
		protected $db;
		protected $statement;
		protected $query;
		protected $params;

		public function setDB($db)
		{
			$this->db = $db;

			return $this;
		}

		public function getDB()
		{
			return $this->db;
		}

		public function setStatement($statement)
		{
			$this->statement = $statement;

			return $this;
		}

		public function getStatement()
		{
			return $this->statement;
		}

		public function setQuery($query)
		{
			$this->query = $query;

			return $this;
		}

		public function getQuery()
		{
			return $this->query;
		}

		public function setParams($params)
		{
			$this->params = $params;

			return $this;
		}

		public function getParams()
		{
			return $this->params;
		}
	}