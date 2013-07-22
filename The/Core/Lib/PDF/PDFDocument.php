<?php

namespace The\Core\Lib\PDF;


use The\Core\Util;
use The\Core\Table;

class PDFDocument extends PDFTable
{

	const TABLE_ALIGN_LEFT = 0;
	const TABLE_ALIGN_CENTER = 1;
	const TABLE_ALIGN_RIGHT = 2;
	const TABLE_ALIGN_CUSTOM = 3;

	protected $tableAlign = self::TABLE_ALIGN_LEFT;
	protected $tableHeader = false;

	public function setFontStyle($style)
	{
		$this->SetFont('', $style);
	}


	public function addTableRow($data)
	{
		foreach($data as &$value){
			$value = $this->convertTxt($value);
		}

		switch($this->tableAlign){
			case self::TABLE_ALIGN_RIGHT:
				$this->SetX(-(array_sum($this->widths) + $this->rMargin));
				break;
			case self::TABLE_ALIGN_CUSTOM:
				break;
			default:
				$this->SetX($this->lMargin);
				break;
		}

		parent::Row($data);
	}

	public function addTextRight($txt, $w = 0, $h = 0, $border = 0)
	{
		$this->addText($txt, $w, $h, $border, 'R');
	}
	/**
	 * @param string $txt text
	 * @param integer $w width
	 * @param integer $h height
	 * @param int $border border width
	 * @param string $align
	 * @param bool $fill
	 */
	public function addText($txt, $w = 0, $h = 5, $border = 0, $align = 'J', $fill = false){
		$txt = $this->convertTxt($txt);
		if (!$h){
			$h = 5;
		}
		parent::MultiCell($w, $h, $txt, $border, $align, $fill);
	}

	protected function convertTxt($txt)
	{
		return mb_convert_encoding($txt, 'windows-1251', 'UTF-8');
	}

	public function addTable(Table $table)
	{
		$captions = array();
		$lengths = array();
		$widths = array();
		$maxCaption = 0;
		$sumCaptions = 0;
		$fields = $table->getFields();
		foreach($fields as $field)
		{
			$captions[] = $field->getCaption();
			$length = max(5, mb_strlen($field->getCaption()));
			$sumCaptions += $length;
			if ($length > $maxCaption){
				$maxCaption = $length;
			}
			$lengths[] = $length;
		}
		foreach($lengths as $length){
			$widths[]  = ($maxCaption * (($this->w - $this->rMargin - $this->lMargin) / $sumCaptions)) * ($length / $maxCaption);
		}
		$this->SetWidths($widths);
		$aligns = array_fill(0, count($fields), 'C');
		$this->SetAligns($aligns);
		$this->addTableRow($captions);
		$aligns = array_fill(0, count($fields), 'L');
		$this->SetAligns($aligns);

		foreach($table->getRows() as $row){
			$data = array_map(function($n){return $n->getValue();}, array_values($row));
			$this->addTableRow($data);
		}
	}

	public function setTableAlign($align = self::TABLE_ALIGN_LEFT)
	{
		$this->tableAlign = $align;
	}

	public function setTableHeader($header){
		$this->tableHeader = $header;
	}

	public function CheckPageBreak($h){
		//If the height h would cause an overflow, add a new page immediately
		if ($this->GetY() + $this->bMargin + $h > $this->PageBreakTrigger){
			$this->AddPage($this->CurOrientation);
			if ($this->tableHeader != false){
				$aligns = $this->aligns;
				call_user_func($this->tableHeader, $this);
				$this->aligns = $aligns;
			}
		}
	}

}