jQuery(document).ready(function () {
	tasteLoadButtons();
	tasteLoadVenueFormEvents();
});

const tasteLoadVouchers = (prodId) => {
	console.log("prodId: ", prodId);
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
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error loading vouchers: " + errorThrown);
		},
	});
};

const tasteRedeemVoucher = (orderList) => {
	console.log(orderList);
	let modalMsg = "Redeeming Voucher(s)...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	// get info from hidden inputs to pass up for re-calc
	let productInfo = tasteGetProductInfo();
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "JSON",
		data: {
			action: "redeem_voucher",
			security: tasteVenue.security,
			order_list: orderList,
			product_info: productInfo,
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

				jQuery("#grevenue-display").html(respObj.grevenue);
				jQuery("#commission-display").html(respObj.commission);
				jQuery("#vat-display").html(respObj.vat);
				jQuery("#payable-display").html(respObj.payable);
				jQuery("#redeem-display").html(respObj.redeem);
				jQuery("#total-display").html(respObj.total);
				jQuery("#balance-due-display").html(respObj.balanceDue);
				jQuery("#hidden-values").html(respObj.hiddenValues);
			}
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error redeeming voucher: " + errorThrown);
		},
	});
};

const tasteGetProductInfo = () => {
	let productInfo = {};
	productInfo.product_id = jQuery("taste-product-id").val();
	productInfo.gr_value = jQuery("#taste-gr-value").val();
	productInfo.commission_value = jQuery("#taste-commission-value").val();
	productInfo.vat_value = jQuery("#taste-vat-value").val();
	productInfo.redeem = jQuery("#taste-redeem").val();
	productInfo.total = jQuery("#taste-total").val();
	productInfo.total_paid = jQuery("#taste-total-paid").val();
	return productInfo;
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
			console.log(selectVal);
			console.log($selectVenueBtn.prop("disabled"));
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
