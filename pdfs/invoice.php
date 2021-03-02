<?php

set_time_limit(10 * 60);

require('fpdf/fpdf.php');

define('LN_HEIGHT', 6);
define('IMAGE_SIZE', 42);
define('IMAGE_START_X', 10);
define('IMAGE_START_Y', 10);
define('FONT_STYLE', 'Arial');
define('FONT_SIZE', 12);
define('PAGE_WIDTH', 210);
define('BOX_WIDTH', 80);
define('EURO', chr(128));

// get POST, otherwise die
 if (!isset($_GET['product_id']) || !$_GET['product_id'] ||
		 !isset($_GET['pay_amt']) || !$_GET['pay_amt'] ||
		 !isset($_GET['pay_date']) || !$_GET['pay_date'] ||
		 !isset($_GET['venue_name']) || !$_GET['venue_name'] ||
		 !isset($_GET['comm_val']) || !isset($_GET['vat_val']) ||
		 !isset($_GET['comm_amt']) || !isset($_GET['vat_amt'])
		) {
	die("invalid parameters to create invoice");
} else {
	$venue_info = array(
		'name' => $_GET['venue_name'],
		'address' => array(
			$_GET['venue_addr1'],
			$_GET['venue_addr2'],
			$_GET['venue_city'],
			$_GET['venue_postal'],
		)
	);

	$payment_info = array(
		'product_id' => $_GET['product_id'],
		'payment_amt' => $_GET['pay_amt'],
		'payment_ln' => $_GET['pay_ln'],
		'payment_date' => $_GET['pay_date'],
		'commission_val' => $_GET['comm_val'],
		'vat_val' => $_GET['vat_val'],
		'commission_amt' => $_GET['comm_amt'],
		'vat_amt' => $_GET['vat_amt'],
	);

}

$pdf = set_up_pdf();
$pdf->AddPage();
$pdf->SetFont(FONT_STYLE,'',FONT_SIZE);
$cur_y = display_logo($pdf);

display_venue_info($pdf, $cur_y, $venue_info);

display_company_tax_info($pdf, $payment_info);

display_horizontal_line($pdf);

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

function display_logo($pdf) {
	$company_title = "Digital Food Ltd. T/A TheTaste.ie";
	$company_addr = array(
		'The Chq Building,',
		'DogpatchLabs Office',
		'Unit 1, North Wall Quay',
		'North Wall',
		'Dublin 1',
	);
	
	$pdf->Image('images/TheTasteLogo.png', IMAGE_START_X, IMAGE_START_Y, IMAGE_SIZE, IMAGE_SIZE);

	$y = IMAGE_START_Y + 5;

	$pdf->setXY(0, $y);

	$pdf->setFont('', 'B', 10);
	$pdf->Cell(PAGE_WIDTH - 10, 5, $company_title, 0, 1, 'R');

	$pdf->setX(0);

	$pdf->SetFont('');
	foreach($company_addr as $addr_ln) {
		$pdf->setX(0);
		$pdf->Cell(PAGE_WIDTH - 10, 5, $addr_ln, 0, 1, 'R');
	}
	$pdf->SetFont('', '', FONT_SIZE);

	return IMAGE_SIZE + IMAGE_START_Y + 8;
}

function display_venue_info($pdf,  $y_loc, $venue_info) {
	$pdf->setY($y_loc);
	
	display_title_info($pdf, 'Client:', $venue_info['name']);

	foreach($venue_info['address'] as $addr_line) {
		if (trim($addr_line)) {
			center($pdf, $addr_line);
		}
	}

}

function display_company_tax_info($pdf, $payment_info) {
	$company_no = '548735';
	$company_vat_no = '3312776JH';
	$date = $payment_info['payment_date'];
	$product_id = $payment_info['product_id'];
	$payment_ln = $payment_info['payment_ln'];
	$invoice_no = $product_id . '-' . $payment_ln;
	
	display_title_info($pdf, 'Company No:', $company_no);
	
	display_title_info($pdf, 'Invoice No:', $invoice_no);
	
	display_title_info($pdf, 'VAT No:', $company_vat_no);

	display_title_info($pdf, 'Date:', $date);
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
	$pdf->Ln();

	center($pdf, 'Campaign ID: ' . $product_id);
	$pdf->Ln();

	$table_width = BOX_WIDTH;
	$table_height = LN_HEIGHT + 4;
	$table_x_start = (PAGE_WIDTH - $table_width) / 2;

	$pdf->setX($table_x_start);
	$pdf->Cell(50, $table_height, ' Staged Payment of:', 1);
	$pdf->Cell(30, $table_height, ' ' . disp_euros($payment_amt), 1, 2);

	$pdf->setX($table_x_start);
	$pdf->Cell(50, $table_height, " Commission @ {$commission_val}%", 1);
	$pdf->Cell(30, $table_height, ' ' . disp_euros($commission_amt), 1, 2);

	$pdf->setX($table_x_start);
	$pdf->Cell(50, $table_height, " Vat @ {$vat_val}%", 1);
	$pdf->Cell(30, $table_height, ' ' . disp_euros($vat_amt), 1, 2);

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

function display_horizontal_line($pdf) {
	$pdf->Ln();
	$pdf->Ln();
	$line_size = BOX_WIDTH;
	$line_x_start = (PAGE_WIDTH - $line_size) / 2;
	$line_x_end = $line_x_start + BOX_WIDTH;
	$line_y_start = $pdf->getY();
	$pdf->Line($line_x_start, $line_y_start, $line_x_end, $line_y_start);
	$pdf->Ln();
	$pdf->Ln();
}

