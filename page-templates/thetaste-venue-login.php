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
require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-head.php';
require_once TASTE_PLUGIN_PATH.'page-templates/partials/venue-navbar.php';


$url_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
$request_base = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $url_path;
$query_var_str = check_query();
$login_failed = strpos($query_var_str, "?login=failed") !== false ? true : false;
$query_var_str = str_replace('?login=failed', '', $query_var_str);
parse_str($query_var_str, $query_var_array);
if (isset($query_var_array['login']) && substr($query_var_array['login'], 0, 6) == 'failed') {
    unset($query_var_array['login']);
}
$redirect = add_query_arg( $query_var_array, $request_base );

?>

<body>
  <?php venue_navbar([]);  ?>
  <div class="container-fluid h-100">
    <div class="row h-100">
      <div class="col-xl-4" id="left">
        <div id="thetaste-logo-link" class="text-center">
          <h1 class="heading">WELCOME TO IRELAND’S AWARD WINNING</h1>
          <h2 class="heading2">FOOD, DRINK & TRAVEL DIGITAL MAGAZINE</h2>
        </div>
      </div>
      <div class="col-xl-8" id="right">
        <div class="col-md-8 col-xl-9 bg-white shadow-lg mt-3 mb-3 mt-sm-3 login_div text-center">
          <i class="fas fa-user"></i>
          <?php
                        login_venue_form($redirect);
                        if ($login_failed) {
                            ?>
          <div class="login-error">
            <h3>Invalid Login. Please try again.</h3>
          </div>
          <?php
                        }
                        ?>
        </div>
      </div>
    </div>
  </div>
</body>

</html>

<?php 
	
function login_venue_form($redirect) {
	?>
<form name="loginform" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ) ?>"
  method="post">
  <div class="form-group row">
    <label class="user_login" for="user_login">Username or Email:</label>
    <input type="text" name="log" id="user_login" class="input form-control shadow-lg" value="" size="20" autofocus
      placeholder="Enter username or email" required />
  </div>
  <div class="form-group row">
    <label class="user_pass" for="user_pass">Password:</label>
    <input type="password" name="pwd" id="user_pass" class="input form-control shadow-lg" value="" size="20"
      placeholder="Enter your password" required />
  </div>
  <div class="form-group row login-submit d-flex justify-content-center">
    <input type="submit" name="wp-submit" id="wp-submit" class="btn button-primary py-2 px-5" value="Log In" />
  </div>
  <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ) ?>" />
</form>
<?php
}