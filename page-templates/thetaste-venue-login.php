<?php
/**
 * 
 * thetaste-venue-login.php
 * login template for venues that
 * can be shared across pages as 
 * the venue portal system grows
 * 
 * Ron Boutilier
 * 9/18/2020
 * 
 */
?>
<!DOCTYPE HTML>

<html>

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
					
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
		<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_URL ?>assets/css/font-awesome.min.css">
		<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
		<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-venue-login.js"></script>
		<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/css/thetaste-venue-login.css">
		<title><?php _e('Campaign Login'); ?></title>
	</head>
	<body>		
		<div class="container text-center">
			<div id="thetaste-logo-link" class="text-center">
				<a href="<?php echo get_site_url() ?>">
					<img src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">
				</a>
			</div>
			<div id="thetaste-venue-heading" class="text-center">
				<b>WELCOME TO IRELAND’S AWARD WINNING FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</b>
				<br><br>
				<span style="font-size:12px;">19.6M READERS WORLDWIDE <b>|</b> 10K ARTICLES <b>|</b> €10M GENERATED FOR THE IRISH HOSPITALITY INDUSTRY <b>|</b> 726K REGISTERED MEMBERS <b>|</b> 200K+ TASTE EXPERIENCES SOLD <b>|</b> 300K SOCIAL MEDIA FOLLOWERS <b>|</b> WINNER OF BEST DIGITAL FOOD MAGAZINE IN THE WORLD <b>|</b> WINNER OF OUTSTANDING SMALL BUSINESS IN IRELAND</span>
			</div>
			<h2>Campaign Login</h2>
			<div id="venue-login-form">
				<?php 
					$login_args = array(
						"label_username" => "Username or Email: ",
						"label_password" => "Password: ",
						"remember" => false,
					);
					wp_login_form($login_args); 
				?>
			</div>
			<?php
				if (get_query_var('login') === 'failed') {
					?>
					<div class="login-error">
						<h3>Invalid Login.  Please try again.</h3>
					</div>
					<?php
				}
			?>
		</div>
	</body>
</html>