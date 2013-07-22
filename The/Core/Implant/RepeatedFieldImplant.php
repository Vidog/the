<?php
/**
 * Created by JetBrains PhpStorm.
 * User: devuser11
 * Date: 25.04.13
 * Time: 13:48
 * To change this template use File | Settings | File Templates.
 */

namespace The\Core\Implant;


trait RepeatedFieldImplant
{

	protected $baseName;
	protected $index;
	protected $isRepeated = false;
	protected $isRemoveAllowed = true;
	protected $isAddAllowed = true;

	public function getBaseName()
	{
		return $this->baseName;
	}

	public function setBaseName($baseName)
	{
		$this->baseName = $baseName;
		return $this;
	}

	public function getIndex()
	{
		return $this->index;
	}

	public function setIndex($index)
	{
		$this->index = $index;
		return $this;
	}

	public function getIsRepeated()
	{
		return $this->isRepeated;
	}

	public function setIsRepeated($isRepeated)
	{
		$this->isRepeated = $isRepeated;
		return $this;
	}

	public function getIsRemoveAllowed()
	{
		return $this->isRemoveAllowed;
	}

	public function setIsRemoveAllowed($isRemoveAllowed)
	{
		$this->isRemoveAllowed = $isRemoveAllowed;
		return $this;
	}

	public function getIsAddAllowed()
	{
		return $this->isAddAllowed;
	}

	public function setIsAddAllowed($isAddAllowed)
	{
		$this->isAddAllowed = $isAddAllowed;
		return $this;
	}

}