jQuery(document).ready(function () {
	userForms();
	tasteLoadVenueFormEvents();
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

const tasteLoadVenueFormEvents = () => {
	if (jQuery("body").hasClass("product_page_venue-assign-products")) {
		let $venueSelect = jQuery("#venue-select");
		$venueSelect.length &&
			$venueSelect.change(function () {
				let $selectVenueBtn = jQuery("#select-venue-btn");
				let selectVal = parseInt(this.value);
				console.log(selectVal);
				console.log($selectVenueBtn.prop("disabled"));
				if (selectVal) {
					$selectVenueBtn.prop("disabled", false);
				} else {
					$selectVenueBtn.prop("disabled", true);
				}
			});
	}
};
