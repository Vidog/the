<?php
	namespace The\Core;

	class Query
	{
		const EQ = '=';
		const NEQ = '!=';
		const LT = '<';
		const LTE = '<=';
		const GT = '>';
		const GTE = '>=';
		const IN = 'IN';
		const NOT_IN = 'NOT IN';
		const LIKE = 'LIKE';
		const NOT_LIKE = 'NOT LIKE';
	    const REGEXP = 'REGEXP';
	    const NOT_REGEXP = 'NOT REGEXP';
		const BETWEEN = 'BETWEEN';
		const NOT_BETWEEN = 'NOT BETWEEN';
		const _AND = 'AND';
		const _OR = 'OR';
		const SQL_NUM_ROWS = 'SQL_NUM_ROWS';
		const DISTINCT = 'DISTINCT';
		const IS = 'IS';
		const IS_NOT = 'IS NOT';
		const IS_NOT_NULL = 'IS NOT NULL';
		const IS_NULL = 'IS NULL';
		const NULL = 'NULL';

		public $tableName;
		public $tableAlias;
		public $_select = array();
		public $_selectModifiers = array();
		public $_from = array();
		public $_join = array();
		public $_where = array();
		public $_on = array();
		public $_having = array();
		public $_order = array();
		public $_group = array();
	    public $_limit;
	    public $_offset;
	    public $_union = array();

	    public $_params = array();

	    public $_paginationQuery;
	    public $_page;
	    public $_byPage;
	    public $_elements;
	    public $_pages;

		public $uniqueName;
		public $builded;

		protected $paginator;

		protected $modelClassName = '\The\Core\Model';

		function __construct($tableName = null, $tableAlias = null, $modelClassName = null)
		{
			$this->tableName = $tableName;
			$this->tableAlias = $tableAlias;
			if($tableName)
			{
	        	$this->from($this->tableName, $this->tableAlias);
			}
			if($modelClassName)
			{
				$this->setModelClassName($modelClassName);
			}
		}

		public static function sql($s)
		{
			return str_replace(array('\\', '\''), array('\\\\', '\\\''), $s);
		}

		public function setModelClassName($modelClassName)
		{
			$this->modelClassName = $modelClassName;

			return $this;
		}

		public function getModelClassName()
		{
			return $this->modelClassName;
		}

		public function setPaginator($paginator)
		{
			$this->paginator = $paginator;

			return $this;
		}

		public function getPaginator()
		{
			return $this->paginator;
		}

		public function getParameters()
		{
			return $this->_params;
		}

		public function setParameters($parameters)
		{
			$this->_params = $parameters;

			return $this;
		}

		public function setParameter($name, $value)
		{
			$this->_params[$name] = $value;
			return $this;
		}

		public function switchModifier($modifier)
		{
			if(!isset($this->_selectModifiers[$modifier]))
			{
				$this->_selectModifiers[$modifier] = true;
			}else
			{
				$this->_selectModifiers[$modifier] = false;
			}
			return $this;
		}

		public function distinct()
		{
			return $this->switchModifier(__FUNCTION__);
		}

		public function sql_no_cache()
		{
			return $this->switchModifier(__FUNCTION__);
		}

		public function sql_calc_found_rows()
		{
			return $this->switchModifier(__FUNCTION__);
		}

		public function union(Query $q)
		{
			foreach(func_get_args() as $q)
			{
				$this->_union[] = $q;
			}
		}

	    /**
	     * @param int $limit
	     * @param int $offset
	     *
	     * @return Query
	     */
	    public function limit($limit, $offset = 0)
	    {
	        $this->_limit = $limit;
	        return $this->offset($offset);
	    }

	    /**
	     * @param int $offset
	     *
	     * @return Query
	     */
	    public function offset($offset)
	    {
	        $this->_offset = $offset;
	        return $this;
	    }

		public function select()
		{
			foreach(func_get_args() as $arg)
			{
				$this->_select[] = $arg;
			}
			return $this;
		}

		public function from($tableName, $tableAlias = null)
		{
			if(is_a($tableName, '\The\Core\Query'))
			{
				if(!isset($this->_from['~subquery']))
				{
					$this->_from['~subquery']= array();
				}
				$this->_from['~subquery'][] = array(
					'table' => $tableName,
					'alias' => $tableAlias,
				);
			}else
			{
				if(!isset($this->_from[$tableName]))
				{
					$this->_from[$tableName] = array();
				}
				$this->_from[$tableName][] = array(
					'table' => $tableName,
					'alias' => $tableAlias,
				);
			}
			return $this;
		}

		public function addJoin($type, $tableName, $tableAlias, $leftField, $rightField, $toTable = null)
		{
			if($toTable === null)
			{
				$toTable = $this->tableName;
				if(is_object($toTable))
				{
					$toTable = '_subquery_'.$toTable->tableName;
				}
			}
			if(!isset($this->_join[$toTable]))
			{
				$this->_join[$toTable] = array();
			}
			if(!isset($this->_join[$toTable][$tableName]))
			{
				$this->_join[$toTable][$tableName] = array();
			}
			if(!isset($this->_join[$toTable][$tableName][$tableAlias]))
			{
				$this->_join[$toTable][$tableName][$tableAlias] = array();
			}
			$this->_join[$toTable][$tableName][$tableAlias][] = array(
				'type' => $type,
				'table' => $tableName,
				'alias' => $tableAlias,
				'leftField' => $leftField,
				'rightField' => $rightField,
			);
			return $this;
		}

		public function join($tableName, $tableAlias, $leftField, $rightField, $toTable = null)
		{
			$this->leftJoin($tableName, $tableAlias, $leftField, $rightField, $toTable);
			return $this;
		}

		public function leftJoin($tableName, $tableAlias, $leftField, $rightField, $toTable = null)
		{
			$this->addJoin('left', $tableName, $tableAlias, $leftField, $rightField, $toTable);
			return $this;
		}

		public function innerJoin($tableName, $tableAlias, $leftField, $rightField, $toTable = null)
		{
			$this->addJoin('inner', $tableName, $tableAlias, $leftField, $rightField, $toTable);
			return $this;
		}

		public function rightJoin($tableName, $tableAlias, $leftField, $rightField, $toTable = null)
		{
			$this->addJoin('right', $tableName, $tableAlias, $leftField, $rightField, $toTable);
			return $this;
		}

		public function where()
		{
			$this->_where[] = func_get_args();
			return $this;
		}

	    /**
	     * @return Query
	     */
	    public function having()
		{
			$this->_having[] = func_get_args();
			return $this;
		}

		public function on($tableName)
		{
			$args = func_get_args();
			array_shift($args);
			if(!isset($this->_on[$tableName]))
			{
				$this->_on[$tableName] = array();
			}
			$this->_on[$tableName][] = $args;
			return $this;
		}

		public function group()
		{
			foreach(func_get_args() as $arg)
			{
				$this->_group[] = $arg;
			}
			return $this;
		}

		public function order()
		{
			$this->_order[] = func_get_args();
			return $this;
		}

		public function byPage($page, $byPage)
		{
			$this->_paginationQuery = true;
			$this->_page = $page;
			$this->_byPage = $byPage;

			return $this;
		}
	}