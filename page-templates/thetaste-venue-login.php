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
		<meta name="viewport" content="width=device-width" />
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
		<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_URL ?>assets/css/font-awesome.min.css">
		<link rel="preconnect" href="https://fonts.gstatic.com">
		<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet">
		<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
		<script	script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
		<script type="text/javascript" src= "<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/js/thetaste-venue-login.js"></script>
		<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/css/thetaste-venue-login.css">
		<title><?php _e('Campaign Login'); ?></title>
	</head>
	<body>
		<div class="main_wrapper">
			<div id="left">
				<div id="thetaste-logo-link" class="text-center">
					<a href="<?php echo get_site_url() ?>">
						<img class="img-fluid" src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">
					</a>
					<h1 class="heading">WELCOME TO IRELAND’S AWARD WINNING</h1>
					<h2 class="heading2">FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</h2>
				</div>
			</div>
			<div id="right">
				<div class="bg-white shadow-lg col-sm-8 login_div text-center">
				<i class="fas fa-user"></i>
					<?php 
						$login_args = array(
							"label_username" => "Username or Email: ",
							"label_password" => "Password: ",
							"remember" => false,
						);
							wp_login_form($login_args); 
					?>
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
			</div>
		</div>
	</body>
</html>