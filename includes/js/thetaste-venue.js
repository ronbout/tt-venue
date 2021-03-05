jQuery(document).ready(function () {
	tasteLoadButtons();
	jQuery("#topbutton").length && tasteLoadScrollUp();
});

const tasteLoadVouchers = (prodId, multiplier) => {
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
			multiplier: multiplier,
		},
		success: function (responseText) {
			tasteCloseMsg();
			//console.log(responseText);
			jQuery("#voucher-list-div").html(responseText);
			tasteLoadVoucherPaymentButtons();
			tasteScrollToVouchers();
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error loading vouchers: " + errorThrown);
		},
	});
};

const tasteRedeemVoucher = (orderList, redeemFlg = true) => {
	let modalMsg = redeemFlg
		? "Redeeming Voucher(s)..."
		: "Un Redeeming Voucher...";
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
			redeem_flg: redeemFlg ? 1 : 0,
		},
		success: function (responseText) {
			tasteCloseMsg();
			// console.log(responseText);
			let respObj = JSON.parse(responseText);
			if (respObj.error) {
				console.log(respObj);
				alert("error in redeem Voucher ajax code");
			} else {
				orderList.map((orderInfo) => {
					if (redeemFlg) {
						jQuery("#td-btn-order-id-" + orderInfo.orderId).html(
							'<button	class="btn btn-info order-unredeem-btn">Unredeem</button>'
						);
						jQuery("#td-check-order-id-" + orderInfo.orderId).html("");
					} else {
						jQuery("#td-btn-order-id-" + orderInfo.orderId).html(
							'<button	class="btn btn-success order-redeem-btn">Redeem</button>'
						);
						jQuery("#td-check-order-id-" + orderInfo.orderId).html(
							'<input type="checkbox" class="order-redeem-check">'
						);
					}
				});

				respObj.emails.map((emailInfo) => {
					jQuery("#email-display-" + emailInfo.orderId).html(emailInfo.email);
				});

				updateOfferCalcs(respObj, productId);
				updateVenueCalcs(respObj);
				tasteLoadVoucherPaymentButtons();
			}
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error redeeming voucher: " + errorThrown);
		},
	});
};

const tasteMakePayment = (paymentData, $modal) => {
	// jQuery("#addEditPaymentModal").modal("hide");
	// jQuery("#addCommentModal").modal("hide");
	$modal.modal("hide");
	let modalMsg = "Updating Payment...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	// get info from hidden inputs to pass up for re-calc
	let productInfo = tasteGetProductInfo();
	let productId = productInfo.product_id;
	let venueInfo = tasteGetVenueInfo();
	let paymentInfo = {
		id: paymentData.get("payment-id"),
		pid: productId,
		amount: paymentData.get("payment-amt"),
		payment_orig_amt: paymentData.get("payment-orig-amt"),
		timestamp: paymentData.get("payment-date"),
		comment: paymentData.get("payment-comment"),
	};
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "JSON",
		data: {
			action: "make_payment",
			security: tasteVenue.security,
			payment_info: paymentInfo,
			product_info: productInfo,
			venue_info: venueInfo,
		},
		success: function (responseText) {
			tasteCloseMsg();
			let respObj = JSON.parse(responseText);
			if (respObj.error) {
				alert("Error updating payment.\n" + respObj.error);
			} else {
				console.log(respObj);

				updateVenueCalcs(respObj);
				jQuery("#balance-due-display").html(respObj.balanceDue);
				jQuery("#balance-due-display-" + productId).html(
					respObj.balanceDue.split(" ")[1]
				);
				jQuery("#hidden-values").html(respObj.hiddenValues);

				if ("edit" === respObj.editMode) {
					jQuery(`#pay-${paymentInfo.id}`).replaceWith(respObj.paymentLine);
				} else {
					jQuery("#payment-lines").append(respObj.paymentLine);
				}

				tasteLoadInvoiceButtons();
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
	jQuery("#redeem-qty-display").html(respObj.redeemQty);
	jQuery("#total-sold-display").html(respObj.totalSold);
	jQuery("#balance-due-display").html(respObj.balanceDue);
	// table items per product id  -- must strip currency sign
	jQuery("#grevenue-display-" + productId).html(respObj.grevenue.split(" ")[1]);
	jQuery("#commission-display-" + productId).html(
		respObj.commission.split(" ")[1]
	);
	jQuery("#vat-display-" + productId).html(respObj.vat.split(" ")[1]);
	jQuery("#payable-display-" + productId).html(respObj.payable.split(" ")[1]);
	jQuery("#redeem-qty-display-" + productId).html(respObj.numServed);
	// jQuery("#total-sold-display-" + productId).html(respObj.total_sold.split(" ")[1]);
	jQuery("#balance-due-display-" + productId).html(
		respObj.balanceDue.split(" ")[1]
	);

	jQuery("#hidden-values").html(respObj.hiddenValues);
};

const updateVenueCalcs = (respObj) => {
	jQuery("#gr-value-total").html(respObj.sumGrValue);
	jQuery("#paid-amount-total").html(respObj.sumTotalPaid);
	jQuery("#vouchers-total").html(respObj.sumRedeemedQty);
	jQuery("#served-total").html(respObj.sumNumServed);
	jQuery("#net-payable-total").html(respObj.sumNetPayable);
	jQuery("#balance-due-total").html(respObj.sumBalanceDue);
	jQuery("#summary-hidden-values").html(respObj.sumHiddenValues);
	jQuery("#gr-value-table-total").html(respObj.sumGrValue.split(" ")[1]);
	jQuery("#net-payable-table-total").html(respObj.sumNetPayable.split(" ")[1]);
	jQuery("#balance-due-table-total").html(respObj.sumBalanceDue.split(" ")[1]);
	jQuery("#redeem-qty-display-table-total").html(respObj.sumNumServed);
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
	productInfo.redeem_qty = jQuery("#taste-redeem-qty").val();
	productInfo.total_sold = jQuery("#taste-total-sold").val();
	productInfo.total_paid = jQuery("#taste-total-paid").val();
	productInfo.multiplier = jQuery("#taste-product-multiplier").val();
	return productInfo;
};

const tasteGetVenueInfo = () => {
	let venueInfo = {};
	venueInfo.revenue = jQuery("#sum-gr-value").val();
	venueInfo.commission = jQuery("#sum-commission").val();
	venueInfo.vat = jQuery("#sum-vat").val();
	venueInfo.redeemed_cnt = jQuery("#sum-redeemed-cnt").val();
	venueInfo.redeemed_qty = jQuery("#sum-redeemed-qty").val();
	venueInfo.num_served = jQuery("#sum-num-served").val();
	venueInfo.net_payable = jQuery("#sum-net-payable").val();
	venueInfo.paid_amount = jQuery("#sum-total-paid").val();
	venueInfo.balance_due = jQuery("#sum-balance-due").val();
	return venueInfo;
};

const tasteLoadVoucherPaymentButtons = () => {
	// this sets up click event for the ajax returned html
	// as well as any other processing required post voucher load
	jQuery(".order-redeem-btn")
		.off("click")
		.click(function (e) {
			e.preventDefault();
			let $rowData = jQuery(this).parent().parent();
			let orderId = $rowData.data("order-id");
			let orderItemId = $rowData.data("order-item-id");
			let orderQty = $rowData.data("order-qty");
			tasteRedeemVoucher([{ orderId, orderItemId, orderQty }], true);
		});

	jQuery(".order-unredeem-btn")
		.off("click")
		.click(function (e) {
			e.preventDefault();
			let $rowData = jQuery(this).parent().parent();
			let orderId = $rowData.data("order-id");
			let orderItemId = $rowData.data("order-item-id");
			let orderQty = $rowData.data("order-qty");
			tasteRedeemVoucher([{ orderId, orderItemId, orderQty }], false);
		});

	jQuery("#checkbox-all")
		.off("click")
		.click(function (e) {
			let checkVal = jQuery(this).prop("checked");
			jQuery(".order-redeem-check").prop("checked", checkVal);
			checkRedeemAllDisable();
		});

	jQuery(".order-redeem-check")
		.off("click")
		.click(function (e) {
			checkRedeemAllDisable();
		});

	jQuery(".order-redeem-checked-btn")
		.off("click")
		.click(function (e) {
			e.preventDefault();
			let orderInfoList = [];
			jQuery(".order-redeem-check:checked").each((ndx, chckbox) => {
				let $rowData = jQuery(chckbox).parent().parent();
				let orderId = $rowData.data("order-id");
				let orderItemId = $rowData.data("order-item-id");
				let orderQty = $rowData.data("order-qty");
				orderInfoList.push({ orderId, orderItemId, orderQty });
			});
			tasteRedeemVoucher(orderInfoList, true);
		});

	jQuery(".payment-save-btn").length &&
		jQuery(".payment-save-btn")
			.off("click")
			.click(function (e) {
				e.preventDefault();
				const $submitBtn = jQuery(this);
				const $modal = $submitBtn.closest(".modal");
				const formId = $submitBtn.attr("form");
				const paymentForm = jQuery(`#${formId}`);
				let paymentData = new FormData(paymentForm[0]);
				tasteMakePayment(paymentData, $modal);
			});

	tasteLoadInvoiceButtons();
	tasteLoadPaymentCommentModal();
	tasteLoadPaymentAddEditModal();
};

const tasteLoadInvoiceButtons = () => {
	jQuery(".print-invoice-btn").length &&
		jQuery(".print-invoice-btn")
			.off("click")
			.click(function (e) {
				e.preventDefault();
				$invBtn = jQuery(this);
				let $productData = $invBtn.closest("table");
				let invoiceURL = $productData.data("invoiceurl");
				let productId = $productData.data("productid");
				let venueName = $productData.data("venuename");
				let venueAddr1 = $productData.data("venueaddr1");
				let venueAddr2 = $productData.data("venueaddr2");
				let venueCity = $productData.data("venuecity");
				let venuePostal = $productData.data("venuepostcode");
				let commissionVal = $productData.data("commval");
				let vatVal = $invBtn.data("paymentvatval");
				let paymentAmt = $invBtn.data("paymentamt");
				let paymentDate = $invBtn.data("paymentdate");
				let paymentId = $invBtn.data("paymentid");
				let commissionAmt = $invBtn.data("comm");
				let vatAmt = $invBtn.data("vat");
				let urlGetString = `?product_id=${productId}&pay_amt=${paymentAmt}&comm_amt=${commissionAmt}
												 &comm_val=${commissionVal}&vat_amt=${vatAmt}&vat_val=${vatVal}&pay_date=${paymentDate}
												 &venue_name=${venueName}&venue_addr1=${venueAddr1}&venue_addr2=${venueAddr2}&venue_city=${venueCity}
												 &venue_postal=${venuePostal}&pay_id=${paymentId}`;
				window.open(`${invoiceURL}${urlGetString}`, "_blank");
			});

	tasteSortPaymentTable();
};

const tasteLoadPaymentCommentModal = () => {
	jQuery("#addCommentModal")
		.off("show.bs.modal")
		.on("show.bs.modal", function (e) {
			const button = jQuery(e.relatedTarget);
			const comment = button.data("comment");
			const paymentId = button.data("paymentid");
			const paymentDate = button.data("paymentdate");
			const paymentAmt = button.data("paymentamt");
			jQuery("#modal-comment").val(comment);
			jQuery("#modal-comment-id").val(paymentId);
			jQuery("#modal-comment-amt").val(paymentAmt);
			jQuery("#modal-comment-orig-amt").val(paymentAmt);
			jQuery("#modal-comment-date").val(paymentDate);
			jQuery("#addCommentModalLabel").html(
				"<strong>Add / Edit Comment for Payment " + paymentId + "</strong>"
			);
			jQuery(this).find("form").initDirty(true);
			jQuery("#modal-comment-form").initDirty(true);
		});
};

/**
 *  TODO: 	combine these, by using the name attribute
 * 					rather than the id and jQuery.each()
 */

const tasteLoadPaymentAddEditModal = () => {
	jQuery("#addEditPaymentModal")
		.off("show.bs.modal")
		.on("show.bs.modal", function (e) {
			const button = jQuery(e.relatedTarget);
			const comment = button.data("comment");
			const paymentId = button.data("paymentid");
			const paymentDate = button.data("paymentdate");
			const paymentAmt = button.data("paymentamt");
			jQuery("#modal-payment-comment").val(comment);
			jQuery("#modal-payment-id").val(paymentId);
			jQuery("#modal-payment-amt").val(paymentAmt);
			jQuery("#modal-payment-orig-amt").val(paymentAmt);
			jQuery("#modal-payment-date").val(paymentDate);
			if (paymentId) {
				// we are in edit mode
				jQuery("#addEditPaymentModalLabel").html(
					"<strong>Edit Payment " + paymentId + "</strong>"
				);
			} else {
				jQuery("#addEditPaymentModalLabel").html("<strong>Enter New Payment");
			}

			jQuery(this).find("form").initDirty(true);
		});
};

const tasteSortPaymentTable = () => {
	tasteSortTableByColumn("audit-payment-table", "sort-by-date", true);
};

const tasteLoadButtons = () => {
	jQuery(".product-select-btn")
		.off("click")
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

const checkRedeemAllDisable = () => {
	if (jQuery(".order-redeem-check:checked").length) {
		jQuery(".order-redeem-checked-btn").prop("disabled", false);
	} else {
		jQuery(".order-redeem-checked-btn").prop("disabled", true);
	}
};

const tasteLoadScrollUp = () => {
	let offset = $("#voucher-list-div").offset().top;
	let duration = 500;
	$(window).scroll(function () {
		if ($(this).scrollTop() < offset) {
			$("#topbutton").fadeOut(duration);
		} else {
			$("#topbutton").fadeIn(duration);
		}
	});
	jQuery("#topbutton")
		.off("click")
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
