<?php

// for some reason, incorrectly getting a DEPRECATED error for FPDF construct
// it CORRECTLY uses __construct, but gets error that it is using function FPDF()
// error_reporting(~E_DEPRECATED);

set_time_limit(10 * 60);

require('fpdf/fpdf.php');

define('LN_HEIGHT', 5);
define('IMAGE_SIZE', 80);
define('IMAGE_START_Y', 15);
define('FONT_STYLE', 'Arial');
define('FONT_SIZE', 12);
define('PAGE_WIDTH', 210);
define('EURO', chr(128));

// get POST, otherwise die
 if (!isset($_GET['product_id']) || !$_GET['product_id'] ||
		 !isset($_GET['payment_amt']) || !$_GET['payment_amt'] ||
		 !isset($_GET['venue_name']) || !$_GET['venue_name'] ||
		 !isset($_GET['commission_val']) || !isset($_GET['vat_val']) ||
		 !isset($_GET['commission_amt']) || !isset($_GET['vat_amt'])
		) {
			// for testing create dummy values
/*
		$venue_name = "**** TEST DATA ONLY ****";
		$payment_info = array(
			'product_id' => 203677,
			'payment_amt' => 5000,
			'commission_val' => 15,
			'vat_val' => 21,
			'commission_amt' => 750,
			'vat_amt' => 157.50,
		);
*/
	die("invalid parameters to create invoice");
} else {
	$venue_name = $_GET['venue_name'];
	$payment_info = array(
		'product_id' => $_GET['product_id'],
		'payment_amt' => $_GET['payment_amt'],
		'commission_val' => $_GET['commission_val'],
		'vat_val' => $_GET['vat_val'],
		'commission_amt' => $_GET['commission_amt'],
		'vat_amt' => $_GET['vat_amt'],
	);

	// var_dump($payment_info);
	// echo '</br>', disp_euros($payment_info['payment_amt'], 2);
	// echo '</br>', disp_euros($payment_info['commission_amt'], 2);
	// echo '</br>', disp_euros($payment_info['vat_amt'], 2);
	// die();
}

$pdf = set_up_pdf();
$pdf->AddPage();
$pdf->SetFont(FONT_STYLE,'',FONT_SIZE);
$cur_y = display_image($pdf);
display_company_info($pdf, $cur_y);

display_venue_name($pdf, $venue_name);

display_payment_info($pdf, $payment_info);

$pdf->Output();

exit;


//****************************************************

/**
 * Functions.
 */
function set_up_pdf() {
    return new FPDF();
}

function display_image($pdf) {
	$page_width = PAGE_WIDTH;
	$image_size = IMAGE_SIZE;
	$image_x = ($page_width - $image_size) / 2;
	$image_y = IMAGE_START_Y;
	
	$pdf->Image('images/TheTasteLogo.png', $image_x, $image_y, $image_size, $image_size);
	return $image_size + $image_y + 10;
}

function display_company_info($pdf, $y_loc) {
	$company_title = "Digital Food Ltd. T/A TheTaste.ie";
	$company_addr = array(
		'The Chq Building,',
		'DogpatchLabs Office',
		'Unit 1, North Wall Quay',
		'North Wall',
		'Dublin 1',
	);
	$company_no = '548735';
	$company_vat_no = '3312776JH';
	$date = date('m/d/Y');

	$pdf->setY($y_loc);
	$pdf->setFont('', 'B');
	center($pdf, $company_title);
	$pdf->Ln();

	$pdf->SetFont('');
	foreach($company_addr as $addr_ln) {
		center($pdf, $addr_ln);
	}
	
	display_title_info($pdf, 'Company No:', $company_no);
	
	display_title_info($pdf, 'VAT No:', $company_vat_no);

	display_title_info($pdf, 'Date:', $date);
}

function display_venue_name($pdf, $venue_name) {
	display_title_info($pdf, 'Client:', $venue_name);
	$pdf->Ln();

	$page_width = PAGE_WIDTH;
	$line_size = IMAGE_SIZE;
	$line_x_start = ($page_width - $line_size) / 2;
	$line_x_end = $line_x_start + IMAGE_SIZE;
	$line_y_start = $pdf->getY();
	$pdf->Line($line_x_start, $line_y_start, $line_x_end, $line_y_start);
	$pdf->Ln();
}

function display_payment_info($pdf, $payment_info) {
	$product_id = $payment_info['product_id'];
	$payment_amt = $payment_info['payment_amt'];
	$commission_val = $payment_info['commission_val'];
	$commission_amt = $payment_info['commission_amt'];
	$vat_val = $payment_info['vat_val'];
	$vat_amt = $payment_info['vat_amt'];

	$pdf->SetFont('', 'B');
	center($pdf, 'Invoice for our Marketing Services');

	display_title_info($pdf, 'Campaign ID:', $product_id);

	display_title_info($pdf, 'Staged Payment of:', disp_euros($payment_amt));

	display_title_info($pdf, "Commission @ {$commission_val}%",  disp_euros($commission_amt));
	
	display_title_info($pdf, "Vat @ {$vat_val}%", disp_euros($vat_amt));
}

function disp_euros($amt) {
	return EURO . number_format(floatval($amt), 2);
}

function display_title_info($pdf, $title, $info) {
	// displays the pattern of a centered title in bold
	// with the info displayed below.  
	$pdf->Ln();
	$pdf->SetFont('', 'B');
	center($pdf, $title);
	$pdf->SetFont('');
	center($pdf, $info);
}

function center($pdf, $txt) {
	$pdf->Cell(190, LN_HEIGHT, $txt, 0, 1, 'C');
}

