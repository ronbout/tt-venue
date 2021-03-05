/**
 *
 * 	thetaste-jquery-plugins.js
 * 	some useful routines added to the jquery object
 * 	3/4/2021   Ron Boutilier
 *
 */

(function ($) {
	$.fn.initDirty = function (submitTest = true) {
		return this.filter("form").each(function () {
			let origFormData = JSON.stringify(Object.fromEntries(new FormData(this)));
			const $thisForm = $(this);
			if (submitTest) {
				// the submit button may be outside the form and not easily selected
				let $submitBtn = $(
					"input[type='submit'], button[type='submit']",
					$thisForm
				);
				if (!$submitBtn.length) {
					const formId = $thisForm.attr("id");
					// look for a submit button that has the form attr set to the form id
					$submitBtn = $(
						`input[type="submit"][form="${formId}"], button[type="submit"][form="${formId}"]`
					);
				}
				if ($submitBtn.length) {
					$submitBtn.prop("disabled", true);
					// check on any change whether to enable submit button
					$thisForm.find(":input").on("keyup change", function () {
						let curFormData = JSON.stringify(
							Object.fromEntries(new FormData($thisForm[0]))
						);
						testFormSubmitability(origFormData, curFormData, $submitBtn);
					});

					// need to do paste separately as it does not update the
					// formData until the field has blurred.
					$thisForm.find(":input").on("paste", function (e) {
						let pastedData = (
							e.originalEvent.clipboardData || window.clipboardData
						).getData("text");
						let curFormDataObj = Object.fromEntries(new FormData($thisForm[0]));
						let inputName = $(this).attr("name");
						// treat the pasted data as though it was in the formData
						curFormDataObj[inputName] = pastedData;

						let curFormData = JSON.stringify(curFormDataObj);
						testFormSubmitability(origFormData, curFormData, $submitBtn);
					});
				}

				function testFormSubmitability(origFormData, curFormData, $submitBtn) {
					if (origFormData.localeCompare(curFormData)) {
						$submitBtn.prop("disabled", false);
					} else {
						$submitBtn.prop("disabled", true);
					}
				}
			}
		});
	};
})(jQuery);
