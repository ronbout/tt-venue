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

function disp_payment_line($payment, $admin, $commission_val) {
	$show_delete = false;
	$payment_date = date('Y-m-d', strtotime($payment['timestamp']));
	ob_start();
	?>
		<tr id="pay-<?php echo $payment['id'] ?>">
		<?php echo $admin ? "<th scope='row'>{$payment['id']}</th>" : '' ?>
		<td><?php echo $payment_date ?></td>
		<td><?php echo get_woocommerce_currency_symbol() . ' ' . number_format($payment['amount'], 2)	?></td>
		<?php
			$pay_calcs = comm_vat_per_payment($payment['amount'], $commission_val, $payment_date)
		?>
			<td>
				<i data-paymentamt="<?php echo $payment['amount'] ?>" data-paymentdate="<?php echo $payment_date ?>"
								data-comm="<?php echo $pay_calcs['pay_comm'] ?>" data-vat="<?php echo $pay_calcs['pay_vat'] ?>"
								data-paymentid="<?php echo $payment['id'] ?>" data-paymentvatval="<?php echo $pay_calcs['vat_val'] ?>"
								class="fas fa-file-pdf print-invoice-btn"></i>
				</i>
			</td>
			<?php if ($admin) {	?>
			<td>
				<button type="button" class="btn btn-primary payment-comment-btn" data-toggle="modal" 
								data-target="#addCommentModal" 
								data-paymentid="<?php echo $payment['id'] ?>" data-paymentdate="<?php echo $payment_date ?>" 
								data-paymentamt="<?php echo $payment['amount'] ?>" data-comment="<?php echo $payment['comment'] ?>" 
								>
					Comment
				</button>
			</td>
			<td>
				<i data-toggle="modal" data-target="#addEditPaymentModal"
						data-paymentid="<?php echo $payment['id'] ?>" data-paymentdate="<?php echo $payment_date ?>" 
						data-paymentamt="<?php echo $payment['amount'] ?>" data-comment="<?php echo $payment['comment'] ?>"
						class="fas fa-pencil-alt edit-payment-btn"></i>
			</td>
			<?php if ($show_delete) { echo '<td><i class="fas fa-trash-alt"></i></td>'; } ?>
		<?php
	}
	?>
	</tr>
	<?php
	$payment_line = ob_get_clean();
	return $payment_line;
}
