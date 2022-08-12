<?php
/**
 * 	venue-navbar.php
 * 
 * 	contains functions related the navbar used in the header 
 *  across the various venue system page templates
 * 
 * 	1/14/2021  Ron Boutilier
 * 
 */
defined('ABSPATH') or die('Direct script access disallowed.');

function venue_navbar ($links, $profile_flag="false", $venue_name="") {
	$link_count = count($links);
	$links_html = '';
	$dropdown_link_flag = false;
	foreach($links as $link) {
		if (!isset($link['profile_dropdown']) || !$link['profile_dropdown']) {
			$links_html .= '
				<li class="nav-item ' . ($link['active'] ? 'active' : '') . '">
					<a class="nav-link" href="' . $link['url'] . '" ' . (isset($link['attrs']) ? $link['attrs']  : '') . '>' . $link['title'] . '</a>
				</li>
			';
		} else {
			$dropdown_link_flag = true;
		}
	}
	if ($profile_flag && $dropdown_link_flag) {
		// add profile dropdown menu
		$links_html .= '
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="navbar-profile-dropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					' . $venue_name . '
				</a>
				<div class="dropdown-menu" aria-labelledby="navbar-profile-dropdown">
			';
		foreach($links as $ndx => $link) { 
			if ($link_count - 1 == $ndx) {
				$links_html .= '<div class="dropdown-divider"></div>';
			}
			if (isset($link['profile_dropdown']) && $link['profile_dropdown']) {
				$links_html .= '
				<a class="dropdown-item" href="' . $link['url'] . '" ' . (isset($link['attrs']) ? $link['attrs']  : '') . '>' . $link['title'] . '</a>
				';
			}
		}
		$links_html .= '
				</div>
			</li>
		';
	}
	?>
<header>
  <nav class="navbar sticky-top navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="<?php echo get_site_url() ?>">
      <img src="<?php echo TASTE_VENUE_UPLOADS_BASE_URL ?>/2022/01/TheTaste-logo-2022.svg" class="img-fluid"
        style="width: 180px" alt="" loading="lazy">
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#venueNavbarToggler"
      aria-controls="venueNavbarToggler" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="venueNavbarToggler">
      <ul class="navbar-nav ml-auto mt-2 mt-lg-0">
        <?php echo $links_html ?>
      </ul>
    </div>
  </nav>
</header>
<?php
}

function venue_navbar_standard_links($use_new_campaign, $venue_voucher_page, $admin=false, $get_string='', $admin_select_get='') {
	global $pagename;
	
	if ($get_string) {
		$get_string = "?$get_string";
	}
	if ($admin_select_get) {
		$admin_select_get = "?$admin_select_get";
	}

	if ($use_new_campaign) {
		$voucher_page_title = 'Campaign Manager';
		$voucher_page = '/campaign-manager';
	} else {
		$voucher_page_title = 'Manage Vouchers';
		$voucher_page = $venue_voucher_page;
	}
	$links = array(
		array(
			'profile_dropdown' => false,
			'title' => 'Dashboard',
			'url' => get_site_url(null, '/venue-portal') . $get_string,
			'active' => 'venue-portal' === $pagename
		),
		array(
			'profile_dropdown' => false,
			'title' => $voucher_page_title,
			'url' => get_site_url(null, $voucher_page) . $get_string,
			'active' => $voucher_page === '/' . $pagename
		),
		array(
			'profile_dropdown' => false,
			'title' => 'Jobs',
			'url' => get_site_url(null, '/job-dashboard') . $get_string,
			'active' => 'job-dashboard' === $pagename
		),
		array(
			'profile_dropdown' => true,
			'title' => 'Account',
			'url' => get_site_url(null, '/venue-profile-page') . $get_string,
			'active' => 'my-taste-account' === $pagename
		),
		array(
			'profile_dropdown' => true,
			'title' => 'Password Reset',
			'url' => get_site_url(null, '/venue-change-password') . $get_string,
			'active' => '#' === $pagename
		),
		array(
			'profile_dropdown' => true,
			'title' => 'Log Out',
			'url' => wp_logout_url(get_site_url(null, '/venue-portal')),
			'active' => false
		),
	);

	// if admin, add Venue Selection to the beginning
	if ($admin) {
		$venue_select_link = array(
			'title' => 'Venue Selection',
			'url' => get_page_link() . $admin_select_get,
			'active' => false
		);
		array_unshift($links, $venue_select_link);
	}
	return $links;
}