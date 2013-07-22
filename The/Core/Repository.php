<?php
namespace The\Core;

use The\Core\Implant\ApplicationImplant;

class Repository
{
	use ApplicationImplant;

	protected $dbName;
	protected $dbSlaveName;
	protected $dbMasterName;

	protected $storageName;

	protected $modelName = false;

	/**
	 * @return \The\Core\DB
	 */
	public function getDB()
	{
		return $this->getSlaveDB();
	}

	/**
	 * @return \The\Core\DB
	 */
	public function getSlaveDB()
	{
		return $this->getApplication()->getSlaveDB($this->dbSlaveName);
	}

	/**
	 * @return \The\Core\DB
	 */
	public function getMasterDB()
	{
		return $this->getApplication()->getMasterDB($this->dbMasterName);
	}

	public function getStorage()
	{
		return $this->getApplication()->getStorage($this->storageName);
	}

	protected function getStorageData($key, $default = null)
	{
		return $this->getStorage()->get(basename(get_called_class()), $key, $default);
	}

	protected function setStorageData($key, $value)
	{
		return $this->getStorage()->set(basename(get_called_class()), $key, $value);
	}

    /**
     * @param $uniqueName
     * @param $tableName
     * @param $alias
     *
     * @return Query
     */
    protected function getQuery($uniqueName, $tableName, $alias)
    {
    	/*$cache = $this->getApplication()->getStorage();
    	if($cache->has('query', $uniqueName))
    	{
    		return $cache->get('query', $uniqueName);
    	}*/
    	$q = new Query($tableName, $alias);
    	$q->uniqueName = $uniqueName;
    	return $q;
    }

	public function handleFindQuery($query, $fields, &$limit)
	{

	}

	public function findOneBy($fields)
	{
		return $this->find($fields, 1);
	}

	public function find($fields = array(), $limit = null)
	{

		if (!$this->modelName) {
			return false;
		}

		$query = $this->getDB()->createQueryFromModel($this->modelName, 'tbl');
		$query->select('tbl.*');

		foreach ($fields as $fieldName => $fieldValue) {
			$paramName = preg_replace('/[^A-Za-z0-9]/', '_', $fieldName);
			if (is_array($fieldValue)) {
				$query->where($query->tableAlias . '.' . $fieldName, Query::IN, ':' . $paramName);
			} else {
				$query->where($query->tableAlias . '.' . $fieldName, ':' . $paramName);
			}
			$query->setParameter($paramName, $fieldValue);
		}

		if ($limit) {
			$query->limit($limit);
		}

		$this->handleFindQuery($query, $fields, $limit);

		if ($limit === 1) {
			return $this->getDB()->fetchObject($query);
		}

		return $this->getDB()->fetchObjects($query);
	}

	public function __call($method, $args)
	{
		if (preg_match('/^findBy(\w+)$/', $method, $matches)) {
			$fieldName = Util::underscoreConvert($matches[1]);

			if (isset($args[1]) && is_int($args[1])) {
				$limit = $args[1];
			} else {
				$limit = null;
			}
			return $this->find(array($fieldName => $args[0]), $limit);
		} elseif (preg_match('/^findOneBy(\w+)$/', $method, $matches)) {
			$fieldName = Util::underscoreConvert($matches[1]);
			return $this->find(array($fieldName => $args[0]), 1);
		} else {
			throw new \Exception('Undefined method ' . $method . ' in ' . __CLASS__);
		}
	}

	protected function getModelName()
	{
		return $this->modelName;
	}
}