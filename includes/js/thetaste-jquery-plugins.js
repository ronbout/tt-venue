/**
 *
 * 	thetaste-jquery-plugins.js
 * 	some useful routines added to the jquery object
 * 	3/4/2021   Ron Boutilier
 *
 */

(function ($) {
	$.fn.initDirty = function (submitTest = true) {
		console.log("inside initdirty");
		return this.filter("form").each(function () {
			console.log("inside each");
			let origFormData = JSON.stringify(Object.fromEntries(new FormData(this)));
			const $thisForm = $(this);
			if (submitTest) {
				$("input[type='submit'], button[type='submit']").prop("disabled", true);
				// check on any change whether to enable submit button
				$thisForm.find(":input").on("keyup change", function () {
					let curFormData = JSON.stringify(
						Object.fromEntries(new FormData($thisForm[0]))
					);
					if (origFormData.localeCompare(curFormData)) {
						$("input[type='submit'], button[type='submit']").prop(
							"disabled",
							false
						);
					} else {
						$("input[type='submit'], button[type='submit']").prop(
							"disabled",
							true
						);
					}
				});
			}
		});
	};
})(jQuery);
