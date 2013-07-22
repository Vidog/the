<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\StatementImplant;
	use The\Core\Util;
	use The\Core\DB;
	use The\Core\Query;
	use The\Core\Paginator;
	use The\Core\Application;

	class Model implements \ArrayAccess
	{
		use ApplicationImplant;
		use StatementImplant;

		const TABLE_NAME = '';

		protected $fieldsData = array();
		protected $paginator;

		protected $onCreate;
		protected $onUpdate;
		protected $onRemove;

		protected $fieldOriginalNames = array();

		public function __construct(DB $db, \PDOStatement $statement, Query $query, array $params, Application $application)
		{
			$this->setDB($db);
			$this->setStatement($statement);
			$this->setQuery($query);
			$this->setParams($params);
			$this->setPaginator($query->getPaginator());
			$this->setApplication($application);

			foreach(range(0, $statement->columnCount() - 1) as $column_index)
			{
			  $meta = $statement->getColumnMeta($column_index);
			  #Util::pr($meta);
			}
		}

		public static function newInstance($className, DB $db, Application $application)
		{
			$statement = new \PDOStatement();
			return new $className($db, $statement, new Query(), array(), $application);
		}

		protected function setPaginator($paginator)
		{
			$this->paginator = $paginator;

			return $this;
		}

		public function getPaginator()
		{
			return $this->paginator;
		}

		public function setOnCreate($onCreate)
		{
			$this->onCreate = $onCreate;

			return $this;
		}

		public function getOnCreate()
		{
			return $this->onCreate;
		}

		public function setOnUpdate($onUpdate)
		{
			$this->onUpdate = $onUpdate;

			return $this;
		}

		public function getOnUpdate()
		{
			return $this->onUpdate;
		}

		public function setOnRemove($onRemove)
		{
			$this->onRemove = $onRemove;

			return $this;
		}

		public function getOnRemove()
		{
			return $this->onRemove;
		}

		public function __set($key, $value)
		{
			if($key === null)
			{
				throw new \Exception('Appending is not allowed');
				return;
			}
			
			$keyCC = implode('', array_map(function($s){ return ucfirst($s); }, explode('_', $key)));
			$this->fieldOriginalNames[Util::underscoreConvert( $keyCC )] = $key;

			if ($key[0] === '_'){
				$this->fieldsData[$key] = $value;
			} else {
				$key = 'set'.implode('', array_map(function($s){ return ucfirst($s); }, explode('_', $key)));
				$this->$key($value);
			}
			return $this;
		}

		public function __get($key)
		{
			if ($key[0] === '_'){
				return Util::arrayGet($this->fieldsData, $key);
			} else {
				$key = 'get'.implode('', array_map(function($s){ return ucfirst($s); }, explode('_', $key)));
				return $this->$key();
			}
		}

		public function __isset($key)
		{
			return isset($this->fieldsData[$key]);
		}

		public function __unset($key)
		{
			unset($this->fieldsData[$key]);
		}

		public function __call($method, $args)
		{
	        $class = get_called_class();
	        $reflection = new \ReflectionClass($class);
	        if (preg_match('/^get(\w+)$/', $method, $matches))
	        {
	            $field = Util::underscoreConvert($matches[1]);
	            if (isset($this->fieldsData[$field]))
	            {
	                return $this->fieldsData[$field];
	            } elseif (in_array($field, $reflection->getConstants()))
	            {
	                return null;
	            } else
	            {
	            	return null;
	                #throw new \Exception('Field \'' . $field . '\' doesn\'t exists in ' . __CLASS__);
	            }
	        } elseif (preg_match('/^set(\w+)$/', $method, $matches))
	        {
	            $field = Util::underscoreConvert($matches[1]);
                $this->fieldsData[$field] = $args[0];
                return $this;
	            /*if (isset($this->fieldsData[$field]))
	            {
	                $this->fieldsData[$field] = $args[0];
	                return $this;
	            } else {
	                if (!in_array($field, $reflection->getConstants()))
	                {
	                    throw new \Exception('Field \'' . $field . '\' doesn\'t exists in ' . __CLASS__);
	                }
	                $this->fieldsData[$field] = $args[0];
	                return $this;
	            }*/
	        } else
	        {
	            throw new \Exception('Undefined method ' . $method . ' in ' . __CLASS__);
	        }
		}

		public function offsetSet($offset, $value)
		{
	        return $this->__set($offset, $value);
	    }

	    public function offsetExists($offset)
	    {
	        return $this->__isset($offset);
	    }

	    public function offsetUnset($offset)
	    {
	        return $this->__unset($offset);
	    }

	    public function offsetGet($offset)
	    {
	        return $this->__get($offset);
	    }

	    public function getId()
	    {
	    	return Util::arrayGet($this->fieldsData, 'id');
	    }

	    public function getFieldsData()
	    {
	    	$fieldsData = array();

	    	foreach($this->fieldsData as $fieldName => $fieldValue)
	    	{
	    		$fieldName = Util::arrayGet($this->fieldOriginalNames, $fieldName, $fieldName);
	    		$fieldsData[$fieldName] = $this->$fieldName;
	    	}

	    	return $fieldsData;
	    }

	    public function getFieldsInsertData()
	    {
	    	return $this->getFieldsData();
	    }

	    public function getFieldsUpdateData()
	    {
	    	$data = $this->getFieldsData();
	    	unset($data['id']);
	    	return $data;
	    }

	    public function loadFromArray(array $data)
	    {
	    	foreach($data as $fieldName => $fieldValue)
	    	{
	    		$this->$fieldName = $fieldValue;
	    	}

	    	return $this;
	    }

	    public function loadFromModel(Model $model)
	    {
	    	return $this->loadFromArray($model->getFieldsData());
	    }

	    public function createQuery()
	    {
	    	return $this->getDB()->createQueryFromModel( get_called_class(), 'tbl' );
	    }

	    public function load($db = null)
	    {
	    	$id = $this->getId();
	    	if($id)
	    	{
				if($db === null)
				{
					$db = $this->getDB();
				}

	    		return $this->loadFromModel( $db->fetchObject( $this->createQuery()->select('*')->where('tbl.id', ':id')->setParameter('id', $id)->limit(1) ) );
	    	}else
	    	{
	    		return false;
	    	}
	    }

	    public function save($db = null)
	    {
	    	$id = $this->getId();
	    	if($id)
	    	{
	    		return $this->update($db);
	    	}else
	    	{
	    		return $this->insert($db);
	    	}
	    }

	    public function insert($db = null)
	    {
	    	$query = $this->createQuery();
	    	$data = $this->getFieldsInsertData();

	    	$handle = $this->getOnCreate();
	    	if(is_callable($handle))
	    	{
	    		$handle($this, $query, $data);
	    	}

			if($db === null)
			{
				$db = $this->getDB();
			}

	    	$id = $db->insert($query, $data);

			$this->setId($id);

			return $id;
	    }

	    public function update($db = null)
	    {
	    	$query = $this->createQuery()->where('id', ':id')->setParameter('id', $this->getId())->limit(1);
	    	$data = $this->getFieldsUpdateData();

	    	$handle = $this->getOnUpdate();
	    	if(is_callable($handle))
	    	{
	    		$handle($this, $query, $data, $this->getId());
	    	}

			if($db === null)
			{
				$db = $this->getDB();
			}

	    	return $db->update($query, $data);
	    }

	    public function remove($db = null)
	    {
	    	$query = $this->createQuery()->where('id', ':id')->setParameter('id', $this->getId())->limit(1);

	    	$handle = $this->getOnRemove();
	    	if(is_callable($handle))
	    	{
	    		$handle($this, $query, $this->getId());
	    	}

			if($db === null)
			{
				$db = $this->getDB();
			}

	    	return $db->delete($query);
	    }

		/**
		 * @return string
		 * @TODO move this to repository
		 */
		public function getTableName()
		{
			$class = get_called_class();

			return $class::TABLE_NAME;
		}
	}