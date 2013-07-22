<?php
	namespace The\Core\Implant;

	use The\Core\Util;

	trait EventsImplant
	{
		protected $events = array();

		public function addEvent($eventName, $eventCallback)
		{
			$this->events[$eventName] = $eventCallback;

			return $this;
		}

		public function getEvent($eventName)
		{
			return Util::arrayGet($this->events, $eventName, function () { });
		}

		public function callEvent($eventName)
		{
			$args    = func_get_args();
			$args[0] = $this;

			return call_user_func_array($this->getEvent($eventName), $args);
		}

		public function getEvents()
		{
			return $this->events;
		}

		/**
		 * @param array $events
		 */
		public function setEvents($events)
		{
			$this->events = $events;
		}
	}