<?php
	namespace The\Core\Validator;

	use The\Core\Validator;

	class RegexpValidator extends Validator
	{
		protected $pattern;
		protected $modificators;
		protected $isFullString;

		public function __construct($pattern, $modificators = 'i', $isFullString = true)
		{
			parent::__construct();

			$this
				->setPattern($pattern)
				->setModificators($modificators)
				->setIsFullString($isFullString)
			;
		}

		public function makeValidation($value)
		{
			$pattern = $this->getPattern();
			if($this->getIsFullString())
			{
				$pattern = '^'.$pattern.'$';
			}
			$pattern = '/'.$pattern.'/'.$this->getModificators();
			$res = preg_match($pattern, $value);
			return (bool)($res > 0);
		}

		protected function setPattern($pattern)
		{
			$this->pattern = $pattern;

			return $this;
		}

		public function getPattern()
		{
			return $this->pattern;
		}

		protected function setModificators($modificators)
		{
			$this->modificators = $modificators;

			return $this;
		}

		public function getModificators()
		{
			return $this->modificators;
		}

		protected function setIsFullString($isFullString)
		{
			$this->isFullString = $isFullString;

			return $this;
		}

		public function getIsFullString()
		{
			return $this->isFullString;
		}
	}