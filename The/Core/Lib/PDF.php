<?php
namespace The\Core\Lib;

use The\Core\Lib\PDF\FPDF;
use The\Core\Lib\PDF\PDFDocument;
use The\Core\Lib\PDF\PDFTable;
use The\Core\Service;

class PDF extends Service
{
	public function createDocument($orientation = 'P', $unit = 'mm', $size = 'A4')
	{
		$pdf = new PDFDocument($orientation, $unit, $size);
		$pdf->AddFont('Courier', '', 'couriercyr.php');
		$pdf->SetFont('Courier', '', 10);
		$pdf->SetAutoPageBreak(true, 5);
		$pdf->AddPage();
		return $pdf;
	}



}
