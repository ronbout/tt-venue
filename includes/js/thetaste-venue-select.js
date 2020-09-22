/**
 *
 * 	thetaste-venue-select.js
 *
 * 	Code for the venue select function, which runs
 * 	on both venue portal code (non-wp enqueue) and
 *	admin sides.  So, making a standalone script
 *
 * 	9/22/2020	Ron Boutilier
 *
 */

jQuery(document).ready(function () {
	if (jQuery("#venue-form-container").length) {
		let $selectVenueBtn = jQuery("#select-venue-btn");
		let $venueSelect = jQuery("#venue-select");
		tasteLoadVenueFormEvents($selectVenueBtn, $venueSelect);
		// console.log(venuesList);
		// console.log("firstOption: ", firstOption);
	}
});

const tasteLoadVenueFormEvents = ($selectVenueBtn, $venueSelect) => {
	setSubmitability($selectVenueBtn, $venueSelect);
	$venueSelect.length &&
		$venueSelect.change(() => {
			setSubmitability($selectVenueBtn, $venueSelect);
		});

	jQuery("#venue-type-select").change(function () {
		let testType = this.value;
		let options = buildVenueOptions(testType);
		$venueSelect.html(options);
	});
};

const setSubmitability = ($selectVenueBtn, $venueSelect) => {
	let selectVal = parseInt($venueSelect.val());
	if (selectVal) {
		$selectVenueBtn.prop("disabled", false);
	} else {
		$selectVenueBtn.prop("disabled", true);
	}
};

const buildVenueOptions = (testType) => {
	// venuesList & firstOption come down from php script
	let options = venuesList.reduce((optList, venue, ndx) => {
		if ("all" !== testType && venue.venue_type !== testType) {
			return optList;
		}
		optList =
			optList + `<option value="${venue.venue_id}">${venue.name}</option>`;
		return optList;
	}, `<option value=0 selected>${firstOption}</option>`);

	return options;
};
