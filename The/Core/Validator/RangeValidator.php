<?php
	namespace The\Core\Validator;

	use The\Core\Validator;

	class RangeValidator extends Validator
	{
		protected $minValue;
		protected $maxValue;

		public function __construct($minValue = null, $maxValue = null)
		{
			parent::__construct();

			$this
				->setMinValue($minValue)
				->setMaxValue($maxValue)
			;
		}

		public function validate($value)
		{
			$res = true;
/*
			$minVal = $this->getMinValue();
			$maxVal = $this->getMinValue();

			$needMin = ($minVal !== null);
			$needMax = ($maxVal !== null);

			$r1 =  && ($value < $minValue);
*/
			return $res;
		}

		protected function setMinValue($minValue)
		{
			$this->minValue = $minValue;

			return $this;
		}

		protected function getMinValue()
		{
			return $this->minValue;
		}

		protected function setMaxValue($maxValue)
		{
			$this->maxValue = $maxValue;

			return $this;
		}

		protected function getMaxValue()
		{
			return $this->maxValue;
		}
	}