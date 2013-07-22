<?php
	namespace The\Core\Repository;

	use The\Core\Repository;

	class BooleanRepository extends Repository
	{
		public function getValues()
		{
			return array(
				array('id' => '1', 'title' => 'Да'),
				array('id' => '0', 'title' => 'Нет'),
			);
		}
	}