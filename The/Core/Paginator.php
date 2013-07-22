<?php
	namespace The\Core;

	use The\Core\Implant\ApplicationImplant;
	use The\Core\Implant\TemplatingImplant;
	use The\Core\Implant\HTMLElementImplant;
	use The\Core\Implant\StatementImplant;
	use The\Core\Util;
	use The\Core\DB;
	use The\Core\Query;
	use The\Core\Paginator;
	use The\Core\Application;

	class Paginator
	{
		use ApplicationImplant;
		use TemplatingImplant;
		use HTMLElementImplant;
		use StatementImplant;

		protected $propertiesNames = array();
		protected $pages;
		protected $page;
		protected $byPage;
		protected $elements;

		public function __construct(DB $db, Query $query, array $params, Application $application)
		{
			$this->setDB($db);
			$this->setQuery($query);
			$this->setParams($params);
			$this->setApplication($application);

			$this
				->setTemplatingFileName('@:Paginator:default')
				->setTagName('ul')
			;
		}

		public function execute()
		{
            #SQL_CALC_FOUND_ROWS 0.00468 (214 RPS)
            #SELECT COUNT(DISTINCT ...) 0.0046 (217 RPS)
            #SELECT COUNT(1) FROM (...) 0.00524 (191 RPS)
            #SELECT COUNT(*) FROM (...) 0.00466 (215 RPS)
            #Without paginator 0.00339 (295 RPS)

			$q = clone($this->getQuery());
			$q->_limit = null;
			$q->_offset = null;
			$q->_order = array();

			$fld = '*';
			if(sizeof($q->_group) > 0)
			{

			}

			$qx = new Query($q, 't');
			$qx->_params = $q->_params;
			$qx->select( array('cnt' => 'COUNT('.$fld.')') );

			$db = $this->getDB();
			$cnt = $db->fetchRow($qx, array(), 'cnt');

			$byPage = $this->getQuery()->_byPage;
			if($byPage <= 0)
			{
				$byPage = 1;
			}
			$pages = ceil($cnt / $byPage);
			$page = $this->getQuery()->_page;
			if($page > $pages)
			{
				$page = $pages;
			}
			if($page < 1)
			{
				$page = 1;
			}

			$limit = $byPage;
			$offset = ($page-1) * $byPage; 

			$this->setElements($cnt);
			$this->setPage($page);
			$this->setPages($pages);
			$this->setByPage($byPage);

			$this->getQuery()->limit($limit)->offset($offset);
		}

		public function loadData($paginatorData)
		{
			$this->setPage( Util::arrayGet($paginatorData, 'page') );
			$this->setByPage( Util::arrayGet($paginatorData, 'by_page') );
			$this->setPages( Util::arrayGet($paginatorData, 'pages') );
			$this->setElements( Util::arrayGet($paginatorData, 'elements') );

			return $this;
		}

		public function getPagesArray()
		{
			$res = array();

			$pages = $this->getPages();

			if($pages > 20)
            {
                $pg = $this->getPage();
                $lp = $pages-5;
                $pgx = ceil($pages / 2);
                $pgxar = array();

                for($i=1; $i<=5; $i++) $pgxar[] = $i;

                for($i=$pages-5; $i<=$pages; $i++) $pgxar[] = $i;

                for($i=1; $i<=5; $i++) $res[] = array($i, $i);
                    $pg1 = ($pg - 3 <= 0 ? 1 : $pg - 3);
                    $pg2 = ($pg + 3 >= $pages ? $pages : $pg + 3);
                    if($pg1 > 5 + 1) $res[] = array('...');
                    for($i=$pg1; $i<=$pg2; $i++)
                    {
                        if(in_array($i, $pgxar)) continue;
                        $res[] = array($i, $i);
                    }
                    if($pg2 < $lp -1) $res[] = array('...');

                for($i=$this->pages-5; $i<=$pages; $i++) $res[] = array($i, $i);
            }else
            {
                for ($i = 1; $i <= $pages; $i++) $res[] = array($i, $i);
            }

			return $res;
		}

		public function setPages($pages)
		{
			$this->pages = $pages;

			return $this;
		}

		public function getPages()
		{
			return $this->pages;
		}

		public function setPage($page)
		{
			$this->page = $page;

			return $this;
		}

		public function getPage()
		{
			return $this->page;
		}

		public function setByPage($byPage)
		{
			$this->byPage = $byPage;

			return $this;
		}

		public function getByPage()
		{
			return $this->byPage;
		}

		public function setElements($elements)
		{
			$this->elements = $elements;

			return $this;
		}

		public function getElements()
		{
			return $this->elements;
		}

		public function setPropertiesNames($propertiesNames)
		{
			$this->propertiesNames = $propertiesNames;

			return $this;
		}

		public function getPropertiesNames()
		{
			return $this->propertiesNames;
		}

		public function setPropertyName($property, $value)
		{
			$this->propertiesNames[$property] = $value;

			return $this;
		}

		public function getPropertyName($property)
		{
			return Util::arrayGet($this->propertiesNames, $property, $property);
		}

		public function onToString()
		{
			$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'before', $this, array());
			if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'before', $this, array());

			$this->addTemplatingVariable('page', $this->getPage());
			$this->addTemplatingVariable('page_property', $this->getPropertyName('page'));
			$this->addTemplatingVariable('pages', $this->getPages());
			$this->addTemplatingVariable('by_page', $this->getByPage());
			$this->addTemplatingVariable('elements', $this->getElements());
			$this->addTemplatingVariable('pages_array', $this->getPagesArray());

			if(get_called_class() != __CLASS__) $this->getApplication()->callInjection(get_called_class(), __FUNCTION__, 'after', $this, array());
			$this->getApplication()->callInjection(__CLASS__, __FUNCTION__, 'after', $this, array());
		}
	}