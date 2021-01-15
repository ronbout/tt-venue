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

function venue_navbar ($links) {
	$links_html = '';
	foreach($links as $link) {
		$links_html .= '
			<li class="nav-item ' . ($link['active'] ? 'active' : '') . '">
				<a class="nav-link" href="' . $link['url'] . '" ' . (isset($link['attrs']) ? $link['attrs']  : '') . '>' . $link['title'] . '</a>
			</li>
		';
	}
	?>
	<header>
		<nav class="navbar sticky-top navbar-expand-lg navbar-light bg-light">
				<a class="navbar-brand" href="<?php echo get_site_url() ?>">
						<img src="<?php echo get_site_url() ?>/wp-content/uploads/2017/12/thetaste-site-homepage-logo5.png" class="img-fluid" style="width: 220px"  alt="" loading="lazy">
				</a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#venueNavbarToggler" aria-controls="venueNavbarToggler" aria-expanded="false" aria-label="Toggle navigation">
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

function venue_navbar_standard_links($use_new_campaign, $venue_voucher_page) {
	global $pagename;

	if ($use_new_campaign) {
		$voucher_page_title = 'Campaign Manager';
		$voucher_page = '/campaign-manager';
	} else {
		$voucher_page_title = 'Manage Vouchers';
		$voucher_page = $venue_voucher_page;
	}
	$links = array(
		array(
			'title' => 'Dashboard',
			'url' => get_site_url(null, '/venue-portal'),
			'active' => 'venue-portal' === $pagename
		),
		array(
			'title' => $voucher_page_title,
			'url' => get_site_url(null, $voucher_page),
			'active' => $voucher_page === '/' . $pagename
		),
		array(
			'title' => 'Profile',
			'url' => get_site_url(null, '/venue-profile-page'),
			'active' => 'venue-profile-page' === $pagename
		),
		array(
			'title' => '<i class="fas fa-sign-out-alt"></i>',
			'url' => wp_logout_url(get_site_url(null, '/venue-portal')),
			'active' => false,
			'attrs' => ' data-toggle="tooltip" data-placement="left" title="Logout" id="logout" '
		),
	);
	return $links;
}