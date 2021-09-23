/**
 * restaurant-manager.js
 * setting up a redirect for the restaurantmanager
 * page template after a splash page has been displayed
 *
 * 9/23/2021	Ron Boutilier
 */

window.onload = function () {
	//console.log("successful js load");
	const redirectDelay = 10000;
	// cmUrl is passed down from splash-page.php
	setTimeout(() => {
		location.href = cmUrl;
	}, redirectDelay);
};
