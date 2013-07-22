<?php
	namespace The\Core\Implant;

	trait SettingsImplant
	{
		protected $settings = array();

		public function setSettings($settings)
		{
			$this->settings = $settings;

			return $this;
		}

		public function getSettings()
		{
			return $this->settings;
		}

		public function getSetting($name, $default = null)
		{
			$ev = "\$this->settings['".str_replace('.', "']['", $name)."']";
			return eval('return isset('.$ev.') ? '.$ev.' : $default;');
		}
	}