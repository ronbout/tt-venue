<?php
/**
 * 	out-column-data.php
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
	'revenue' => 'Venue Revenue',
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

$outstanding_order_columns = array(
	'product_id' => 'Product Id',
	'order_id' => 'Order Id',
	'order_item_id' => 'Order</br>Item Id',
	'order_status' => 'Order Status',
	'customer_name' => 'Customer Name',
	'customer_email' => 'Customer Email',
	'redeemed' => 'Redeemed',
	'qty' => 'Quantity',
	'price' => 'Product</br>Price',
	'paid_amt' => 'Customer</br>Amount Paid',
	'taste_gross_revenue' => 'TheTaste Gross</br>Revenue',
	'coupon_amt' => 'Coupon</br>Amount',
	'order_date' => 'Order Date',
	'expired' => 'Expired',
	'venue_id' => 'Venue Id',
	'venue_name' => 'Venue Name',
);

$out_default_order_columns = array(
	'product_id',
	'order_id',
	'order_item_id',
	'order_status',
	'redeemed',
	'qty',
	'price',
	'paid_amt',
	'coupon_amt',
	'order_date',
	'venue_name',
);