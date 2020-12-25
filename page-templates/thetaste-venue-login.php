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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
		<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/style/css/thetaste-venue-login.css">
		<title><?php _e('Campaign Login'); ?></title>
	</head>
	<body>
    <nav class="navbar sticky-top navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">
            <img src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png" class="img-fluid" style="width: 220px"  alt="" loading="lazy">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
            <ul class="navbar-nav ml-auto mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="#">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Link 2</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" >Link 3</a>
                </li>
            </ul>
        </div>
    </nav>
		<div class="main_wrapper">
			<div id="left">
				<div id="thetaste-logo-link" class="text-center animate__animated animate__bounceInDown">
<!--					<a href="--><?php //echo get_site_url() ?><!--">-->
<!--						<img class="img-fluid" src="--><?php //echo get_site_url() ?><!--/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png">-->
<!--					</a>-->
					<h1 class="heading">WELCOME TO IRELAND’S AWARD WINNING</h1>
					<h2 class="heading2">FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</h2>
				</div>
			</div>
			<div id="right">
				<div class="bg-white shadow-lg col-sm-8 login_div text-center">
				<i class="fas fa-user"></i>
					<?php
						login_venue_form();
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

<?php 
	
function login_venue_form() {
	$redirect = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	?>
		<form name="loginform" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ) ?>" method="post">
				<div class="form-group row">
						<label class="user_login" for="user_login">Username or Email:</label>
						<input type="text" name="log" id="user_login" class="input form-control shadow-lg" value="" size="20" autofocus placeholder="Enter username or email" required/>
				</div>
				<div class="form-group row">
						<label class="user_pass" for="user_pass">Password:</label>
						<input type="password" name="pwd" id="user_pass" class="input form-control shadow-lg" value="" size="20" placeholder="Enter your password" required/>
				</div>
				<div class="form-group row login-submit d-flex justify-content-center">
						<input type="submit" name="wp-submit" id="wp-submit" class="btn button-primary py-2 px-5" value="Log In" />
						<input type="hidden" name="redirect_to" value="" />
				</div>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ) ?>" />
		</form>
	<?php
}