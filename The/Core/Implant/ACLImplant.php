<?php
	namespace The\Core\Implant;

	trait ACLImplant
	{
		protected $acl;

		public function setACL($acl)
		{
			$this->acl = $acl;

			return $this;
		}

		public function getACL()
		{
			return $this->acl;
		}
	}