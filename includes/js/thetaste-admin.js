jQuery(document).ready(function () {
	userForms();
});

const userForms = () => {
	if (
		jQuery("body").hasClass("user-new-php") ||
		jQuery("body").hasClass("user-edit-php")
	) {
		let $roleSelect = jQuery("#role");
		$roleSelect.length &&
			$roleSelect.change(function () {
				let selectRole = this.value;
				if ("venue" === selectRole) {
					jQuery("#user-venue-fields").css("display", "block");
				} else {
					jQuery("#user-venue-fields").css("display", "none");
				}
			});
	}
};
