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
	setLoginSubmitability($userName.val(), $userPass.val(), $submitBtn);
	$userName.keyup(function () {
		setLoginSubmitability($userName.val(), $userPass.val(), $submitBtn);
	});
	$userName.on("paste", function (e) {
		let name = (e.originalEvent.clipboardData || window.clipboardData).getData(
			"text"
		);
		setLoginSubmitability(name, $userPass.val(), $submitBtn);
	});
	$userPass.keyup(function () {
		setLoginSubmitability($userName.val(), $userPass.val(), $submitBtn);
	});
	$userPass.on("paste", function (e) {
		let pass = (e.originalEvent.clipboardData || window.clipboardData).getData(
			"text"
		);
		setLoginSubmitability($userName.val(), pass, $submitBtn);
	});
});

const setLoginSubmitability = ($userName, $userPass, $submitBtn) => {
	// check that neither login field is empty
	// otherwise, disable the submit button
	if (!$userName || !$userPass) {
		$submitBtn.prop("disabled", true);
	} else {
		$submitBtn.prop("disabled", false);
	}
};
