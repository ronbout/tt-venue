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
	'sku' => 'SKU',
	'status' => 'Status',
	'revenue' => 'Revenue',
	'redeemed_qty' => 'Redeemed',
	'order_qty' => 'Vouchers</br>Sold',
	'sales_amt' => 'Gross</br>Sales',
	'coupon_amt' => 'Coupon Amount',
	'net_sales' => 'Net</br>Sales',
	'commission' => 'Commission',
	'vat' => 'VAT',
	'paid_amount' => 'Amount</br>Paid',
	'payment_list' => 'Payment</br>Listing',
	'min_order_date' => 'Min Order</br>Date',
	'max_order_date' => 'Max Order</br>Date',
	'venue_name' => 'Venue Name',
	'product_date' => 'Product</br>Date',
	'net_payable' => 'Net</br>Payable',
	'unredeemed_income' => 'Unredeemed</br>Income',
	'balance_due' => 'Balance</br>Due',
	'total_income' => 'Total</br>Income',
	'profit_margin' => 'Income /</br>Sales',
);

$out_default_product_columns = array(
	'product_id',
	'status',
	'revenue',
	'redeemed_qty',
	'sales_amt',
	'coupon_amt',
	'net_sales',
	'min_order_date',
	'venue_name',
	'product_date',
	'net_payable',
	'balance_due',
);