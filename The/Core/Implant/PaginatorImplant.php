<?php
	namespace The\Core\Implant;

	trait PaginatorImplant
	{
		/**
		 * @var \The\Core\Paginator
		 */
		protected $paginator;

		public function setPaginator($paginator)
		{
			$this->paginator = $paginator;

			return $this;
		}

		public function getPaginator()
		{
			return $this->paginator;
		}
	}