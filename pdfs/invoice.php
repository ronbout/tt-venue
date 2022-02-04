<?php

set_time_limit(10 * 60);

require('fpdf/fpdf.php');

define('LN_HEIGHT', 6);
define('TABLE_LN_HEIGHT', 4);
define('IMAGE_SIZE', 42);
define('IMAGE_HEIGHT', 18);
define('IMAGE_WIDTH', 68);
define('IMAGE_START_X', 10);
define('IMAGE_START_Y', 10);
define('FONT_STYLE', 'Arial');
define('FONT_SIZE', 12);
define('PAGE_WIDTH', 210);
define('BREAK_WIDTH', 100);
define('BOX_WIDTH', 178);
define('PRODUCT_ID_WIDTH', 30);
define('GROSS_SALES_WIDTH', 30);
define('COMM_WIDTH', 30);
define('COMM_PCT_WIDTH', 26);
define('VAT_WIDTH', 26);
define('COMM_AND_VAT_WIDTH', 32);
define('STAGED_PAY_WIDTH', 30);
define('EURO', chr(128));

// get POST, otherwise die
 if (!isset($_GET['pay_id']) || !$_GET['pay_id'] ) {
	die("Payment Id parameter missing.");
} 

// need to get enough WordPress to use the db functions
require_once '../../../../wp-load.php';
//As this is external file, we aren't using the WP theme here. So setting this as false
define('WP_USE_THEMES', false);

// require_once TASTE_PLUGIN_INCLUDES.'/ajax/outstanding/ajax-functions.php';

$payment_id = $_GET['pay_id'];

$venue_info = get_venue_info($payment_id);

// var_dump($venue_info);

// $payment_info = array(
// 	'product_id' => $_GET['product_id'],
// 	'payment_amt' => $_GET['pay_amt'],
// 	'payment_gross' => $_GET['pay_gross'],
// 	'payment_id' => $_GET['pay_id'],
// 	'payment_date' => $_GET['pay_date'],
// 	'commission_val' => $_GET['comm_val'],
// 	'vat_val' => $_GET['vat_val'],
// 	'commission_amt' => $_GET['comm_amt'],
// 	'vat_amt' => $_GET['vat_amt'],
// );

$payment_info = get_payment_info($payment_id);

$pdf = set_up_pdf();
$pdf->AddPage();
$pdf->SetFont(FONT_STYLE,'',FONT_SIZE);
$cur_y = display_logo($pdf);

display_venue_info($pdf, $cur_y, $venue_info);

display_company_tax_info($pdf, $payment_info);

display_horizontal_line($pdf);

display_payment_info($pdf, $payment_info);

display_order_info($pdf, $payment_info);

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
	$company_title = "JFG Digital Ltd T/A TheTaste.ie";
	$company_addr = array(
		'The Chq Building,',
		'DogpatchLabs Office',
		'Unit 1, North Wall Quay',
		'North Wall',
		'Dublin 1',
	);
	
	// $pdf->Image('images/TheTasteLogo.png', IMAGE_START_X, IMAGE_START_Y, IMAGE_SIZE, IMAGE_SIZE);
	$pdf->Image('images/TheTaste-logo-2022.png', IMAGE_START_X, IMAGE_START_Y + 5, IMAGE_WIDTH, IMAGE_HEIGHT);

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

	return IMAGE_SIZE + IMAGE_START_Y + 4;
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
	$company_no = '681166';
	$company_vat_no = '3738046TH';
	$date = $payment_info['payment_date'];
	$invoice_no = $payment_info['payment_id'];
	
	$pdf->Ln();
	$pdf->SetFont('', 'B');
	$pdf->Cell(95, LN_HEIGHT, 'Company No:', 0, 0, 'C');
	$pdf->Cell(95, LN_HEIGHT, 'Invoice No:', 0, 1, 'C');
	$pdf->SetFont('');
	$pdf->Cell(95, LN_HEIGHT, $company_no, 0, 0, 'C');
	$pdf->Cell(95, LN_HEIGHT, $invoice_no, 0, 1, 'C');
	// display_title_info($pdf, 'Company No:', $company_no);
	
	// display_title_info($pdf, 'Invoice No:', $invoice_no);
	
	$pdf->Ln();
	$pdf->SetFont('', 'B');
	$pdf->Cell(95, LN_HEIGHT, 'VAT No:', 0, 0, 'C');
	$pdf->Cell(95, LN_HEIGHT, 'Date:', 0, 1, 'C');
	$pdf->SetFont('');
	$pdf->Cell(95, LN_HEIGHT, $company_vat_no, 0, 0, 'C');
	$pdf->Cell(95, LN_HEIGHT, $date, 0, 1, 'C');
	
	// display_title_info($pdf, 'VAT No:', $company_vat_no);

	// display_title_info($pdf, 'Date:', $date);
}

function display_payment_info($pdf, $payment_info) {
	$vat_val = $payment_info['product_list'][0]['vat_val'];

	$pdf->SetFont('', 'B');
	center($pdf, 'Invoice for our Marketing Services');
	$pdf->Ln();
	$pdf->SetFont('');

	$table_width = BOX_WIDTH;
	$table_height = LN_HEIGHT + 4;
	$table_x_start = (PAGE_WIDTH - $table_width) / 2;
	$label_width = $table_width - 30;

	display_payment_table_header($pdf, $vat_val);

	$pdf->SetFont('','',FONT_SIZE - 1);
	foreach($payment_info['product_list'] as $payment_row) {
		display_payment_table_row($pdf, $payment_row);
	}
	$pdf->SetFont('','',FONT_SIZE);

	if (count($payment_info['product_list']) > 1) {
		display_payment_table_totals($pdf, $payment_info);
	}

}

function display_payment_table_header($pdf, $vat_val) {
	$table_width = BOX_WIDTH;
	$min_header_height = LN_HEIGHT;
	$header_height = ($min_header_height * 2);
	$table_x_start = (PAGE_WIDTH - $table_width) / 2;
	$table_y_start = $pdf->getY();

	$pdf->SetFont('', 'B');

	$pdf->setX($table_x_start);
	$pdf->Cell(PRODUCT_ID_WIDTH, $header_height , " Campaign ID", 1, 0, "C");
	$pdf->Cell(GROSS_SALES_WIDTH, $header_height, " Gross Sales", 1, 0, "C");
	$pdf->Cell(COMM_WIDTH, $header_height, " Comm", 1, 0, "C");
	$pdf->Cell(VAT_WIDTH, $header_height, "Vat @ {$vat_val}%", 1, 0, "C");
	$pdf->Cell(COMM_AND_VAT_WIDTH, $header_height, " Comm & VAT", 1, 0, "C");
	$tmp_x = $pdf->getX();
	$pdf->Cell(STAGED_PAY_WIDTH, $min_header_height, " Staged", "LTR", 0, "C");
	$pdf->setXY($tmp_x, $table_y_start + $min_header_height);
	$pdf->Cell(STAGED_PAY_WIDTH, $min_header_height, " Payment", "LBR", 1, "C");
	
	$pdf->SetFont('');
}

function display_payment_table_row($pdf, $payment_row) {
	$table_width = BOX_WIDTH;
	$min_table_row_height = TABLE_LN_HEIGHT;
	$row_height = ($min_table_row_height * 2);
	$table_x_start = (PAGE_WIDTH - $table_width) / 2;

	$product_id = $payment_row['product_id'];
	$payment_gross = $payment_row['payment_gross'];
	$commission_val = $payment_row['commission_val'];
	$vat_val = $payment_row['vat_val'];
	$commission_amt = $payment_row['commission_amt'];
	$vat_amt = $payment_row['vat_amt'];
	$payment_amt = $payment_row['payment_amt'];
	$tot_comm_w_vat = floatval($commission_amt) + floatval($vat_amt);

	$pdf->setX($table_x_start);
	$pdf->Cell(PRODUCT_ID_WIDTH, $row_height , $product_id, 1, 0, "C");
	$pdf->Cell(GROSS_SALES_WIDTH, $row_height, disp_euros($payment_gross), 1, 0, "C");
	$pdf->Cell(COMM_WIDTH, $row_height, disp_euros($commission_amt) . " " . "($commission_val%)", 1, 0, "C");
	$pdf->Cell(VAT_WIDTH, $row_height, disp_euros($vat_amt), 1, 0, "C");
	$pdf->Cell(COMM_AND_VAT_WIDTH, $row_height, disp_euros($tot_comm_w_vat), 1, 0, "C");
	$pdf->Cell(STAGED_PAY_WIDTH, $row_height, disp_euros($payment_amt), 1, 1, "C");

}

function display_payment_table_totals($pdf, $payment_info) {
	$table_width = BOX_WIDTH;
	$min_table_row_height = TABLE_LN_HEIGHT;
	$row_height = ($min_table_row_height * 2);
	$table_x_start = (PAGE_WIDTH - $table_width) / 2;

	$total_payment_amt = $payment_info['total_payment_amt'];
	$total_payment_gross  = $payment_info['total_payment_gross'];
	$total_commission_amt = $payment_info['total_commission_amt'];
	$total_vat_amt = $payment_info['total_vat_amt'];
	$grand_tot_comm_w_vat = $payment_info['grand_tot_comm_w_vat'];

	$pdf->setX($table_x_start);
	$pdf->SetFont('', 'B');
	$pdf->Cell(PRODUCT_ID_WIDTH, $row_height , Totals, 1, 0, "C");
	$pdf->SetFont('', '');
	$pdf->Cell(GROSS_SALES_WIDTH, $row_height, disp_euros($total_payment_gross), 1, 0, "C");
	$pdf->Cell(COMM_WIDTH, $row_height, disp_euros($total_commission_amt), 1, 0, "C");
	$pdf->Cell(VAT_WIDTH, $row_height, disp_euros($total_vat_amt), 1, 0, "C");
	$pdf->Cell(COMM_AND_VAT_WIDTH, $row_height, disp_euros($grand_tot_comm_w_vat), 1, 0, "C");
	$pdf->Cell(STAGED_PAY_WIDTH, $row_height, disp_euros($total_payment_amt), 1, 1, "C");

}

function display_order_info($pdf, $payment_info) {
	$pdf->AddPage();

	$pdf->SetFont('', 'B');
	center($pdf, 'Paid Orders by Campaign ID');
	$pdf->Ln();
	$pdf->SetFont('');

	display_order_table_header($pdf);

	$pdf->SetFont('','',FONT_SIZE - 1);
	foreach($payment_info['product_list'] as $payment_row) {
		display_order_table_row($pdf, $payment_row);
	}
	$pdf->SetFont('','',FONT_SIZE);

}

function display_order_table_header($pdf) {
	$table_width = BOX_WIDTH;
	$min_header_height = LN_HEIGHT;
	$header_height = ($min_header_height * 2);
	$table_x_start = (PAGE_WIDTH - $table_width) / 2;
	$order_col_width = $table_width - PRODUCT_ID_WIDTH;

	$pdf->SetFont('', 'B');

	$pdf->setX($table_x_start);
	$pdf->Cell(PRODUCT_ID_WIDTH, $header_height , " Campaign ID", 1, 0, "C");
	$pdf->Cell($order_col_width, $header_height, " Order ID's", 1, 1, "C");
	
	$pdf->SetFont('');
}

function display_order_table_row($pdf, $payment_row) {
	$table_width = BOX_WIDTH;
	$min_table_row_height = TABLE_LN_HEIGHT;
	$row_height = ($min_table_row_height * 2);
	$table_x_start = (PAGE_WIDTH - $table_width) / 2;
	$order_col_width = $table_width - PRODUCT_ID_WIDTH;
	$order_col_x_start = $table_x_start + PRODUCT_ID_WIDTH;

	$product_id = $payment_row['product_id'];

	$order_ids = str_replace(',', ', ', $payment_row['order_ids']);

	$order_ids = $order_ids ? $order_ids : "Orders were not attached to this payment";

	$pdf->setX($order_col_x_start);
	$cur_y = $pdf->getY();

	$pdf->MultiCell($order_col_width, $row_height, $order_ids, 1);

	$new_y = $pdf->getY();
	$cell_height = $new_y - $cur_y;
	$pdf->setXY($table_x_start, $cur_y);
	$pdf->Cell(PRODUCT_ID_WIDTH, $cell_height , $product_id, 1, 1, "C");
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
	$line_size = BREAK_WIDTH;
	$line_x_start = (PAGE_WIDTH - $line_size) / 2;
	$line_x_end = $line_x_start + BREAK_WIDTH;
	$line_y_start = $pdf->getY();
	$pdf->Line($line_x_start, $line_y_start, $line_x_end, $line_y_start);
	$pdf->Ln();
	$pdf->Ln();
}

function get_venue_info($payment_id) {
	global $wpdb;

	$sql = "
	SELECT venue.venue_id, venue.name, venue.address1, venue.address2, venue.city, venue.postcode
		FROM {$wpdb->prefix}taste_venue_payment vpay
		JOIN {$wpdb->prefix}taste_venue venue ON venue.venue_id = vpay.venue_id
		WHERE vpay.id = %d
	";

	$venue_row = $wpdb->get_results($wpdb->prepare($sql, $payment_id), ARRAY_A);

	$venue_info = array(
		'name' => $venue_row[0]['name'],
		'address' => array(
			$venue_row[0]['address1'],
			$venue_row[0]['address2'],
			$venue_row[0]['city'],
			$venue_row[0]['postcode'],
		)
	);

	return $venue_info;

}

function get_payment_info($payment_id) {
	global $wpdb;

	$payment_table = $wpdb->prefix."taste_venue_payment";
	$payment_products_table = $wpdb->prefix."taste_venue_payment_products";
	$payment_order_xref_table = $wpdb->prefix."taste_venue_payment_order_item_xref";
	$v_p_join_table = $wpdb->prefix."taste_venue_products";
	$product_order_table = $wpdb->prefix."wc_order_product_lookup";
	$postmeta_table = $wpdb->prefix."postmeta";

	$sql = "
			SELECT  pprods.product_id, pay.amount as total_amount, pprods.amount as product_amount, 
				pay.comment, pay.payment_date, pm1.meta_value AS 'comm_val',
				GROUP_CONCAT(plook.order_item_id) as order_item_ids,
				GROUP_CONCAT(plook.product_qty) as order_item_qty,
				GROUP_CONCAT(plook.order_id) as order_ids
			FROM $payment_products_table pprods
				JOIN $payment_table pay ON pay.id = pprods.payment_id
				JOIN $v_p_join_table vp ON vp.product_id = pprods.product_id
				LEFT JOIN $payment_order_xref_table pox ON pox.payment_id = pay.id
				LEFT JOIN $product_order_table plook ON plook.order_item_id = pox.order_item_id
					AND plook.product_id = pprods.product_id
				LEFT JOIN $postmeta_table pm1 ON pprods.product_id = pm1.post_id AND pm1.meta_key = 'commission'
			WHERE pay.id = %d
			AND pay.status = " . TASTE_PAYMENT_STATUS_PAID . "
			GROUP BY pprods.product_id
			ORDER BY pay.id, pprods.product_id DESC, pay.payment_date ASC
	";

	$payment_rows = $wpdb->get_results($wpdb->prepare($sql, $payment_id), ARRAY_A);

	if (!count($payment_rows)) {
		die("Payment Id is either does not exist or does not have a Paid status.");
	}
	
	$payment_info = process_payment_info($payment_rows, $payment_id); 

	// var_dump($payment_info);
	// die();
	return $payment_info;

}

function process_payment_info($payment_rows, $payment_id) {
	$total_net_payable = $payment_rows[0]['total_amount'];
	$total_payment_gross = 0;
	$total_commission_amt = 0;
	$total_vat_amt = 0;
	$grand_tot_comm_w_vat = 0;
	$total_qty = 0;
	$product_list = array();
	$payment_date = $payment_rows[0]['payment_date'];
	$payment_date_display = date('Y-m-d', strtotime($payment_date));

	foreach($payment_rows as $payment_row) {
		$product_id = $payment_row['product_id'];
		$tmp_order_array = array();
		$net_payable = $payment_row['product_amount'];
		$comm_val = $payment_row['comm_val'];
		$order_item_id_list = $payment_row['order_item_ids'];
		$order_item_id_array = explode(',', $order_item_id_list);
		$order_id_list = $payment_row['order_ids'];
		$order_id_array = explode(',', $order_id_list);
		$order_qty_list = $payment_row['order_item_qty'];
		$order_qty_array = explode(',', $order_qty_list);
		$order_qty = array_sum($order_qty_array);
		$total_qty += $order_qty;

		$net_pay_per_qty = $net_payable / $order_qty;

		foreach($order_item_id_array as $key => $order_item_id) {
			$tmp_order_array[] = array(
				'order_item_id' => $order_item_id,
				'order_id' => $order_id_array[$key],
				'order_qty' => $order_qty_array[$key],
				'order_net_payable' => round($order_qty_array[$key] * $net_pay_per_qty, 2)
			);
		}

		$payment_calcs = comm_vat_per_payment($net_payable, $comm_val, $payment_date);
		$payment_gross = $payment_calcs['pay_gross'];
		$comm_amt = $payment_calcs['pay_comm'];
		$vat_amt = $payment_calcs['pay_vat'];
		$total_payment_gross += $payment_gross;
		$total_commission_amt += $comm_amt;
		$total_vat_amt += $vat_amt;
		$grand_tot_comm_w_vat += floatval($comm_amt) + floatval($vat_amt);

		$tmp_array = array(
			'product_id' => $product_id,
			'payment_amt' => $net_payable,
			'payment_gross' => $payment_gross,
			'commission_val' => $comm_val,
			'vat_val' => $payment_calcs['vat_val'],
			'commission_amt' => $payment_calcs['pay_comm'],
			'vat_amt' => $payment_calcs['pay_vat'],
			'order_qty' => $order_qty,
			'order_item_list' => $tmp_order_array,
			'order_ids' => $order_id_list,
		);
		$product_list[] = $tmp_array; 
	}
	return array(
		'payment_id' => $payment_id,
		'total_payment_amt' => $total_net_payable,
		'total_payment_gross' => $total_payment_gross,
		'total_commission_amt' => $total_commission_amt,
		'grand_tot_comm_w_vat' => $grand_tot_comm_w_vat,
		'total_vat_amt' => $total_vat_amt,
		'payment_date' => $payment_date_display,
		'product_list' => $product_list,
	);
	
}

