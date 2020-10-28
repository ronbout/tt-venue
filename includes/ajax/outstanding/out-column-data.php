<?php
/**
 * 	column-data.php
 *  shared array for the customizable columns
 * 	various reporting page templates
 * 	10/23/2020	Ron Boutilier
 */

defined('ABSPATH') or die('Direct script access disallowed.');

$outstanding_product_columns = array(
	'product_id' => 'ID',
	'title' => 'Offer',
	'status' => 'Status',
	'redeemed_qty' => 'Redeemed',
	'order_qty' => 'Vouchers</br>Sold',
	'sales_amt' => 'Total</br>Sales',
	'revenue' => 'Revenue',
	'commission' => 'Commission',
	'vat' => 'VAT',
	'net_payable' => 'Net</br>Payable',
	'paid_amount' => 'Amount</br>Paid',
	'balance_due' => 'Balance</br>Due',
	'min_order_date' => 'Min Order</br>Date',
	'max_order_date' => 'Max Order</br>Date',
	'product_date' => 'Product</br>Date',
	'venue_name' => 'Venue Name',
	'unredeemed_income' => 'Unredeemed</br>Income',
	'total_income' => 'Total</br>Income',
	'profit_margin' => 'Income /</br>Sales',
);

$out_default_product_columns = array(
	'product_id',
	'title',
	'status',
	'revenue',
	'redeemed_qty',
	'min_order_date',
	'venue_name',
	'product_date',
	'net_payable',
	'balance_due',
);