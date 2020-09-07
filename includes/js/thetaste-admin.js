jQuery(document).ready(function () {
	userForms();
});

const userForms = () => {
	if (jQuery("body").hasClass("user-new-php")) {
		let $roleSelect = jQuery("#role");
		$roleSelect.length &&
			$roleSelect.change(function () {
				let selectRole = this.value;
				if ("venue" === selectRole) {
					jQuery("#new-user-venue-fields").css("display", "block");
				} else {
					jQuery("#new-user-venue-fields").css("display", "none");
				}
			});
	}
};
