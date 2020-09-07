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
	productInfo.product_id = $("taste-product-id").val();
	productInfo.gr_value = $("#taste-gr-value").val();
	productInfo.commission_value = $("#taste-commission-value").val();
	productInfo.vat_value = $("#taste-vat-value").val();
	productInfo.redeem = $("#taste-redeem").val();
	productInfo.total = $("#taste-total").val();
	productInfo.total_paid = $("#taste-total-paid").val();
	return productInfo;
};

const tasteLoadRedeemButtons = () => {
	// this sets up click event for the ajax returned html
	$(".order-redeem-btn").click(function (e) {
		e.preventDefault();
		let $rowData = $(this).parent().parent();
		let orderId = $rowData.data("order-id");
		let orderItemId = $rowData.data("order-item-id");
		let orderQty = $rowData.data("order-qty");
		tasteRedeemVoucher([{ orderId, orderItemId, orderQty }]);
	});

	$("#checkbox-all").click(function (e) {
		let checkVal = $(this).prop("checked");
		$(".order-redeem-check").prop("checked", checkVal);
	});

	$("#order-redeem-checked-btn").click(function (e) {
		e.preventDefault();
		let orderInfoList = [];
		$(".order-redeem-check:checked").each((ndx, chckbox) => {
			let $rowData = $(chckbox).parent().parent();
			let orderId = $rowData.data("order-id");
			let orderItemId = $rowData.data("order-item-id");
			let orderQty = $rowData.data("order-qty");
			orderInfoList.push({ orderId, orderItemId, orderQty });
		});
		tasteRedeemVoucher(orderInfoList);
	});
};

const tasteLoadButtons = () => {
	$(".product-select-btn").click(function (e) {
		e.preventDefault();
		let prodId = $(this).data("prod-id");
		tasteLoadVouchers(prodId);
	});
};

const tasteLoadVenueFormEvents = () => {
	let $venueSelect = $("#venue-select");
	$venueSelect.length &&
		$venueSelect.change(function () {
			let $selectVenueBtn = $("#select-venue-btn");
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
		$("#taste-msg-close").show();
	} else {
		$("#taste-msg-close").hide();
	}

	$("#taste-msg-text").html(msg);
	$("#taste-msg-box").show();
	$("#taste-modal-layer").show();
}
/**
 * Closes the Modal msg box
 * @returns {void}
 */
function tasteCloseMsg() {
	$("#taste-modal-layer").hide();
	$("#taste-msg-box").hide();
}
