<?php 

/**
 * Template partial to display a list of vouchers
 * for a given product, which will be in the args
 */

 
function display_voucher_table($product_id, $multiplier, $cutoff_date) {
	global $wpdb;

	$user = wp_get_current_user();
	$role = $user->roles[0];
	$admin = ('ADMINISTRATOR' === strtoupper($role));

	$order_rows = $wpdb->get_results($wpdb->prepare("
			SELECT p.post_title,
				im.meta_value AS quan,
				wclook.product_id AS productID,
				bf.meta_value AS b_fname,
				bl.meta_value AS b_lname,
				be.meta_value AS b_email,
				i.order_id, i.order_item_id as itemid, i.downloaded as downloaded,i.paid as paid
			FROM " . $wpdb->prefix . "wc_order_product_lookup wclook
			JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta im ON im.order_item_id = wclook.order_item_id
			LEFT JOIN " . $wpdb->prefix . "woocommerce_order_items i ON i.order_item_id = wclook.order_item_id
			LEFT JOIN " . $wpdb->prefix . "posts o ON o.id = wclook.order_id
			JOIN " . $wpdb->prefix . "posts p ON p.id = %d
			LEFT JOIN " . $wpdb->prefix . "postmeta bf ON bf.post_id = wclook.order_id
			LEFT JOIN " . $wpdb->prefix . "postmeta bl ON bl.post_id = wclook.order_id
			LEFT JOIN " . $wpdb->prefix . "postmeta be ON be.post_id = wclook.order_id
			WHERE im.meta_key = '_qty'
			AND bf.meta_key = '_billing_first_name'
			AND bl.meta_key = '_billing_last_name'
			AND be.meta_key = '_billing_email'
			AND o.post_status = 'wc-completed'
			AND o.post_type = 'shop_order'
			AND o.post_date >= %s
			AND wclook.product_id = %d group by o.id", $product_id, $cutoff_date, $product_id));

	$product_row = $wpdb->get_results($wpdb->prepare("
		SELECT  pm.post_id, p.post_title,	v.venue_id, 
						v.name AS venue_name, v.address1, v.address2, v.city,	v.postcode,
						MAX(CASE WHEN pm.meta_key = '_sale_price' then pm.meta_value ELSE NULL END) as price,
						MAX(CASE WHEN pm.meta_key = 'vat' then pm.meta_value ELSE NULL END) as vat,
						MAX(CASE WHEN pm.meta_key = 'commission' then pm.meta_value ELSE NULL END) as commission,
						MAX(CASE WHEN pm.meta_key = 'expired' then pm.meta_value ELSE NULL END) as expired,
						MAX(CASE WHEN pm.meta_key = '_purchase_note' then pm.meta_value ELSE NULL END) as purchase_note

		FROM   {$wpdb->prefix}postmeta pm
		JOIN {$wpdb->prefix}posts p ON p.id = pm.post_id
		LEFT JOIN {$wpdb->prefix}taste_venue_products vp ON vp.product_id = pm.post_id
		LEFT JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
		WHERE pm.post_id = %d                    
		GROUP BY
			pm.post_id
	", $product_id), ARRAY_A);

	$product_price = $product_row[0]['price'];
	$vat_val = $product_row[0]['vat'];
	$commission_val = $product_row[0]['commission'];
	$expired_val = $product_row[0]['expired'];
	$tandc_val = $product_row[0]['purchase_note'];
	$product_title = $product_row[0]['post_title'];
	$venue_id = $product_row[0]['venue_id'];

	$venue_info = array(
		'name' => $product_row[0]['venue_name'],
		'address1' => $product_row[0]['address1'],
		'address2' => $product_row[0]['address2'],
		'city' => $product_row[0]['city'],
		'postcode' => $product_row[0]['postcode'],
	);

	$termsandconditions = str_replace('\r\n','<br>', json_encode($tandc_val));
	$termsandconditions = str_replace('[{"meta_value":"','', $termsandconditions);
	$termsandconditions = str_replace('"}]','', $termsandconditions);
	$termsandconditions = str_replace('(\u20ac80)','', $termsandconditions);
	$termsandconditions = str_replace('<a hef="mailto:','', $termsandconditions);
	$termsandconditions = str_replace('<\/a>','', $termsandconditions);
	$termsandconditions = str_replace('\u20ac','€', $termsandconditions);
	$termsandconditions = str_replace('\u2013','-', $termsandconditions);
	$termsandconditions = str_replace('\u2019','', $termsandconditions);

	$activecampaign = 'N';

	if(strpos($expired_val, 'N') !== false){
		$expired_val = 'N';
	} else{
		$expired_val = 'Y';
	}
	$status_display = ('N' === $expired_val) ?
		'<i class="fas fa-check-circle fa-lg ml-2"></i><span class="ml-3 text-danger">Active</span>' :
		'<i class="fas fa-times-circle fa-lg ml-2"></i><span class="ml-3 text-danger">Expired</span>';

	?>
	<!-- CAMPAIGN SUMMARY START -->

	<div class="collapse-container campaign_summary mt-5">
		<h3 class="text-center">Summary for Campaign <?php echo $product_id . ' - ' . $status_display?></h3>
		<span class="circle-span" data-placement="top" title="Show / Hide" data-toggle="tooltip">
				<i 
				data-toggle="collapse" 
				data-target="#campaign_summary_collapse" 
				aria-expanded="true" 
				aria-controls="campaign_summary_collapse" 
				class="collapse-icon fas fa-minus-circle"></i>
		</span>
		<div class="collapse show" id="campaign_summary_collapse">
			<?php 
			
			display_campaign_header($expired_val, $product_id, $product_title);
			
			echo '<h4>';
				$row_count = count($order_rows);
				if ($row_count)  {
					echo "Orders ($row_count Rows)";
				} else {
					echo "No Orders Found";
				}			
			echo '</h4>';

			// $payable is calc'd inside the table but needs to come out to be used in the payments table
			$order_totals = display_orders_table($order_rows, $expired_val, $product_price, $vat_val, $commission_val );
			$payable = $order_totals['payable'];
			$redeem_qty = $order_totals['redeem_qty'];
			$total_sold = $order_totals['total_sold'];
			$commission = $order_totals['commission'];
			$vat = $order_totals['vat'];
			
			display_terms($termsandconditions);
			?>
		</div>
	</div>
	<!-- CAMPAIGN SUMMARY END -->

	<?php 
	$payment_list = $wpdb->get_results($wpdb->prepare("
				SELECT  id, timestamp, pid, amount, comment, comment_visible_venues, status
				FROM {$wpdb->prefix}offer_payments 
				WHERE pid = %d
				ORDER BY timestamp ASC ", $product_id), ARRAY_A);
	?>
	<div class=" collapse-container payment_transaction mt-5">
		<h3 class="text-center">Transactions for Campaign <?php echo $product_id ?></h3>
		<span class="circle-span" data-placement="top" title="Show / Hide" data-toggle="tooltip">
			<i 
				data-toggle="collapse" 
				data-target="#payment_transaction_collapse" 
				aria-expanded="true" 
				aria-controls="payment_transaction_collapse" 
				class="collapse-icon fas fa-minus-circle"></i>
		</span>
		<div class="collapse show" id="payment_transaction_collapse">
		<h4>
			<?php 
				$row_count = count($payment_list);
				if ($row_count)  {
					echo "Transaction Items ($row_count Rows)";
				} else {
					echo "No Transactions Found";
				}
			?>				
		</h4>

	<?php
		$total_paid_to_customer = display_payments_table($product_id, $payable, $commission_val, $commission, $vat_val, $vat, $admin, $venue_info, $payment_list);
	?>
	<div id="hidden-values">
		<input type="hidden" id="taste-product-id" value="<?php echo $product_id ?>">
		<input type="hidden" id="taste-product-multiplier" value="<?php echo $multiplier ?>">
		<input type="hidden" id="taste-gr-value" value="<?php echo $product_price ?>">
		<input type="hidden" id="taste-commission-value" value="<?php echo $commission_val ?>">
		<input type="hidden" id="taste-vat-value" value="<?php echo $vat_val ?>">
		<input type="hidden" id="taste-redeem-qty" value="<?php echo $redeem_qty ?>">
		<input type="hidden" id="taste-num-served" value="<?php echo ($redeem_qty * $multiplier) ?>">
		<input type="hidden" id="taste-total-sold" value="<?php echo $total_sold ?>">
		<input type="hidden" id="taste-total-paid" value="<?php echo $total_paid_to_customer ?>">
	</div>
	<?php
}

function display_campaign_header($expired_val, $product_id, $product_title) {
	$status_display = ('N' === $expired_val) ?
		'<i class="fas fa-check-circle ml-2"></i><span class="ml-3 text-danger">Expired</span>' :
		'<i class="fas fa-times-circle ml-2"></i><span class="ml-3 text-danger">Expired</span>';
	?>
	<!-- REVENUE CAMPAIGN DETAILS START -->
	<div class="revenue_campaign_details mb-4">
		<div class="row">
			<div class="col cols">
					<p class="lead"><strong><?php echo $product_title ?></strong></p>
			</div>
		</div>
		<div class="row mt-3">
			<div class="col please_note cols">
					<h3>Please Note :</h3>
					<p>
							This management console has 3 unique rules, the first is all payments due to venues
							are for served customers only, by law TheTaste must be able to complete refunds 
							direct to customers who have not been served. The second change you will notice is 
							as a result of the recent GDPR laws meaning we can only disclose the email addresses 
							of the customers you have served. The final change is due to National Consumer Law
							meaning we have to allow 14 days after the campaign validity has expired
							to issue payments.
					</p>
			</div>
		</div>
		<div class="row mt-3">
				<div class="col-md please_note cols">
						<h3>Important :</h3>
						<p>
								By clicking the Redeem button below you are confirming you have fully served that customer and the 
								customer will receive an automatic email thanking them and asking them to share their experience 
								feedback with us. Fraudulently Redeeming Vouchers will expose details of customers 
								below and break GDPR Laws.
						</p>
						<p class="text-danger font-weight-bold">You must retain all paper vouchers for this campaign!</p>
						<p class="text-danger font-weight-bold">
								Fraudulently Redeeming Vouchers will result in a full paper 
								audit of this campaign and Put Your Payment On Hold!
						</p>
				</div>
				<div class="col-md-4 revenue_campaign_info cols">
						<h3>Campaign VAT Statement :</h3>
						<p class="campaign_details">
								JFG Digital Ltd T/A TheTaste.ie<br/><br/>
								5 Main Street, Rathangan, Co. Kildare<br/><br/>
								Company No 548735<br/><br/>
								VAT No 3312776JH<br/><br/>
						</p>
				</div>
		</div>
	</div>
<!-- REVENUE CAMPAIGN DETAILS END -->
	<?php
}

function display_orders_table($order_rows, $expired_val, $product_price, $vat_val, $commission_val ) {
	// display the orders table 
	$total_sold = 0;
	$redeem_qty = 0;
	$tproduct = 0;
	if (count($order_rows)) {
				?>
				<?php
					if ($expired_val === 'N') {
						echo '<button class="btn btn-success order-redeem-checked-btn my-2" disabled >Redeem Checked</button>';
					}
				?>
				<div class="table-fixed-wrapper">
					<div id="voucher-table-container" class="table-fixed-container">
						<table class="table table-striped table-bordered table-fixed">
							<?php display_order_table_heading($order_rows, $expired_val) ?>

								<tbody id="voucher-table-body">

								<?php 
								foreach ($order_rows as $order_item_info) {
									$tproduct = $tproduct + 1;							
									$total_sold = $total_sold + $order_item_info->quan;
									if (1 == $order_item_info->downloaded ) {
										$redeem_qty = $redeem_qty + $order_item_info->quan;
									}
									display_order_table_row($order_item_info, $expired_val);
								}
								?>
								</tbody>
						</table>
					</div>
				</div>
				<?php 
				$totals = display_order_table_summary($redeem_qty, $total_sold, $product_price, $commission_val, $vat_val);
				$payable = $totals['payable'];
				$commission = $totals['commission'];
				$vat = $totals['vat'];
			} else  {
				$payable = 0;
				$commission = 0;
				$vat = 0;
			}
			?>
	<?php
	// have to return some of th totals calulated 
	$order_totals = array (
		'payable' => $payable,
		'commission' => $commission,
		'vat' => $vat,
		'redeem_qty' => $redeem_qty,
		'total_sold' => $total_sold,
	);
	return $order_totals;
}

function display_order_table_heading($order_rows, $expired_val) {
	// just the table headers
	?>
		<thead>
			<th scope="col">
				<?php 
					if ($expired_val === 'N' && in_array('0', array_column($order_rows, 'downloaded'))) {
						?>
							<input type="checkbox" id="checkbox-all">
						<?php
					} else {
						echo '';
					}
				?>
			</th>
			<th scope="col">Order ID</th>
			<th scope="col">Customer Name</th>
			<th scope="col">Customer Email</th>
			<th scope="col">Quantity</th>
			<th scope="col">Action</th>
		</thead>
	<?php
}

function display_order_table_row($order_item_info, $expired_val) {
	// display the individual  row
	?>
	<tr data-order-id="<?php echo $order_item_info->order_id ?>" 
			data-order-qty="<?php echo $order_item_info->quan ?>" 
			data-order-item-id="<?php echo $order_item_info->itemid ?>"
	>
		<td id="td-check-order-id-<?php echo $order_item_info->order_id ?>" class="text-center">
			<?php 
				if ($order_item_info->downloaded === '0' && $expired_val === 'N') {
					?>
						<input type="checkbox" class="order-redeem-check">
					<?php
				} else {
					echo '';
				}
			?>
		</td>
		<td><?php echo $order_item_info->order_id ?></td>
		<td><?php echo $order_item_info->b_fname . ' ' . $order_item_info->b_lname ?></td>
		<?php
					if ($order_item_info->downloaded == '1') {
				?>
					<td><span id="email-display-<?php echo $order_item_info->order_id ?>"><?php echo $order_item_info->b_email ?></span></td>
				<?php
					} else {
				?>
					<td><span id="email-display-<?php echo $order_item_info->order_id ?>">*** GDPR BLANKED EMAIL ***</span></td>
				<?php
					}
				?>

		<td class="table-nbr"><?php echo $order_item_info->quan ?> </td>
		<?php $action_class = ('N' == $expired_val) ? 'text-center' : 'pl-3' ?>
		<td id="td-btn-order-id-<?php echo $order_item_info->order_id ?>" class="<?php echo $action_class ?>">
				<?php
				if ('0' == $order_item_info->downloaded) {
						if ('N' == $expired_val) {
							echo '<button	class="btn btn-success order-redeem-btn">Redeem</button>';
						}
						else {
							echo '<span class="notserved">
											<i class="fas fa-times-circle"></i>
											Not Served / Expired
										</span>';
						}
				}	else {
					if ($expired_val == 'N') {
						echo '<button	class="btn btn-info order-unredeem-btn">Unredeem</button>';
					} else {
						echo '<span class="served">
										<i class="fas fa-check-circle"></i>
										Voucher Served
									</span>';
					}
					$redeem_qty = $redeem_qty + $order_item_info->quan;
				}
						?>
		</td> 
	</tr>
	<?php
}

function display_order_table_summary($redeem_qty, $total_sold, $product_price, $commission_val, $vat_val) {
	// the totals at the end
	$grevenue = $redeem_qty * $product_price;
	$commission = ($grevenue / 100) * $commission_val;
	$vat = ($commission / 100) * $vat_val;
	$grevenue = round($grevenue, 2);
	$commission = round($commission, 2);
	$vat = round($vat, 2);
	$payable = $grevenue - ($commission + $vat);
	$payable = round($payable, 2);
	?>
            
	<div class="row mt-3">
		<div class="col-md tcard-lg">
				<h3 class="numbers">
					<span id="redeem-qty-display"><?php echo $redeem_qty ?></span>
				<h3>
				<p class="titles">Out of possible 
					<span id="total-sold-display"><?php echo $total_sold ?></span>
				</p>
				<div class="eclipse_icon_bg users_icon">
						<i class="fas fa-users"></i>
				</div>
		</div>
		<div class="col-md tcard-lg">
				<h3 class="numbers">
					<span id="grevenue-display">
						<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($grevenue, 2)  ?>
					</span>
				<h3>
				<p class="titles">Gross Revenue</p>
				<div class="eclipse_icon_bg money_bill_icon">
						<i class="far fa-money-bill-alt"></i>
				</div>
		</div>
		<div class="col-md tcard">
			<h3 class="numbers">
				<span id="commission-display">
					<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($commission, 2)  ?>
				</span>
			<h3>
			<p class="titles">Commission</p>
			<div class="eclipse_icon_bg coins_icon">
					<i class="fas fa-coins"></i>
			</div>
		</div>
		<div class="col-md tcard">
				<h3 class="numbers">
					<span id="vat-display">
						<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($vat, 2)  ?>
					</span>
				<h3>
				<p class="titles">VAT</p>
				<div class="eclipse_icon_bg balance_scale_icon">
						<i class="fas fa-balance-scale"></i>
				</div>
		</div>
		<div class="col-md tcard">
				<h3 class="numbers">
					<span class="payable-display">
						<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($payable, 2)  ?>
					</span>
				<h3>
				<p class="titles">Net Payable</p>
				<div class="eclipse_icon_bg cash_register_icon">
						<i class="fas fa-cash-register"></i>
				</div>
		</div>
	</div>

	<?php
		$totals = array(
			'payable' => $payable,
			'commission' => $commission,
			'vat' => $vat,
		);
		return $totals;
}

function display_terms($termsandconditions) {
	?>
	<div class="revenue_camapaign_details mt-5">
		<div class="row">
			<div class="revenue_campaign_info p-4 col cols">
				<h3 class="terms">Campaign Terms & Conditions</h3>
				<p class="text-secondary">(printed on each voucher)</p>
				<?php	echo stripslashes($termsandconditions) ?>
			</div>
		</div>
	</div>
	<?php
}

function display_payments_table($product_id, $payable, $commission_val, $commission, $vat_val, $vat, $admin, $venue_info, $payment_list) {
	global $wpdb;

	$total_paid_to_customer = 0;
	?>
			<div class="table-fixed-wrapper">
				<div id="payment-table-container" class="table-fixed-container">		
					<table id="audit-payment-table" class="table table-striped table-bordered table-fixed text-center"
						<?php
								// need data for invoice button
								$invoice_pdf_url = TASTE_PLUGIN_URL . "pdfs/invoice.php";
						?>
						data-commval="<?php echo $commission_val ?>" data-vatval="<?php echo $vat_val ?>"
						data-productid="<?php echo $product_id ?>" data-invoiceurl="<?php echo $invoice_pdf_url ?>"
						data-venuename="<?php echo $venue_info['name'] ?>" data-venueaddr1="<?php echo $venue_info['address1'] ?>"
						data-venueaddr2="<?php echo $venue_info['address2'] ?>" data-venuecity="<?php echo $venue_info['city'] ?>"
						data-venuepostcode="<?php echo $venue_info['postcode'] ?>" >
						<thead>
							<tr>
								<?php echo $admin ? '<th scope="col">ID</th>' : '' ?>
								<th scope="col" class="sort-by-date">Date</th>
								<th scope="col">Amount</th>
								<th scope="col">Invoice</th>
								<th scope="col">Description</th>
								<?php if ($admin) {	?>
									<th scope="col">Edit</th>
									<th scope="col">Delete</th>
									<?php
								}
								?>
							</tr>
						</thead>
						<tbody id="payment-lines">
							<?php
							$ln = 1;
							foreach($payment_list as $payment){ 
								// disp_payment_line is in ajax/functions.php
								echo disp_payment_line($payment, $admin, $commission_val);
								$total_paid_to_customer = $total_paid_to_customer + $payment['amount'];
								$ln++;
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
			<?php if ($admin) {
				?>
					<!--  ADD NEW TRANSACTION MODAL TRIGGER  -->
					<button type="button" class="btn btn-success mt-2" data-toggle="modal" data-target="#addEditPaymentModal"
						data-paymentid="" data-paymentdate="<?php echo date('Y-m-d') ?>" data-paymentamt="0" data-comment="" 
						data-commentvisibility="1"
						>
						<i class="fa fa-plus-circle"></i> &nbsp; Add Transaction
					</button>
					<?php
				}
				$balance_due = $payable - $total_paid_to_customer;
			?>
			<!-- PAYMENTS SUMMARY -->
			<div class="row">
				<div class="col-md tcard">
						<h3 class="numbers">
							<span class="payable-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($payable, 2)  ?>
							</span>
						<h3>
						<p class="titles">Net Payable</p>
						<div class="eclipse_icon_bg cash_register_icon">
								<i class="fas fa-cash-register"></i>
						</div>
				</div>
				<div class="col-md tcard">
						<h3 class="numbers">
							<span class="total-payments-display">
								<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($total_paid_to_customer, 2)  ?>
							</span>
						<h3>
						<p class="titles">Campaign Payments</p>
						<div class="eclipse_icon_bg coins_icon">
							<i class="fas fa-coins"></i>
						</div>
				</div>
				<div class="col-md tcard">
						<h3 class="numbers">
						<span id="balance-due-display">
						<?php echo get_woocommerce_currency_symbol() ?> <?php echo number_format($balance_due, 2) ?>
							</span>
						<h3>
						<p class="titles">Balance Due</p>
						<div class="eclipse_icon_bg balance_scale_icon">
								<i class="fas fa-balance-scale"></i>
						</div>
				</div>
			</div>
			<!-- END OF PAYMENTS SUMMARY -->

		<!-- Payment Modal -->
		<div class="modal fade" id="addEditPaymentModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="addEditPaymentModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="addEditPaymentModalLabel"></h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<form id="modal-payment-form">
							<input type="hidden" id="modal-payment-id" value="0" name="payment-id">
							<input type="hidden" id="modal-payment-orig-amt" name="payment-orig-amt">
							<input type="hidden" id="modal-payment-orig-date" name="payment-orig-date">
							<div class="form-group">
								<label for="modal-payment-date">Transaction date</label>
								<input class="form-control" type="date" id="modal-payment-date" required name="payment-date">
							</div>
							<div class="form-group">
								<label for="modal-payment-amt">Transaction amount</label>
								<input class="form-control" type="text" id="modal-payment-amt" required name="payment-amt">
							</div>
							<div class="form-group">
								<label for="modal-payment-comment">Description							
									<div class="form-check" id="payment-visibility-checkbox-div">
										<input class="form-check-input" type="checkbox" value="" id="payment-comment-visible-checkbox"	
												name="payment-comment-visibility">
											<label class="form-check-label" for="payment-comment-visible-checkbox">
												Visible to Venues
											</label>
									</div>
								</label>
								<textarea class="form-control" id="modal-payment-comment" name="payment-comment" placeholder="Add comment" rows="3"></textarea>
							</div>

						</form>
					</div>
					<div class="modal-footer">
						<div id="payment-modal-addedit">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
							<button type="submit" form="modal-payment-form" id="modal-payment-submit" class="btn btn-primary payment-save-btn">Save</button>
						</div>
						<div id="payment-modal-delete">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
							<button type="button" form="modal-payment-form" id="modal-payment-delete-btn" class="btn btn-danger payment-save-btn">Delete</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Comment Modal -->
		<div class="modal fade" id="addCommentModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="addCommentModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="addCommentModalLabel"><strong>Add / Edit Description</strong></h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<form id="modal-comment-form">
							<input type="hidden" id="modal-comment-id" name="payment-id">
							<input type="hidden" id="modal-comment-amt" name="payment-amt">
							<input type="hidden" id="modal-comment-date" name="payment-date">
							<input type="hidden" id="modal-comment-orig-amt" name="payment-orig-amt">
							<input type="hidden" id="modal-comment-orig-date" name="payment-orig-date">
							<div class="form-group">
								<label for="modal-comment">Description</label>
								<textarea class="form-control" id="modal-comment" name="payment-comment" placeholder="Add comment" rows="3"></textarea>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" value="" id="modal-comment-visible-checkbox" name="payment-comment-visibility">
								<label class="form-check-label" for="modal-comment-visible-checkbox" >
									Visible to Venues
								</label>
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
						<button type="submit" form="modal-comment-form" id="modal-comment-submit" class="btn btn-primary payment-save-btn">Save changes</button>
					</div>
				</div>
			</div>
		</div>
	<?php
	return $total_paid_to_customer;
}

