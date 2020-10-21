jQuery(document).ready(function () {
	tasteLoadFormSubmit();
	jQuery("#topbutton").length && tasteLoadScrollUp();
});

const tasteLoadFormSubmit = () => {
	jQuery("#load-products")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			let formData = new FormData(jQuery("#year-form")[0]);
			console.log(formData.get("year_select"));
			console.log(formData.get("year_type"));
			tasteLoadProducts(formData);
		});
};

const tasteLoadProducts = (formData) => {
	let modalMsg = "Loading Products...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "html",
		data: {
			action: "outstanding_load_products",
			security: tasteVenue.security,
			year_select: formData.get("year_select"),
			year_type: formData.get("year_type"),
		},
		success: function (responseText) {
			tasteCloseMsg();
			// console.log(responseText);
			jQuery("#product-list-div").html(responseText);
			tasteLoadProductButtons();
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error loading products: " + errorThrown);
		},
	});
};

const tasteLoadProductButtons = () => {
	jQuery(".product-select-btn")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			let prodId = jQuery(this).data("prod-id");
			let $curProdInput = jQuery("#taste-product-id");
			if ($curProdInput.length && $curProdInput.val() === prodId.toString()) {
				// prod is already loaded, just scroll to the section
				tasteScrollToVouchers();
			} else {
				let $rowData = jQuery(this).parent().parent();
				let multiplier = $rowData.data("multiplier");
				tasteLoadVouchers(prodId, multiplier);
			}
		});
};

const tasteLoadVouchers = (prodId, multiplier) => {
	let modalMsg = "Loading Vouchers...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "html",
		data: {
			action: "outstanding_load_vouchers",
			security: tasteVenue.security,
			product_id: prodId,
			multiplier: multiplier,
		},
		success: function (responseText) {
			tasteCloseMsg();
			//console.log(responseText);
			jQuery("#voucher-list-div").html(responseText);
			tasteScrollToVouchers();
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error loading vouchers: " + errorThrown);
		},
	});
};

const tasteLoadScrollUp = () => {
	var offset = $("#voucher-list-div").offset().top;
	var duration = 500;
	$(window).scroll(function () {
		if ($(this).scrollTop() < offset) {
			$("#topbutton").fadeOut(duration);
		} else {
			$("#topbutton").fadeIn(duration);
		}
	});
	jQuery("#topbutton")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			$("html, body").animate(
				{
					scrollTop: $("#venue-summary-div").offset().top,
				},
				600
			);
		});
};

const tasteScrollToVouchers = () => {
	$("html, body").animate(
		{
			scrollTop: $("#voucher-list-div").offset().top,
		},
		600
	);
};

/***********************************************************
 * modal message functions
 ***********************************************************/
/**
 * Display modal popup for both msgs and ajax loading
 * @param {string} msg  The message to display
 * @param {boolean} closeBtn  whether to display the Close button
 * @returns {void}
 */
function tasteDispMsg(msg, closeBtn) {
	// create own modal popup window
	// closeBtn is true if normal msg box..
	// false is for ajax temp disp

	if (closeBtn) {
		jQuery("#taste-msg-close").show();
	} else {
		jQuery("#taste-msg-close").hide();
	}

	jQuery("#taste-msg-text").html(msg);
	jQuery("#taste-msg-box").show();
	jQuery("#taste-modal-layer").show();
}
/**
 * Closes the Modal msg box
 * @returns {void}
 */
function tasteCloseMsg() {
	jQuery("#taste-modal-layer").hide();
	jQuery("#taste-msg-box").hide();
}
