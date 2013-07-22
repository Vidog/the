<?php
	namespace The\Core\Field;

	use The\Core\Field;
	use The\Core\Query;

	class StringField extends Field
	{
		public function applyFilterQuery(Query $q)
		{
			if( ($res = $this->callEvent(self::EVENT_APPLY_FILTER_QUERY, $q)) )
			{
				return $res;
			}

			$name = $this->getQueryName();
			$q->where($name, Query::LIKE, ':filter_'.$name)->setParameter('filter_'.$name, $this->getValue());

			return $this;
		}
	
	}