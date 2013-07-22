<?php
namespace The\Core;

    use The\Core\Implant\ApplicationImplant;
    use The\Core\Paginator;
    use The\Core\Query;
    use The\Core\Exception\DBException;
    use \PDO;

class DB
{
	use ApplicationImplant;

	private $link;
	private $username;
	private $password;
	private $names;

	private $connected = false;

	/**
	 * @var \PDO $pdo
	 */
	public $pdo;

	function __construct($link, $username, $password, $names = 'utf8')
	{
		#var_dump($link);
		$this->link = $link;
		$this->username = $username;
		$this->password = $password;
		$this->names = $names;
	}

	protected function connect()
	{
		if ($this->connected) {
			return true;
		}

		$this->connected = true;

		$this->pdo = new PDO($this->link, $this->username, $this->password, array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->names . ';',
			PDO::ATTR_PERSISTENT => false,
			PDO::MYSQL_ATTR_COMPRESS => true,
			#PDO::MYSQL_ATTR_DIRECT_QUERY => true,
		));

		return true;
	}

	protected function isStaticStatement($s)
	{
		if (preg_match('/\(\)\.\*\\\'\\\//', $s) || is_numeric($s)) {
			return true;
		} else {
			return false;
		}
	}

	protected function getVariable($s)
	{
		return $this->isStaticStatement($s) ? $s : '"' . $s . '"';
	}

	protected function getSelectVariable($s)
	{
		if (is_array($s)) {
			$r = array();
			foreach ($s as $k => $v) {
				$r[] = $v . ' as ' . $k;
			}
			return implode(', ', $r);
		} else {
			return $s;
		}
	}

	protected function getOrderVariable($s)
	{
		if (is_array($s)) {
			if (isset($s[0]) && !is_array($s[0])) {
				return implode(', ', $s);
			} else {
				$r = array();
				foreach ($s[0] as $k => $v) {
					$r[] = $k . ' ' . $v;
				}
				return implode(', ', $r);
			}
		} else {
			return $s;
		}
	}

	protected function getWhereLeft($s)
	{
		return $s;
	}

	protected function getWhereRight($s)
	{
		return $s;
	}

	protected function buildWhere($where)
	{
		$len = sizeof($where);
		switch ($len) {
			case 1:
				return '(' . $where[0] . ')';
				break;

			case 2:
				if (is_bool($where[1])) {
					$where[1] = (string)$where[1];
				}
				switch ($where[1]) {
					case Query::IS_NULL:
					case Query::IS_NOT_NULL:
						return '(' . $this->getWhereLeft($where[0]) . ' ' . $where[1] . ')';
						break;

					default:
						return '(' . $this->getWhereLeft($where[0]) . ' ' . Query::EQ . ' ' . $this->getWhereRight($where[1]) . ')';
						break;
				}
				break;

			case 3:
				$oper = strtoupper($where[1]);
				switch ($oper) {
					default:
						$operand = $oper . ' ' . $this->getWhereRight($where[2]);
						break;

					case Query::IN:
					case Query::NOT_IN:
						if (is_a($where[2], '\The\Core\Query')) {
							$where[2] = $this->buildQuery($where[2]);
						}
						$operand = $oper . ' ' . '(' . (is_array($where[2]) ? implode(',', $where[2]) : $where[2]) . ')';
						break;

					case Query::BETWEEN:
					case Query::NOT_BETWEEN:
						$operand = $oper . ' ' . (is_array($where[2]) ? implode(' AND ', $where[2]) : $where[2]);
						break;
				}
				return '(' . $this->getWhereLeft($where[0]) . ' ' . $operand . ')';
				break;

			default:
				//@TODO O__o
				break;
		}
	}

	protected function buildQuery(Query $query)
	{
		if ($query->builded) return $query->builded;
		$parts = array();

		$selects = array();
		foreach ($query->_select as $select) {
			$selects[] = $this->getSelectVariable($select);
		}
		if (sizeof($selects) > 0) {
			$parts[] = 'SELECT';
			$pt = array();
			foreach ($query->_selectModifiers as $mod => $is) {
				if (!$is) {
					continue;
				}
				$pt[] = strToUpper($mod);
			}
			$parts[] = implode(' ', $pt);
			$parts[] = implode(', ', $selects);
		}

		$froms = array();
		foreach ($query->_from as $tableName => $tables) {
			foreach ($tables as $table) {
				$fromTable = $table['table'];
				$fromAlias = $table['alias'];

				$isSubquery = is_a($fromTable, '\The\Core\Query');
				if ($isSubquery) {
					$tbl = $fromTable;
					$fromTable = '_subquery_' . $tbl->tableName;
				}

				$joins = array();
				if (isset($query->_join[$fromTable])) {
					foreach ($query->_join[$fromTable] as $joinTargetTable => $joinAliases) {
						foreach ($joinAliases as $joinAlias => $joinTables) {
							$ons = array();
							foreach($joinTables as $joinTable)
							{
								$query->on($joinTargetTable, $joinTable['leftField'], $joinTable['rightField']);
								foreach ($query->_on[$joinTargetTable] as $on) {
									$ons[] = $this->buildWhere($on);
								}
							}
							$joins[] = ' ' . strtoupper($joinTable['type']) . ' JOIN ' . $joinTargetTable . ' ' . $joinAlias . ' ON ' . implode(' AND ', $ons);
						}
					}
				}

				if ($isSubquery) {
					$froms[] = '(' . $this->buildQuery($tbl) . ')' . ' ' . $table['alias'] . implode(' ', $joins);
				} else {
					$froms[] = $fromTable . ' ' . $table['alias'] . implode(' ', $joins);
				}
			}
		}
		if (sizeof($froms) > 0) {
			$parts[] = 'FROM';
			$parts[] = implode(', ', $froms);
		}

		$wheres = array();
		foreach ($query->_where as $where) {
			$wheres[] = $this->buildWhere($where);
		}
		if (sizeof($wheres) > 0) {
			$parts[] = 'WHERE';
			$parts[] = implode(' AND ', $wheres);
		}

		$groups = $query->_group;
		if (sizeof($groups) > 0) {
			$parts[] = 'GROUP BY';
			$parts[] = implode(', ', $groups);
		}

		$havings = array();
		foreach ($query->_having as $having) {
			$havings[] = $this->buildWhere($having);
		}
		if (sizeof($havings) > 0) {
			$parts[] = 'HAVING';
			$parts[] = implode(' AND ', $havings);
		}

		$orders = array();
		foreach ($query->_order as $order) {
			$orders[] = $this->getOrderVariable($order);
		}
		if (sizeof($orders) > 0) {
			$parts[] = 'ORDER BY';
			$parts[] = implode(', ', $orders);
		}

		if ($query->_limit !== null) {
			$parts[] = 'LIMIT ' . $query->_limit;
		}

		if ($query->_offset !== null) {
			$parts[] = 'OFFSET ' . $query->_offset;
		}

		$parts = array_filter($parts, function ($v) {
			return (bool)$v;
		});

		$sql = implode(' ', $parts);

		if (sizeof($query->_union) > 0) {
			foreach ($query->_union as $q) {
				$sql .= ' UNION ' . $this->buildQuery($q);
			}
		}

		$query->builded = $sql;

		/*$cache = $this->getApplication()->getStorage();
		if($query->uniqueName)
		{
			$cache->set('query', $query->uniqueName, $sql);
		}*/

		#Utility::pr($sql);

		//return 'SELECT 1,2,3;';
		return $sql;
	}

	public function rawSQL($sql, array $parameters = array())
	{
		return $this->runSQL($sql, $parameters);
	}

	protected function runSQL($sql, array $parameters = array())
	{
		$this->connect();

		$params = array();

		$newSQL = preg_replace_callback('/(\:[\w\.]+)/i', function ($match) use ($parameters, &$params) {
			static $i = 0;
			++$i;
			$paramKey = 'param_' . $i;
			$paramName = $match[0];

                $value = Util::arrayGet($parameters, substr($paramName, 1));
                if(is_array($value))
                {
                    $j = 0;
                    $arrayParams = array();
                    foreach($value as $val)
                    {
                        ++$j;
                        $paramKeyArray = $paramKey.'_'.$j;
                        $arrayParams[] = ':'.$paramKeyArray;
                        $params[$paramKeyArray] = $val;
                    }
                    return $arrayParams ? implode(', ', $arrayParams) : '0';
                }

			$params[$paramKey] = $value;
			return ':' . $paramKey;
		}, $sql);

		$sql = $newSQL;
		$parameters = $params;

		#if ($this->getApplication()->getIsDebug()) {

			$tm2 = microtime(true);
			$stmt = $this->pdo->prepare($sql);
			$tm2 = microtime(true) - $tm2;
			$tm1 = microtime(true);
			$stmt->execute($parameters);
			$tm1 = microtime(true) - $tm1;

                list($errorCode, $errorId, $errorMessage) = $stmt->errorInfo();

                #error_log('[SQL] '.$sql.' :: '.print_r($parameters, true).' :: '.$errorCode.' :: '.$errorId.' :: '.$errorMessage);

                Util::data('DB', array(
                    'sql' => $sql,
                    'params' => $parameters,
                    'time_prepare' => $tm2,
                    'time_execute' => $tm1,
                    'rows' => $stmt->rowCount(),
                    'success' => !(bool)$errorId,
                    'error' => array(
                        'code' => $errorCode,
                        'id' => $errorId,
                        'message' => $errorMessage,
                    ),
                ));

                $real_query = $this->getApplication()->getDBRealQuery($sql, $parameters).';';

                if($tm1 > 0.2)
                {
                	error_log('[DB SLOW QUERY :: '.$tm1.'] '.$real_query);
                }

                if($errorId)
                {
                	$this->getApplication()->addError('db', $errorId, $errorMessage, $real_query, null, array(), null);

                    throw new DBException($errorMessage, $errorId);
                }
                #Profiler::addDbQuery($sql, $parameters, $tm1, 0, $stmt->rowCount(), null);
           	#}else
            #{
            #    $stmt = $this->pdo->prepare($sql);
            #    $stmt->execute($parameters);
            #}

		return $stmt;
	}

	protected function execute(Query $query, array $parameters = array())
	{
		$this->connect();

		if ($query->_paginationQuery) {
			$paginator = new Paginator($this, $query, $parameters, $this->getApplication());
			$paginator->execute();
			$query->setPaginator($paginator);
		}

		$sql = $this->buildQuery($query);
		$parameters = array_merge($parameters, $query->_params);
		$stmt = $this->runSQL($sql, $parameters);

		return $stmt;
	}

	public function createQuery($tableName = null, $tableAlias = null, $modelClassName = null)
	{
		return new Query($tableName, $tableAlias, $modelClassName);
	}

	public function createQueryFromModel($modelClassName, $tableAlias = null)
	{
		if (strpos($modelClassName, '\\') === false) {
			$modelClassName = $this->getApplication()->getClassName($modelClassName, 'Model');
		}
		return new Query($modelClassName::TABLE_NAME, $tableAlias, $modelClassName);
	}

	public function fetchAll(Query $query, array $parameters = array())
	{
		$this->connect();
		$stmt = $this->execute($query, $parameters);
		$res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		return $res;
	}

	public function fetchObjects(Query $query, array $parameters = array())
	{
		$this->connect();
		$stmt = $this->execute($query, $parameters);
		$modelClassName = $query->getModelClassName();
		if (strpos($modelClassName, '\\') === false) {
			$modelClassName = $this->getApplication()->getClassName($modelClassName, 'Model');
		}
		$res = $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $modelClassName, array($this, $stmt, $query, $parameters, $this->getApplication()));
		return $res;
	}

	public function fetchRow(Query $query, array $parameters = array(), $key = null)
	{
		$this->connect();
		$stmt = $this->execute($query, $parameters);
		$res = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $key !== null ? (isset($res[$key]) ? $res[$key] : $res) : $res;
	}

	public function fetchObject(Query $query, array $parameters = array())
	{
		$this->connect();
		$stmt = $this->execute($query, $parameters);
		$modelClassName = $query->getModelClassName();
		if (strpos($modelClassName, '\\') === false) {
			$modelClassName = $this->getApplication()->getClassName($modelClassName, 'Model');
		}
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $modelClassName, array($this, $stmt, $query, $parameters, $this->getApplication()));
		$res = $stmt->fetch();
		return $res;
	}

	public function getValue($name, $value, &$parameters)
	{

		if (preg_match_all('/[^a-zA-Z0-9_]:([a-zA-Z0-9_]+)/', $value, $matches)) {
			foreach ($matches[1] as $param) {
				$key = 'value_' . str_replace('.', '_', $name) . '_' . mt_rand(100, 999);
				$value = str_replace(':' . $param, ':' . $key, $value);
				$parameters[$key] = Util::arrayGet($parameters, $param);
			}
			return $value;
		} else {
			$key = 'value_' . $name . '_' . mt_rand(100, 999);
			$parameters[$key] = $value;
			return ':' . $key;

		}
	}

	public function insert(Query $query, array $data, array $parameters = array())
	{
		$parts = array();

		$parts[] = 'INSERT';
		$parts[] = 'INTO';
		$parts[] = $query->tableName;
		$parts[] = 'SET';

		$pt = array();
		foreach ($data as $key => $value) {
			#$val = ($value === null) ? 'NULL' : $this->getValue($key, $value, $parameters);
			$val = $this->getValue($key, $value, $parameters);
			$pt[] = '`'.$key.'`' . ' = ' . $val;
		}
		$parts[] = implode(', ', $pt);

		$sql = implode(' ', $parts);
		$query->builded = $sql;

		$this->connect();
		$r = $this->execute($query, $parameters);

		return $this->pdo->lastInsertId();
	}

	public function update(Query $query, array $data, array $parameters = array())
	{
		$parts = array();

		$parts[] = 'UPDATE';
		$parts[] = $query->tableName . ' ' . $query->tableAlias;
		$parts[] = 'SET';

		$pt = array();
		foreach ($data as $key => $value) {
			$pt[] = '`'.$key.'`' . ' = ' . $this->getValue($key, $value, $parameters);
		}
		$parts[] = implode(', ', $pt);

		$wheres = array();
		foreach ($query->_where as $where) {
			$wheres[] = $this->buildWhere($where);
		}
		if (sizeof($wheres) > 0) {
			$parts[] = 'WHERE';
			$parts[] = implode(' AND ', $wheres);
		}

		if ($query->_limit !== null) $parts[] = 'LIMIT ' . $query->_limit;
		#if($query->_offset !== null) $parts[] = 'OFFSET ' . $query->_offset;

		$sql = implode(' ', $parts);
		$query->builded = $sql;

		$this->connect();
		$r = $this->execute($query, $parameters);

		return $r->rowCount();
	}

	public function delete(Query $query, array $parameters = array())
	{
		$parts = array();

		$parts[] = 'DELETE';
		$parts[] = 'FROM';
		$parts[] = $query->tableName;

		$wheres = array();
		foreach ($query->_where as $where) {
			$wheres[] = $this->buildWhere($where);
		}
		if (sizeof($wheres) > 0) {
			$parts[] = 'WHERE';
			$parts[] = implode(' AND ', $wheres);
		}

		if ($query->_limit !== null) $parts[] = 'LIMIT ' . $query->_limit;
		#if($query->_offset !== null) $parts[] = 'OFFSET ' . $query->_offset;

		$sql = implode(' ', $parts);
		$query->builded = $sql;

		$this->connect();
		$r = $this->execute($query, $parameters);

		return $r->rowCount();
	}

	public function describeTable($tableName)
	{
		$sql = 'DESCRIBE `' . $tableName . '`';
		$q = new Query();
		$q->builded = $sql;

		$r = $this->fetchObjects($q);

		return $r;
	}

	public function startTransaction()
	{
		$this->connect();

		return $this->pdo->beginTransaction();
	}

	public function commit()
	{
		$this->connect();

		return $this->pdo->commit();
	}

	public function rollBack()
	{
		$this->connect();
		
		return $this->pdo->rollBack();
	}
}