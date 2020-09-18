jQuery(document).ready(function () {
	tasteLoadButtons();
	tasteLoadVenueFormEvents();
	jQuery("#topbutton").length && tasteLoadScrollUp();
});

const tasteLoadVouchers = (prodId) => {
	let modalMsg = "Loading Vouchers...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "html",
		data: {
			action: "load_vouchers",
			security: tasteVenue.security,
			product_id: prodId,
		},
		success: function (responseText) {
			tasteCloseMsg();
			//console.log(responseText);
			jQuery("#voucher-list-div").html(responseText);
			tasteLoadRedeemButtons();
			// make sure that we scroll down to the section
			$("html, body").animate(
				{
					scrollTop: $("#voucher-list-div").offset().top,
				},
				600
			);
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error loading vouchers: " + errorThrown);
		},
	});
};

const tasteRedeemVoucher = (orderList) => {
	let modalMsg = "Redeeming Voucher(s)...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	// get info from hidden inputs to pass up for re-calc
	let productInfo = tasteGetProductInfo();
	let productId = productInfo.product_id;
	let venueInfo = tasteGetVenueInfo();
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "JSON",
		data: {
			action: "redeem_voucher",
			security: tasteVenue.security,
			order_list: orderList,
			product_info: productInfo,
			venue_info: venueInfo,
		},
		success: function (responseText) {
			tasteCloseMsg();
			let respObj = JSON.parse(responseText);
			if (respObj.error) {
				alert("error in redeem Voucher ajax code");
			} else {
				console.log(respObj);
				orderList.map((orderInfo) => {
					jQuery("#td-btn-order-id-" + orderInfo.orderId).html("<b>Served</b>");
					jQuery("#td-check-order-id-" + orderInfo.orderId).html("");
				});

				respObj.emails.map((emailInfo) => {
					jQuery("#email-display-" + emailInfo.orderId).html(emailInfo.email);
				});

				updateOfferCalcs(respObj, productId);
				updateVenueCalcs(respObj);
			}
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error redeeming voucher: " + errorThrown);
		},
	});
};

const tasteMakePayment = (mapAmount) => {
	let modalMsg = "Making Payment...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	// get info from hidden inputs to pass up for re-calc
	let productInfo = tasteGetProductInfo();
	let productId = productInfo.product_id;
	let venueInfo = tasteGetVenueInfo();
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "JSON",
		data: {
			action: "make_payment",
			security: tasteVenue.security,
			map_amount: mapAmount,
			product_info: productInfo,
			venue_info: venueInfo,
		},
		success: function (responseText) {
			tasteCloseMsg();
			let respObj = JSON.parse(responseText);
			if (respObj.error) {
				alert("error in Make Payment ajax code");
			} else {
				console.log(respObj);

				updateVenueCalcs(respObj);
				jQuery("#map-amount").val("0.00");
				jQuery("#balance-due-display").html(respObj.balanceDue);
				jQuery("#balance-due-display-" + productId).html(
					respObj.balanceDue.split(" ")[1]
				);
				jQuery("#payment-lines").append(respObj.paymentLine);
				jQuery("#hidden-values").html(respObj.hiddenValues);
			}
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error making payment: " + errorThrown);
		},
	});
};

const updateOfferCalcs = (respObj, productId) => {
	jQuery("#grevenue-display").html(respObj.grevenue);
	jQuery("#commission-display").html(respObj.commission);
	jQuery("#vat-display").html(respObj.vat);
	jQuery("#payable-display").html(respObj.payable);
	jQuery("#redeem-display").html(respObj.redeem);
	jQuery("#total-sold-display").html(respObj.total_sold);
	jQuery("#balance-due-display").html(respObj.balanceDue);
	// table items per product id  -- must strip currency sign
	jQuery("#grevenue-display-" + productId).html(respObj.grevenue.split(" ")[1]);
	jQuery("#commission-display-" + productId).html(
		respObj.commission.split(" ")[1]
	);
	jQuery("#vat-display-" + productId).html(respObj.vat.split(" ")[1]);
	jQuery("#payable-display-" + productId).html(respObj.payable.split(" ")[1]);
	jQuery("#redeem-display-" + productId).html(respObj.redeem);
	// jQuery("#total-sold-display-" + productId).html(respObj.total_sold.split(" ")[1]);
	jQuery("#balance-due-display-" + productId).html(
		respObj.balanceDue.split(" ")[1]
	);

	jQuery("#hidden-values").html(respObj.hiddenValues);
};

const updateVenueCalcs = (respObj) => {
	let servedMulti = jQuery("#sum-multiplier").val();
	jQuery("#gr-value-total").html(respObj.sumGrValue);
	jQuery("#paid-amount-total").html(respObj.sumTotalPaid);
	jQuery("#served-total").html(
		parseInt(respObj.sumRedeemed) * parseInt(servedMulti)
	);
	jQuery("#net-payable-total").html(respObj.sumNetPayable);
	jQuery("#balance-due-total").html(respObj.sumBalanceDue);
	jQuery("#summary-hidden-values").html(respObj.sumHiddenValues);
	jQuery("#gr-value-table-total").html(respObj.sumGrValue.split(" ")[1]);
	jQuery("#net-payable-table-total").html(respObj.sumNetPayable.split(" ")[1]);
	jQuery("#balance-due-table-total").html(respObj.sumBalanceDue.split(" ")[1]);
	jQuery("#redeem-display-table-total").html(respObj.sumRedeemed);
	jQuery("#commission-display-table-total").html(
		respObj.sumCommission.split(" ")[1]
	);
	jQuery("#vat-display-table-total").html(respObj.sumVat.split(" ")[1]);
};

const tasteGetProductInfo = () => {
	let productInfo = {};
	productInfo.product_id = jQuery("#taste-product-id").val();
	productInfo.gr_value = jQuery("#taste-gr-value").val();
	productInfo.commission_value = jQuery("#taste-commission-value").val();
	productInfo.vat_value = jQuery("#taste-vat-value").val();
	productInfo.redeem = jQuery("#taste-redeem").val();
	productInfo.total_sold = jQuery("#taste-total-sold").val();
	productInfo.total_paid = jQuery("#taste-total-paid").val();
	return productInfo;
};

const tasteGetVenueInfo = () => {
	let venueInfo = {};
	venueInfo.revenue = jQuery("#sum-gr-value").val();
	venueInfo.commission = jQuery("#sum-commission").val();
	venueInfo.vat = jQuery("#sum-vat").val();
	venueInfo.redeemed = jQuery("#sum-redeemed").val();
	venueInfo.net_payable = jQuery("#sum-net-payable").val();
	venueInfo.paid_amount = jQuery("#sum-total-paid").val();
	venueInfo.balance_due = jQuery("#sum-balance-due").val();
	return venueInfo;
};

const tasteLoadRedeemButtons = () => {
	// this sets up click event for the ajax returned html
	jQuery(".order-redeem-btn").click(function (e) {
		e.preventDefault();
		let $rowData = jQuery(this).parent().parent();
		let orderId = $rowData.data("order-id");
		let orderItemId = $rowData.data("order-item-id");
		let orderQty = $rowData.data("order-qty");
		tasteRedeemVoucher([{ orderId, orderItemId, orderQty }]);
	});

	jQuery("#checkbox-all").click(function (e) {
		let checkVal = jQuery(this).prop("checked");
		jQuery(".order-redeem-check").prop("checked", checkVal);
		checkRedeemAllDisable();
	});

	jQuery(".order-redeem-check").click(function (e) {
		checkRedeemAllDisable();
	});

	jQuery("#order-redeem-checked-btn").click(function (e) {
		e.preventDefault();
		let orderInfoList = [];
		jQuery(".order-redeem-check:checked").each((ndx, chckbox) => {
			let $rowData = jQuery(chckbox).parent().parent();
			let orderId = $rowData.data("order-id");
			let orderItemId = $rowData.data("order-item-id");
			let orderQty = $rowData.data("order-qty");
			orderInfoList.push({ orderId, orderItemId, orderQty });
		});
		tasteRedeemVoucher(orderInfoList);
	});

	jQuery("#make-payment-btn").length &&
		jQuery("#make-payment-btn").click(function (e) {
			e.preventDefault();
			let mapAmount = jQuery("#map-amount").val();
			tasteMakePayment(mapAmount);
		});
};

const tasteLoadButtons = () => {
	jQuery(".product-select-btn").click(function (e) {
		e.preventDefault();
		let prodId = jQuery(this).data("prod-id");
		tasteLoadVouchers(prodId);
	});
};

const tasteLoadVenueFormEvents = () => {
	let $venueSelect = jQuery("#venue-select");
	$venueSelect.length &&
		$venueSelect.change(function () {
			let $selectVenueBtn = jQuery("#select-venue-btn");
			let selectVal = parseInt(this.value);
			if (selectVal) {
				$selectVenueBtn.prop("disabled", false);
			} else {
				$selectVenueBtn.prop("disabled", true);
			}
		});
};

const checkRedeemAllDisable = () => {
	if (jQuery(".order-redeem-check:checked").length) {
		console.log("turn off disable");
		jQuery("#order-redeem-checked-btn").prop("disabled", false);
	} else {
		console.log("turn disable on");
		jQuery("#order-redeem-checked-btn").prop("disabled", true);
	}
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
	jQuery("#topbutton").click(function (e) {
		e.preventDefault();
		$("html, body").animate(
			{
				scrollTop: $("#venue-summary-div").offset().top,
			},
			600
		);
	});
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
