<?php

namespace The\App\Example\Repository\Base;

use The\Core\Repository;

/**
 * Class NewsRepository
 * @package The\App\Example\Repository
 * 
 * @method The\App\Example\Model\NewsModel[] findById(\mixed $value, \int $limit = null)
 * @method The\App\Example\Model\NewsModel findOneById(\mixed $value)
 * 
 * @method The\App\Example\Model\NewsModel[] findByTitle(\mixed $value, \int $limit = null)
 * @method The\App\Example\Model\NewsModel findOneByTitle(\mixed $value)
 * 
 * @method The\App\Example\Model\NewsModel[] findByText(\mixed $value, \int $limit = null)
 * @method The\App\Example\Model\NewsModel findOneByText(\mixed $value)
 * 
 * @method The\App\Example\Model\NewsModel[] findByCreatedAt(\mixed $value, \int $limit = null)
 * @method The\App\Example\Model\NewsModel findOneByCreatedAt(\mixed $value)
 */
class NewsBaseRepository extends Repository
{
	protected $modelName = 'News';
}
