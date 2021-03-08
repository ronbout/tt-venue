<?php 
/**
 * 	the <head> section for all the venue pages as it was
 * 	decided not to use the standard thetaste header and menus
 * 
 */
defined('ABSPATH') or die('Direct script access disallowed.');
?>
<!DOCTYPE HTML>

<html>

<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />

<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_URL ?>assets/css/font-awesome.min.css">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script
  src="https://code.jquery.com/jquery-3.5.1.min.js"
  integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
  crossorigin="anonymous"></script>
<script	script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://kit.fontawesome.com/90e4bc8c6b.js" crossorigin="anonymous"></script>

<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/style/css/thetaste-venue.css">
<link rel="stylesheet" href="<?php echo TASTE_PLUGIN_INCLUDES_URL ?>/style/css/thetaste-venue-login.css">
<script src="<?php echo TASTE_PLUGIN_INCLUDES_URL?>/js/thetaste-jquery-plugins.js"></script>
<script src="<?php echo TASTE_PLUGIN_INCLUDES_URL?>/js/thetaste-tablesort.js"></script>
<title><?php _e(the_title()); ?></title>
</head>