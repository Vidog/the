<?php
	namespace The\Core\Implant;

	trait ItemsImplant
	{
		protected $items = array();

		public function addItem($item)
		{
			$this->items[] = $item;

			return $this;
		}

		public function setItems(array $items)
		{
			$this->items = $items;

			return $this;
		}

		public function getItems()
		{
			return $this->items;
		}
	}