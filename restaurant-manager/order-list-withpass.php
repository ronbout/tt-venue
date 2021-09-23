<?php
/*
Template Name: Order List PASSWORD
*/
?>

<?php 

// need to find out if an admin level user is accessing this page. 
// otherwise wil restrict payment ability below


if ( !is_user_logged_in()) {
	$admin - false;
} else {
	$user = wp_get_current_user();
	$role = $user->roles[0];
	$admin = ('ADMINISTRATOR' === strtoupper($role));
}

if (isset($_POST['product_id'])) {

	// need to perform a check on the product to see if it belongs to a venue 
	// that should be using Campaign Manager and redirect if necessary
	if (! $admin) {
		check_venue_redirect($_POST['product_id']);
	}
}
?>

<!DOCTYPE HTML>

<html>

<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
 
    <title><?php _e('Processing Orders'); ?></title>
    <style>

        .pimage
        {
            font-size: 14pt;
        }

        .pimage img
        {
            vertical-align: text-bottom;
            margin-right: 15px;
        }
    </style>
</head>

<body>

<section>
</br>
</br>
<div class="container">

<center><img src="http://thetaste.ie/wp/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png"></center>
<br><br>
<center>
<b>WELCOME TO IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
<br><br>
<span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL BUSINESS IN IRELAND</span></center>
<br><br>

<div class="panel panel-default">
  <div class="panel-heading">
	<b>TASTE MANAGEMENT CONSOLE</b>
	<br><br>
	Enter ID and Restaurant Password</div>
  <div class="panel-body">
 
<form class="form-inline" method="post" action="">
  <div class="form-group">
          <input type="text" name="product_id" class="form-control" id="product_id" placeholder="ID" required="">
  </div>
  <div class="form-group">
          <input type="password" name="product_pass" class="form-control" id="product_pass" placeholder="PASSWORD" required="">
  </div>
  <button type="submit" class="btn btn-default">Submit</button>
</form>
  </div>
</div>
</div>

<div class="container">

    <?php
    global $wpdb;

    $tproduct = 0;
    $redeem = 0;



    if (isset($_POST['product_id'])) {

			// need to perform a check on the product to see if it belongs to a venue 
			// that should be using Campaign Manager and redirect if necessary
			if (! $admin && false) {
				check_venue_redirect($_POST['product_id']);
			}

    $id = 1;
    $pid = $_POST['product_id'];
    $pass = $_POST['product_pass'];
    $total = 0;
    $myrows1 = $wpdb->get_results($wpdb->prepare("SELECT p.post_title, p.id
FROM " . $wpdb->prefix . "posts p
JOIN " . $wpdb->prefix . "postmeta pw ON p.id = pw.post_id
WHERE p.post_type = 'product'
 AND p.post_status = 'publish'
AND pw.meta_key = 'RestaurantPassword'
AND pw.meta_value = '%s'
AND p.id = %d", $pass, $pid));


    if (count($myrows1) > 0)
    {
        if(isset($_POST['redeem']))
        {

            $oii = $_POST['oii'];
            $prid = $_POST['prid'];
            $rows_affected = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE " . $wpdb->prefix . "woocommerce_order_items
                SET downloaded = '1' where order_item_id = %d ",$oii
                ) // $wpdb->prepare
            ); // $wpdb->query

           /* $wpdb->prepare(
                "UPDATE " . $wpdb->prefix . "woocommerce_order_items
                SET downloaded = '1' where order_item_id = %d ",$oii
            );*/

						if ($rows_affected) {
							update_redeem_audit_table($oii, $prid);
						}

        } 
        
        
        if(isset($_POST['makeapayment']))
        {
            $amounttopay = $_POST['MAP_Amount'];
            $map_product_id = $_POST['product_id'];
            $KMSQL = 'INSERT INTO ' . $wpdb->prefix . 'offer_payments (id, timestamp, pid, amount) VALUES (NULL, CURRENT_TIMESTAMP, '.$map_product_id.', '.  $amounttopay . ')';
            $rows_affected = $wpdb->query($wpdb->prepare($KMSQL));
            $wpdb->prepare($KMSQL);
        } 
        
        


    $myrows = $wpdb->get_results($wpdb->prepare("SELECT p.post_title,
                                                  im.meta_value AS quan,
                                                  im1.meta_value AS productID,
                                                  bf.meta_value AS b_fname,
                                                  bl.meta_value AS b_lname,
                                                  be.meta_value AS b_email,
                                                   i.order_id, i.order_item_id as itemid, i.downloaded as downloaded,i.paid as paid
													FROM " . $wpdb->prefix . "woocommerce_order_itemmeta im
													JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta im1 ON im.order_item_id = im1.order_item_id
													LEFT JOIN " . $wpdb->prefix . "woocommerce_order_items i ON im.order_item_id = i.order_item_id
													LEFT JOIN " . $wpdb->prefix . "posts o ON i.order_id = o.id
													JOIN " . $wpdb->prefix . "posts p ON im1.meta_value = p.id
													LEFT JOIN " . $wpdb->prefix . "postmeta bf ON o.id = bf.post_id
													LEFT JOIN " . $wpdb->prefix . "postmeta bl ON o.id = bl.post_id
													LEFT JOIN " . $wpdb->prefix . "postmeta be ON o.id = be.post_id
													WHERE im.meta_key = '_qty'
													AND im1.meta_key = '_product_id'
													AND bf.meta_key = '_billing_first_name'
													AND bf.post_id = o.id
													AND bl.meta_key = '_billing_last_name'
													AND bl.post_id = o.id
													AND be.meta_key = '_billing_email'
													AND be.post_id = o.id
											        AND o.post_status = 'wc-completed'
													AND o.post_type = 'shop_order'
													AND im1.meta_value = %d group by o.id", $pid));




    $gr = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value
FROM " . $wpdb->prefix . "postmeta pm
WHERE pm.post_id = %d
AND pm.meta_key = '_price'", $pid));


    $vat_val = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value
FROM " . $wpdb->prefix . "postmeta pm
WHERE pm.post_id = %d
AND pm.meta_key = 'Vat'", $pid));

        $commission_val = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value
FROM " . $wpdb->prefix . "postmeta pm
WHERE pm.post_id = %d
AND pm.meta_key = 'Commission'", $pid));


        $expired_val = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value
FROM " . $wpdb->prefix . "postmeta pm
WHERE pm.post_id = %d
AND pm.meta_key = 'Expired'", $pid));

        $tandc_val = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value
FROM " . $wpdb->prefix . "postmeta pm
WHERE pm.post_id = %d
AND pm.meta_key = '_purchase_note'", $pid));



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
    
    
    if(strpos(json_encode($expired_val), 'N') !== false){
        $expired_val = 'N';
    } else{
        $expired_val = 'Y';
    }


    ?>


        <div class="row">
        <div class="col-md-12">
        <p class="pimage">
	<b>Revenue Campaign : <u><?= $pid ?></u> : </b><?= $myrows[0]->post_title ?></p>
	
	<b>Campaign Status : </b>
	<?php
	if($expired_val == $activecampaign) {
	    echo 'Active';
	}
	Else {
	    echo 'Expired';
	}
	?>

	<hr>
	<br>

	<b>Please Note : </b> This management console has 3 unique rules, the first is all payments due to venues are for served customers only, by law TheTaste must be able to complete refunds direct to customers who have not been served. The second change you will notice is as a result of the recent GDPR laws meaning we can only disclose the email addresses of the customers you have served. The final change is due to National Consumer Law meaning we have to allow 14 days after the campaign validity has expired to issue payments.

	<br><br>

	<b>Important : </b> By clicking the Redeem button below you are confirming you have fully served that customer and the customer will receive an automatic email thanking them and asking them to share their experience feedback with us. Fraudulently Redeeming Vouchers will expose details of customers below and break GDPR Laws.
	
	<br><br>
	
	<b style="color:red;">You must retain all paper vouchers for this campaign!</b>
	
	<br><br>
		
	<b style="color:red;">Fraudulently Redeeming Vouchers will result in a full paper audit of this campaign and Put Your Payment On Hold!</b>

	<br><br>
	<hr>
	
	<b>Campaign VAT Statement</b><br>
	JFG Digital Ltd T/A TheTaste.ie<br>
	Dogpatch Labs, Unit 1 The CHG Building, Dublin<br>
	Company No 681166<br>
  VAT No 3738046TH<br>
    <br>

    </div>
        </div>


        <div class="panel panel-default">
            <div class="panel-heading"><h2 style="text-align: center">CAMPAIGN SUMMARY</h2></div>
            <div class="panel-body">

                <table class="table table-striped table-bordered">

                    <thead>

                    <th>Order ID</th>

                    <th>Customer Name</th>

                    <th>Customer Email</th>

                    <th>Quantity</th>

                    <th>Redeem</th>
                 

                    </thead>

                    <tbody>

                    <?php foreach ($myrows as $val) {
                        $tproduct = $tproduct + 1;


                        ?>

                        <tr>

                            <td><?= $val->order_id ?></td>

                            <td><?= $val->b_fname . ' ' . $val->b_lname ?></td>

                            <?php
                                	if ($val->downloaded == '1') {
                                ?>
                            		<td><?= $val->b_email ?></td>

 				<?php
                                } else {
				?>
					<td>*** GDPR BLANKED EMAIL ***</td>
				<?php
				}
                                ?>

                            <td><?= $val->quan ?> </td>
                            <td>
                                <?php
                                if($val->downloaded == '0') {
                                    if($expired_val == 'N') {
                                    ?>
                                    <form method="post" action="">
                                        <input type="hidden" name="prid" value="<?= $val->order_id ?>">
                                        <input type="hidden" name="oii" value="<?= $val->itemid ?>">
                                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                                        <input type="hidden" name="product_pass" value="<?= $pass ?>">
                                        <input type="submit" value="Redeem" name="redeem">

                                    </form>
                                <?php
                                    }
                                    else {
                                        echo 'Not Served / Expired';
                                    }
                                }
                                else
                                {
                                    $redeem = $redeem + $val->quan;
                                    echo '<b>Served</b>';
                                }
                                    ?>

                            </td> 



                        </tr>

                        <?php

    			$total = $total + $val->quan;
                        $grevenue = $redeem * $gr[0]->meta_value;
                        $commission = ($grevenue / 100) * $commission_val[0]->meta_value;
                        $vat = ($commission / 100) * $vat_val[0]->meta_value;
                        $grevenue = round($grevenue, 2);
                        $commission = round($commission, 2);
                        $vat = round($vat, 2);
                        $payable = $grevenue - ($commission + $vat);
                        $payable = round($payable, 2);

                    }

                    ?>



                    <tr>

                        <td></td>

                        <td></td>

                        <td></td>

                        <td></td>

                    </tr>
                    <tr>

                        <td></td>

                        <td></td>
                        <td></td>

                        <td style="text-align: right; padding-right: 120px;"><b>Gross Revenue</b></td>

                        <td><b><?= get_woocommerce_currency_symbol() ?> <?= number_format($grevenue, 2)  ?></b> </td>

                    </tr>
                    <tr>

                        <td></td>

                        <td></td>
                        <td></td>

                        <td style="text-align: right; padding-right: 120px;">Commission</td>

                        <td><?= get_woocommerce_currency_symbol() ?> <?= number_format($commission, 2) ?> </td>

                    </tr>
                    <tr>

                        <td></td>

                        <td></td>
                        <td></td>

                        <td style="text-align: right; padding-right: 120px;">Vat</td>

                        <td><?= get_woocommerce_currency_symbol() ?> <?= number_format($vat, 2) ?> </td>

                    </tr>
                    <tr>

                        <td></td>

                        <td></td>
                        <td></td>

                        <td style="text-align: right; padding-right: 120px;"><b>Net Payable </b></td>

                        <td><b><?= get_woocommerce_currency_symbol() ?> <?= number_format($payable, 2) ?></b> </td>

                    </tr>
                    <tr>

                        <td></td>

                        <td></td>
                        <td></td>

                        <td style="text-align: right; padding-right: 120px;"><b>Redeemed</b></td>

                        <td>Served <?= $redeem ?> customers <br> out of a possible <?= $total ?></td>

                    </tr>

                    </tbody>

                </table>

            </div>
        </div>

    <p class="pimage">
	<b>Campaign Terms & Conditions</b> (printed on each voucher)</p>

    <?php
    echo $termsandconditions;
   
	$paymentList = $wpdb->get_results($wpdb->prepare("SELECT  * from " . $wpdb->prefix . "offer_payments where pid = %d", $pid));

	$total_paid_to_customer = 0;

    ?>
<br><br>
<div class="panel panel-default">			
			<div class="panel-body">
			<div class="panel-heading"><h2 style="text-align: center">Payment Transactions </h2></div>			
			<table class="table table-striped table-bordered">
				<thead>
				<tr>
                    <th>Payment Date</th>
                    <th>Payment Amount</th>
				</tr>
                </thead>
				<tbody>
					<?php
					
					foreach($paymentList as $val){ ?>
					<tr>
					<td><?= $val->timestamp ?></td>
					<td><?= get_woocommerce_currency_symbol() ?> <?= number_format($val->amount, 2) ?></td>
					<?php $total_paid_to_customer = $total_paid_to_customer + $val->amount ?>
					<?php } ?>
					</tr>
				</tbody>
				</table>

            </div>
		<br>
		<center><b>Balance Due : € <?= number_format($payable - $total_paid_to_customer, 2) ?></b></center>
		<br>
		<hr>
	<?php if ($admin && false) { ?>
		<center>
		<div style="width:200px;">
		<b>For Office Use Only:</b><br><br>
		<form method="post" action="">
            <b>€</b> <input type="text" name="MAP_Amount" value="0.00" style="width='100px';">
            <input type="hidden" name="product_id" value="<?= $pid ?>">
            <input type="hidden" name="product_pass" value="<?= $pass ?>"><br><br>
            <input type="submit" value="Make a Payment" name="makeapayment">
        </form>
		</div>
		</center>
	<?php } ?>
		
		
		
		<br><br>
        </div>

<br><br>
	<b>Important : </b> By clicking the Redeem button below you are confirming you have fully served that customer and the customer will receive an automatic email thanking them and asking them to share their experience feedback with us. Fraudulently Redeeming Vouchers will expose details of customers below and break GDPR Laws.
<br><br>

By using our Management Console, you have agreed to our Terms & Conditions : <a href="httphttp://thetaste.ie/wp/terms-use/">Terms of Use</a> | <a href="http://thetaste.ie/wp/our-refund-policy/">Refund Policy</a>
<br><br>



        <?php


        }


        else {

          echo  '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">TheTaste Error -</span> Values entered do not match in our system
</div>';
        }

        }





        ?>

</div>
</section>

</body>

</html>

<?php 
function update_redeem_audit_table($order_item_id, $product_id) {
	global $wpdb;

	$redemption_audit_table = $wpdb->prefix ."taste_venue_order_redemption_audit";

	if ( !is_user_logged_in()) {
		$user_id = 0;
	} else {
		$user_id = get_current_user_id();
	}

	
	$sql = "INSERT into $redemption_audit_table
						(order_item_id, user_id, redemption_value)
					VALUES (%d, %d, %d)";

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $order_item_id, $user_id, 1)
	);

	return $rows_affected;

}

function check_venue_redirect($product_id) {
	global $wpdb; 

	$sql = "
		SELECT v.use_new_campaign
			FROM wp_posts p
			LEFT JOIN wp_taste_venue_products vp ON vp.product_id = p.ID
			LEFT JOIN wp_taste_venue v ON v.venue_id = vp.venue_id
			WHERE p.ID = %d";

	$venue_info = $wpdb->get_results(
		$wpdb->prepare($sql, $product_id), ARRAY_A
	);

	if ($venue_info[0]['use_new_campaign']) {
		require(get_stylesheet_directory(  ) . "/page-templates/splash-page.php");
		exit;
	}
}
	