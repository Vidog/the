<?php
	namespace The\App\Example\Repository;

	use The\Core\Repository;

	class TestRepository extends Repository
	{
		public function getMobileVendors()
		{
			return array(
				array(
					'id' => 1,
					'title' => 'Apple',
				),
				array(
					'id' => 2,
					'title' => 'Samsung',
				),
				array(
					'id' => 3,
					'title' => 'LG',
				),
				array(
					'id' => 4,
					'title' => 'Nokia',
				),
			);
		}
	}