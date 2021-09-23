<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php _e('Processing Orders Redirect'); ?></title>

	<?php 
		// need to pass the redirect address to javascript
		// w/o the benefit of wp_localize_script()
		$venue_url = home_url('/venue-portal');
	?>
	<script>
		cmUrl = "<?php echo $venue_url ?>"
	</script>
	<script src='<?php echo get_stylesheet_directory_uri(  ) . "/page-templates/restaurant-manager.js" ?>'></script>
	<style>
		header {
			margin: 40px auto;
		}
		header, p {
			text-align: center;
		}
	</style>
</head>
<body>
	<header>
		<h3>You are being redirected to the new and improved Campaign Manager in 30 seconds.<br>
			 <a href="<?php echo $venue_url ?>">Click Here</a> to go there now.
		</h3>
	</header>
	<main>
	<p>
		You will be required to login as your venue if not so currently.
	</p> 
	</main>
</body>
</html>