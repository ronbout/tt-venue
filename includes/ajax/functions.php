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
	// need to recalc the gross to avoid rounding issues 
	$gross = $payment + $commission + $vat;
	return array(
		'pay_gross' => $gross,
		'pay_comm' => $commission,
		'pay_vat' => $vat,
		'vat_val' => $vat_val
	);
}

function disp_payment_line($payment, $admin, $commission_val) {
	$payment_date = date('Y-m-d', strtotime($payment['timestamp']));
	//  determine comment to display.  if venue and not visible flag or empty, show "Payment"
	
	$total_payment_amount = $payment['total_amount'];
	$comment = $payment['comment'];
	$show_comment_venue = $payment['comment_visible_venues'];
	$disp_comment = (empty($comment) || (! $show_comment_venue && ! $admin)) ? "Payment" : $comment;
	$disp_invoice = $payment['attach_vat_invoice'];
	$order_list = $payment['order_item_ids'];
	
	$payment_status = $payment['status'];
	$payment_status_display = payment_status_to_string($payment_status);

	$tooltip_title = "Payment ID: {$payment['id']} &#10;";
	$tooltip_title .= "Total Payment: $total_payment_amount &#10;";
	$tooltip_title .= "Payment Status: $payment_status_display &#10;";


	ob_start();
	?>
<tr id="pay-<?php echo $payment['id'] ?>" title="<?php echo $tooltip_title ?>">
  <?php echo $admin ? "<th scope='row'>{$payment['id']}</th>" : '' ?>
  <td><?php echo $payment_date ?></td>
  <td class="table-nbr"><?php echo get_woocommerce_currency_symbol() . ' ' . number_format($payment['amount'], 2)	?>
  </td>
  <?php
			$pay_calcs = comm_vat_per_payment($payment['amount'], $commission_val, $payment_date);
		?>
  <?php if ($disp_invoice) { ?>
  <td class="text-success">
    <i data-paymentid="<?php echo $payment['id'] ?>" class="fas fa-file-pdf print-invoice-btn"></i>
    </i>
  </td>
  <?php } else { ?>
  <td>

  </td>
  <?php } ?>
  <td>
    <?php echo $admin ? $comment : $disp_comment	?>
  </td>
  <?php if ($admin && !$order_list) {	?>
  <td class="text-primary">
    <i data-toggle="modal" data-target="#addEditPaymentModal"
      <?php build_editable_payment_data_attrs($payment, $payment_date) ?>
      class="fas fa-pencil-alt edit-payment-btn"></i>
  </td>
  <td class="text-danger">
    <i data-toggle="modal" data-target="#addEditPaymentModal"
      <?php build_editable_payment_data_attrs($payment, $payment_date, true) ?> class="fas fa-trash-alt"></i>
  </td>
  <?php } else { ?>
  <td>

  </td>
  <td>

  </td>
  <?php
			}
	?>
</tr>
<?php
	$payment_line = ob_get_clean();
	return $payment_line;
}

function disp_all_payment_line($payment) {
	$payment_date = date('Y-m-d', strtotime($payment['timestamp']));
	$total_payment_amount = $payment['total_amount'];
	$payment_id = $payment['id'];
	$order_item_list = str_replace(',', ', ', $payment['order_item_ids']);
	
	$payment_status = $payment['status'];
	$payment_status_display = payment_status_to_string($payment_status);
	
	$disp_invoice = $payment['attach_vat_invoice'] && TASTE_PAYMENT_STATUS_PAID == $payment_status;

	$tooltip_title = "Payment ID: $payment_id &#10;";
	$tooltip_title .= "Total Payment: $total_payment_amount &#10;";
	$tooltip_title .= "Payment Status: $payment_status_display &#10;";
	$tooltip_title .= $order_item_list ? "Order Items for this Product: &#10; $order_item_list &#10;" : "";
	/**
	 * 
	 *  Need to indication ADJUSTMENT type payment, 
	 *  for now,just add "ADJ" to the payment id display
	 *  This is somewhat experimental for now, so keep the
	 * 	potential re-write to a minimum
	 * 
	 */
	$status_ind = '';

	if (TASTE_PAYMENT_STATUS_ADJ == $payment['status']) {
		$status_ind = ' - Archived';
	}
	if (TASTE_PAYMENT_STATUS_PENDING == $payment['status']) {
		$status_ind = ' - Pending';
	}
	
	if (TASTE_PAYMENT_STATUS_PROCESSING == $payment['status']) {
		$status_ind = ' - Processing';
	}

	ob_start();
	?>
<tr id="all-pay-<?php echo $payment_id ?>-<?php echo $payment['product_id']?>"
  class="all-payments-row all-pay-<?php echo $payment_id ?>" data-toggle="tooltip"
  data-prodamount="<?php echo $payment['amount'] ?>" data-prodid="<?php echo $payment['product_id'] ?>"
  title="<?php echo $tooltip_title ?>">
  <td><?php echo $payment['product_id'] ?></td>
  <td><?php echo $payment_id, $status_ind ?></td>
  <td><?php echo $payment_date ?></td>
  <td class="table-nbr pr-5">
    <?php echo get_woocommerce_currency_symbol() . ' ' . number_format($payment['amount'], 2)	?></td>
  <?php if ($disp_invoice) { ?>
  <td class="text-success">
    <i data-paymentid="<?php echo $payment_id ?>" class="fas fa-file-pdf print-invoice-btn"></i>
    </i>
  </td>
  <?php } else { ?>
  <td>

  </td>
  <?php } ?>
  <?php 
			if ($order_item_list) {
				?>
  <td class="text-primary">
    <i id="edit-pbo-<?php echo $payment_id ?>-<?php echo $payment['product_id']?>"
      class="fas fa-pencil-alt edit-pbo-btn" data-payment-id="<?php echo $payment_id ?>"></i>
  </td>
  <td class="text-danger">
    <i <?php /* data-toggle="modal" data-target="#deletePBOModal" */ ?>
      id="delete-pbo-<?php echo $payment_id ?>-<?php echo $payment['product_id']?>"
      data-payment-id="<?php echo $payment_id ?>" class="fas fa-trash-alt delete-pbo-btn"></i>
  </td>
  <?php
			} else {
				?>
  <td colspan="2">&nbsp;</td>
  <?php
			}
			?>
</tr>
<?php
	$payment_line = ob_get_clean();
	return $payment_line;
}

function build_editable_payment_data_attrs($payment, $payment_date, $delete_flag=false) {
	?>
data-paymentid="<?php echo $payment['id'] ?>" data-paymentdate="<?php echo $payment_date ?>"
data-paymentamt="<?php echo $payment['amount'] ?>" data-comment="<?php echo $payment['comment'] ?>"
data-deletemode="<?php echo $delete_flag ? 'true' : 'false' ?>"
data-commentvisibility="<?php echo $payment['comment_visible_venues'] ?>"
data-invoiceattachment="<?php echo $payment['attach_vat_invoice'] ?>"
<?php
}