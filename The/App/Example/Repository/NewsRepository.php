<?php

namespace The\App\Example\Repository;

use The\App\Example\Repository\Base\NewsBaseRepository;

class NewsRepository extends NewsBaseRepository
{
	public function testMethod()
	{
		$db = $this->getDB();

		/** @var $query \The\Core\Query */
		$query = $db->createQueryFromModel($this->getModelName(), 't');

		$query->select('*');

		/** @var $res \The\App\Example\Model\NewsModel[] */
		$res = $db->fetchObjects($query);

		return $res;
	}

	public function getTestQuery()
	{
		$db = $this->getDB();

		$query = $db->createQueryFromModel($this->getModelName(), 't');
		
		$query->select('t.id', 't.title');
		$query->select( array('date' => 'created_at') );

		return $query;
	}
}
