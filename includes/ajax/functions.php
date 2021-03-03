<?php 
/**
 *  functions.php
 *  used for functions that only used in ajax code
 *  3/3/2021  Ron Boutilier
 * 
 */

 
function comm_vat_per_payment($payment, $commission_val, $payment_date) {
	// VAT rate is a set amount in Ireland that rarely changes.  It should
	// not be attached to the product.  Due to a temp change in the rate to
	// mitigate the economic damages of covid, this code needs to include that
	// 23% - normal rate   21% rate from 09/01/2020 to 2/28/2021

	$vat_start_date = strtotime("2020-09-01");
	$vat_end_date = strtotime("2021-02-28");
	$pay_date_comp = strtotime($payment_date);

	$vat_val = $pay_date_comp >= $vat_start_date && $pay_date_comp <= $vat_end_date ? 21 : 23;
	$comm_pct = $commission_val / 100;
	$vat_pct = $vat_val / 100;
	$gross = $payment / (1 - $comm_pct - ($comm_pct * $vat_pct));
	$commission = round($gross * $comm_pct, 2);
	$vat = round($commission * $vat_pct, 2);
	return array(
		'pay_gross' => round($gross, 2),
		'pay_comm' => $commission,
		'pay_vat' => $vat,
		'vat_val' => $vat_val
	);
}
