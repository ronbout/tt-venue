/**
 *
 * 	thetaste-venue-login.js
 *
 * 	Code for the venue login function, which
 * 	possibly be run from multiple standalone
 *  programs, so making a separate js file
 *
 * 	9/22/2020	Ron Boutilier
 *
 */

jQuery(document).ready(function () {
	// code for the login page, such as validation
	let $userName = jQuery("#user_login");
	let $userPass = jQuery("#user_pass");
	let $submitBtn = jQuery("#wp-submit");
	$submitBtn.addClass("btn btn-primary");
	$submitBtn.removeClass("button button-primary");
	setLoginSubmitability($userName, $userPass, $submitBtn);
	$userName.keyup(function () {
		setLoginSubmitability($userName, $userPass, $submitBtn);
	});
	$userPass.keyup(function () {
		setLoginSubmitability($userName, $userPass, $submitBtn);
	});
});

const setLoginSubmitability = ($userName, $userPass, $submitBtn) => {
	// check that neither login field is empty
	// otherwise, disable the submit button
	if (!$userName.val() || !$userPass.val()) {
		$submitBtn.prop("disabled", true);
	} else {
		$submitBtn.prop("disabled", false);
	}
};
