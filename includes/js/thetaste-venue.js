let cmDisplayMode;
jQuery(document).ready(function () {
	if ($("body").hasClass("campaign-manager")) {
		cmDisplayMode = tasteVenue?.displayMode;
		cmDisplayMode = cmDisplayMode || "redeem";
		setupToggleButtons();
		setDisplayForMode();
		buildPaymentOrders();
		displayOrderPaymentInfo();
		tasteLoadPaymentByOrdersModal();
		/*
		if (jQuery(".all-payments-row").length) {
			console.log("here");
			const tooltipOptions = {
				placement: "right",
				html: true,
				container: "body",
			};
			// jQuery(".all-payments-row").tooltip(tooltipOptions);
		}			*/
	}
	tasteLoadButtons();
	tasteLoadCollapseIcons();
	jQuery("#topbutton").length && tasteLoadScrollUp();
});

const tasteLoadVouchers = (
	prodId,
	multiplier,
	cutoffDate,
	makePaymentsBelow
) => {
	let modalMsg = "Loading Vouchers...";
	tasteDispMsg(modalMsg);
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "html",
		data: {
			action: "load_vouchers",
			security: tasteVenue.security,
			product_id: prodId,
			multiplier: multiplier,
			cutoff_date: cutoffDate,
			make_payments_below: makePaymentsBelow,
		},
		success: function (responseText) {
			//console.log(responseText);
			jQuery("#all-payments-collapse").length &&
				jQuery("#all-payments-collapse").collapse("hide");
			jQuery("#voucher-list-div").html(responseText);
			setDisplayForMode();
			tasteLoadVoucherPaymentButtons();
			tasteCloseMsg();
			tasteScrollToVouchers();
			jQuery("#all-payments-collapse").length &&
				jQuery("#all-payments-collapse").collapse("hide");
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			console.log(errorThrown);
			alert(
				"Error loading vouchers. Your login may have timed out. Please refresh the page and try again."
			);
		},
	});
};

const setDisplayForMode = () => {
	if ("redeem" === cmDisplayMode) {
		jQuery(".payment-mode-only").hide();
		jQuery(".redeem-mode-only").show();
	} else {
		jQuery(".redeem-mode-only").hide();
		jQuery(".payment-mode-only").show();
	}
};

const buildPaymentOrders = () => {
	// each paymentOrdrs object property will be a product id
	// containing an array of orders that are selected on the screen
	tasteVenue.paymentOrders = {
		totalNetPayable: 0,
		totalQty: 0,
		productList: {},
	};
	jQuery(".product-select-for-payments").each(function () {
		const prodId = $(this).data("prod-id");
		tasteVenue.paymentOrders.productList[prodId] = {
			netPayable: 0,
			orderQty: 0,
			orderItemList: [],
		};
	});
};

const displayOrderPaymentInfo = () => {
	// re-calcs totals and displays on screen
	let totalPayments = 0;
	let totalQty = 0;
	for (const [prodId, prodInfo] of Object.entries(
		tasteVenue.paymentOrders.productList
	)) {
		let prodTotal = 0;
		let prodQtyTotal = 0;
		prodInfo.orderItemList.forEach((orderItem) => {
			prodTotal += orderItem.orderNetPayable;
			prodQtyTotal += orderItem.orderQty;
		});
		tasteVenue.paymentOrders.productList[prodId].netPayable =
			financial(prodTotal);
		tasteVenue.paymentOrders.productList[prodId].orderQty = prodQtyTotal;
		totalPayments += prodTotal;
		totalQty += prodQtyTotal;
		jQuery(`#selected-pay-amt-${prodId}`).text(financial(prodTotal));
	}
	tasteVenue.paymentOrders.totalNetPayable = financial(totalPayments);
	tasteVenue.paymentOrders.totalQty = totalQty;
	jQuery("#select-orders-pay-total").text(financial(totalPayments));
	jQuery("#payAllSelected").attr("disabled", !totalPayments);
};

const setupToggleButtons = () => {
	jQuery(".toggle-btn")
		.off("click")
		.click(function (e) {
			e.preventDefault();
			$this = jQuery(this);
			const mode = $this.data("toggle");
			const toggleId = $this.attr("id");
			const $toggleContainer = $this.parent().parent();
			const $otherToggleBtn = $toggleContainer
				.find(".toggle-btn")
				.not(`#${toggleId}`);
			$this.addClass("toggle-on").attr("disabled", true);
			$otherToggleBtn.removeClass("toggle-on").attr("disabled", false);
			cmDisplayMode = mode;
			setDisplayForMode();
		});
};

const tasteRedeemVoucher = (orderList, redeemFlg = true) => {
	let modalMsg = redeemFlg
		? "Redeeming Voucher(s)..."
		: "Un Redeeming Voucher...";
	tasteDispMsg(modalMsg);
	// get info from hidden inputs to pass up for re-calc
	let productInfo = tasteGetProductInfo();
	let productId = Object.keys(productInfo)[0];
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
			// console.log(responseText);
			let respObj = JSON.parse(responseText);
			if (respObj.error) {
				console.log(respObj);
				tasteCloseMsg();
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
				tasteCloseMsg();
			}
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			console.log(errorThrown);
			alert(
				"Error updating redemption information.  Your login may have timed out. Please refresh the page and try again."
			);
		},
	});
};

const tasteMakePayment = (
	paymentData,
	$modal,
	deleteMode,
	ordersFlag = false
) => {
	// for (let [k, v] of paymentData.entries()) {
	// 	console.log(k, v);
	// }
	$modal.modal("hide");
	const modalMsg = deleteMode ? "Deleting Payment..." : "Updating Payment...";
	tasteDispMsg(modalMsg);

	// get info from hidden inputs and data values to pass up for re-calc
	const productInfo = ordersFlag
		? tasteGetAllProductInfo()
		: tasteGetProductInfo();
	let postProductList = {};
	let postTotalAmount = 0;

	if (ordersFlag) {
		postProductList = Object.entries(
			tasteVenue.paymentOrders.productList
		).filter((prod) => {
			return prod[1].orderQty;
		});
		postTotalAmount = tasteVenue.paymentOrders.totalNetPayable;
	} else {
		postTotalAmount = paymentData.get("payment-amt");
		const productId = Object.keys(productInfo)[0];
		postProductList = [
			[
				productId,
				{
					netPayable: postTotalAmount,
					orderQty: 0,
					orderItemList: [],
				},
			],
		];
	}

	let venueInfo = tasteGetVenueInfo();
	let paymentInfo = {
		id: paymentData.get("payment-id"),
		amount: financial(postTotalAmount),
		payment_orig_amt: paymentData.get("payment-orig-amt"),
		payment_orig_date: paymentData.get("payment-orig-date"),
		timestamp: paymentData.get("payment-date"),
		comment: paymentData.get("payment-comment"),
		comment_visible_venues: paymentData.has("payment-comment-visibility")
			? 1
			: 0,
		attach_vat_invoice: paymentData.has("payment-invoice-attachment") ? 1 : 0,
		delete_mode: deleteMode,
		all_payment_cnt: paymentData.get("allpaymentcnt"),
		prod_payment_cnt: paymentData.get("prodpaymentcnt"),
		orders_flag: ordersFlag ? 1 : 0,
		product_order_list: JSON.stringify(postProductList),
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
			/****
			 *
			 *
			 * just tmp
			 */

			tasteCloseMsg();
			let respObj = JSON.parse(responseText);
			if (respObj.error) {
				tasteCloseMsg();
				alert("Error updating payment.\n" + respObj.error);
			} else {
				console.log(respObj);

				updateVenueCalcs(respObj);
				jQuery(".total-payments-display").html(respObj.totalPaid);
				jQuery("#balance-due-display").html(respObj.balanceDue);
				jQuery("#balance-due-display-" + productId).html(
					respObj.balanceDue.split(" ")[1]
				);
				//jQuery("#hidden-values").html(respObj.hiddenValues);
				jQuery("#hidden-payment-values").html(respObj.hiddenPaymentValues);

				if ("UPDATE" === respObj.editMode) {
					jQuery(`#pay-${paymentInfo.id}`).replaceWith(respObj.paymentLine);
					jQuery(`#all-pay-${paymentInfo.id}`).replaceWith(
						respObj.allPaymentLine
					);
				} else if ("INSERT" === respObj.editMode) {
					jQuery("#payment-lines").append(respObj.paymentLine);
					jQuery("#all-payment-lines").append(respObj.allPaymentLine);
				} else {
					jQuery(`#pay-${paymentInfo.id}`).remove();
					jQuery(`#all-pay-${paymentInfo.id}`).remove();
				}

				jQuery("#all-payments-cnt-disp").html(respObj.allPaymentCnt);
				jQuery("#all-payments-table").length &&
					jQuery("#all-payments-table").data(
						"allpaymentcnt",
						respObj.allPaymentCnt
					);

				const prodCntDisp = respObj.prodPaymentCnt
					? `Transaction Items (${respObj.prodPaymentCnt} Rows)`
					: "No Transactions Found";

				jQuery("#prod-transactions-cnt-display").html(prodCntDisp);
				jQuery("#audit-payment-table").data(
					"paymentcnt",
					respObj.prodPaymentCnt
				);

				tasteLoadInvoiceButtons();
				tasteCloseMsg();
			}
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			console.log(errorThrown);
			alert(
				"Error updating payment.  Your login may have timed out. Please refresh the page and try again."
			);
		},
	});
};

const filterProductList = (productListObj) => {};

const updateOfferCalcs = (respObj, productId) => {
	jQuery("#grevenue-display").html(respObj.grevenue);
	jQuery("#commission-display").html(respObj.commission);
	jQuery("#vat-display").html(respObj.vat);
	jQuery(".payable-display").html(respObj.payable);
	jQuery("#redeem-qty-display").html(respObj.redeemQty);
	jQuery("#total-sold-display").html(respObj.totalSold);
	jQuery("#balance-due-display").html(respObj.balanceDue);
	jQuery(".total-payments-display").html(respObj.totalPaid);
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
	jQuery(".paid-amount-total").html(respObj.sumTotalPaid);
	jQuery("#vouchers-total").html(respObj.sumRedeemedQty);
	jQuery("#served-total").html(respObj.sumNumServed);
	jQuery("#net-payable-total").html(respObj.sumNetPayable);
	jQuery("#balance-due-total").html(respObj.sumBalanceDue);
	respObj.sumHiddenValues &&
		jQuery("#summary-hidden-values").html(respObj.sumHiddenValues);
	respObj.sumHiddenPaymentValues &&
		jQuery("#summary-hidden-payment-values").html(
			respObj.sumHiddenPaymentValues
		);
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

	productInfo[jQuery("#taste-product-id").val()] = {
		price: jQuery("#taste-price").val(),
		commission_value: jQuery("#taste-commission-value").val(),
		vat_value: jQuery("#taste-vat-value").val(),
		redeem_qty: jQuery("#taste-redeem-qty").val(),
		total_sold: jQuery("#taste-total-sold").val(),
		total_paid: jQuery("#taste-total-paid").val(),
		multiplier: jQuery("#taste-product-multiplier").val(),
	};

	return productInfo;
};

const tasteGetAllProductInfo = () => {
	let productAllProductInfo = {};
	jQuery(".product-info-row").each((ndx, productRow) => {
		$productRow = jQuery(productRow);
		productAllProductInfo[$productRow.data("productid")] = {
			vat_value: $productRow.data("vatrate"),
			commisson_value: $productRow.data("commissionrate"),
			price: $productRow.data("price"),
			total_paid: $productRow.data("paidamount"),
		};
	});
	// console.log(productAllProductInfo);
	return productAllProductInfo;
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

	jQuery("#checkbox-payment-all")
		.off("click")
		.click(function (e) {
			const checkVal = jQuery(this).prop("checked");
			const prodId = jQuery("#taste-product-id").val();
			tasteVenue.paymentOrders.productList[prodId].orderItemList = [];
			jQuery(".order-payment-check").each(function () {
				$this = jQuery(this);
				const $rowData = $this.parent().parent();
				$this.prop("checked", checkVal);
				if (checkVal) {
					loadOrderPaymentInfo($rowData);
				}
				displayOrderPaymentInfo();
			});
		});

	jQuery(".order-payment-check")
		.off("click")
		.click(function (e) {
			const $rowData = jQuery(this).parent().parent();
			const orderItemId = $rowData.data("order-item-id");
			const checkVal = jQuery(this).prop("checked");
			const prodId = jQuery("#taste-product-id").val();
			if (checkVal) {
				loadOrderPaymentInfo($rowData);
			} else {
				tasteVenue.paymentOrders.productList[prodId].orderItemList =
					tasteVenue.paymentOrders.productList[prodId].orderItemList.filter(
						function (payItem) {
							payItem.orderItemId !== orderItemId;
						}
					);
			}
			displayOrderPaymentInfo();
		});

	const loadOrderPaymentInfo = ($rowData) => {
		const orderId = $rowData.data("order-id");
		const orderItemId = $rowData.data("order-item-id");
		const orderQty = $rowData.data("order-qty");
		const orderNetPayable = $rowData.data("order-net-payable");
		const prodId = jQuery("#taste-product-id").val();
		const orderInfo = {
			orderItemId,
			orderId,
			orderQty,
			orderNetPayable,
		};
		tasteVenue.paymentOrders.productList[prodId].orderItemList.push(orderInfo);
	};

	jQuery(".payment-save-btn").length &&
		jQuery(".payment-save-btn")
			.off("click")
			.click(function (e) {
				e.preventDefault();
				const $submitBtn = jQuery(this);
				const $modal = $submitBtn.closest(".modal");
				const formId = $submitBtn.attr("form");
				const $paymentForm = jQuery(`#${formId}`);
				let paymentData = new FormData($paymentForm[0]);
				// console.log(paymentData);
				// for (let [k, v] of paymentData.entries()) {
				// 	console.log(k, v);
				// }
				// check if delete button
				const btnId = $submitBtn.attr("id");
				const deleteMode = "modal-payment-delete-btn" === btnId;
				// get payment counts for both All Payments (if exists) and product Payments
				const allPayCount = jQuery("#all-payments-table").length
					? jQuery("#all-payments-table").data("allpaymentcnt")
					: 0;

				const prodPayCount = jQuery("#audit-payment-table").length
					? jQuery("#audit-payment-table").data("paymentcnt")
					: 0;

				paymentData.set("allpaymentcnt", allPayCount);
				paymentData.set("prodpaymentcnt", prodPayCount);
				tasteMakePayment(paymentData, $modal, deleteMode);
			});

	tasteLoadInvoiceButtons();
	tasteLoadCollapseIcons();
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
				let payGross = $invBtn.data("paygross");
				let paymentAmt = $invBtn.data("paymentamt");
				let paymentDate = $invBtn.data("paymentdate");
				let paymentId = $invBtn.data("paymentid");
				let commissionAmt = $invBtn.data("comm");
				let vatAmt = $invBtn.data("vat");
				let urlGetString = `?product_id=${productId}&pay_amt=${paymentAmt}&comm_amt=${commissionAmt}
												 &comm_val=${commissionVal}&vat_amt=${vatAmt}&vat_val=${vatVal}&pay_date=${paymentDate}
												 &venue_name=${venueName}&venue_addr1=${venueAddr1}&venue_addr2=${venueAddr2}&venue_city=${venueCity}
												 &venue_postal=${venuePostal}&pay_id=${paymentId}&pay_gross=${payGross}`;
				window.open(`${invoiceURL}${urlGetString}`, "_blank");
			});

	tasteSortPaymentTable();
};

const tasteLoadCollapseIcons = () => {
	jQuery(".collapse")
		.off("shown.bs.collapse")
		.on("shown.bs.collapse", function (e) {
			const collapseId = jQuery(this).attr("id");
			const $collapseIcon = jQuery("i[data-target='#" + collapseId + "']");
			$collapseIcon.removeClass("fa-plus-circle").addClass("fa-minus-circle");
		});

	jQuery(".collapse")
		.off("hidden.bs.collapse")
		.on("hidden.bs.collapse", function (e) {
			const collapseId = jQuery(this).attr("id");
			const $collapseIcon = jQuery("i[data-target='#" + collapseId + "']");
			$collapseIcon.removeClass("fa-minus-circle").addClass("fa-plus-circle");
		});
};

const tasteLoadPaymentCommentModal = () => {
	jQuery("#addCommentModal")
		.off("show.bs.modal")
		.on("show.bs.modal", function (e) {
			const button = jQuery(e.relatedTarget);
			const $form = jQuery(this).find("form");
			const comment = button.data("comment");
			const paymentId = button.data("paymentid");
			const paymentDate = button.data("paymentdate");
			const paymentAmt = button.data("paymentamt");
			const commentVisibility = button.data("commentvisibility");
			jQuery("#modal-comment").val(comment);
			jQuery("#modal-comment-id").val(paymentId);
			jQuery("#modal-comment-amt").val(paymentAmt);
			jQuery("#modal-comment-orig-amt").val(paymentAmt);
			jQuery("#modal-comment-date").val(paymentDate);
			jQuery("#modal-comment-orig-date").val(paymentDate);
			jQuery("#addCommentModalLabel").html(
				"<strong>Add / Edit Comment for Payment " + paymentId + "</strong>"
			);
			jQuery("#modal-comment-visible-checkbox").prop(
				"checked",
				commentVisibility
			);
			$form.initDirty(true);
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
			const $form = jQuery(this).find("form");
			const comment = button.data("comment");
			const paymentId = button.data("paymentid");
			const paymentDate = button.data("paymentdate");
			const paymentAmt = button.data("paymentamt");
			const commentVisibility = button.data("commentvisibility");
			const invoiceAttachment = button.data("invoiceattachment");
			const deleteMode = button.data("deletemode");
			jQuery("#modal-payment-comment").val(comment);
			jQuery("#modal-payment-id").val(paymentId);
			jQuery("#modal-payment-amt").val(paymentAmt);
			jQuery("#modal-payment-orig-amt").val(paymentAmt);
			jQuery("#modal-payment-date").val(paymentDate);
			jQuery("#modal-payment-orig-date").val(paymentDate);
			jQuery("#payment-comment-visible-checkbox").prop(
				"checked",
				commentVisibility
			);
			jQuery("#payment-attach-invoice-checkbox").prop(
				"checked",
				invoiceAttachment
			);
			if (deleteMode) {
				$form.find(":input").prop("readonly", true);
				jQuery("#payment-modal-addedit").hide();
				jQuery("#payment-modal-delete").show();
				jQuery("#addEditPaymentModalLabel").html(
					"<strong>Delete Payment " + paymentId + "<br/>Are you sure?</strong>"
				);
			} else if (paymentId) {
				// we are in edit mode
				$form.find(":input").prop("readonly", false);
				jQuery("#payment-modal-delete").hide();
				jQuery("#payment-modal-addedit").show();
				jQuery("#addEditPaymentModalLabel").html(
					"<strong>Edit Payment " + paymentId + "</strong>"
				);
			} else {
				// add mode
				$form.find(":input").prop("readonly", false);
				jQuery("#payment-modal-delete").hide();
				jQuery("#payment-modal-addedit").show();
				jQuery("#addEditPaymentModalLabel").html(
					"<strong>Enter New Transaction"
				);
			}

			$form.initDirty(true);
		});
};

const tasteLoadPaymentByOrdersModal = () => {
	jQuery("#paySelectedModal")
		.off("show.bs.modal")
		.on("show.bs.modal", function (e) {
			const tableRows = buildOrdersPaymentTableRows();
			// console.log(tableRows);
			jQuery("#orders-payment-table > tbody").html(tableRows.tbodyRows);
			jQuery("#orders-payment-table > tfoot").html(tableRows.tfootRow);
		});

	// load the submit button for this modal
	jQuery("#orders-payment-submit").length &&
		jQuery("#orders-payment-submit")
			.off("click")
			.click(function (e) {
				e.preventDefault();
				const $submitBtn = jQuery(this);
				const $modal = $submitBtn.closest(".modal");
				const formId = $submitBtn.attr("form");
				const $paymentForm = jQuery(`#${formId}`);
				let paymentData = new FormData($paymentForm[0]);
				const deleteMode = false;
				// get payment counts for both All Payments (if exists) and product Payments
				const allPayCount = jQuery("#all-payments-table").length
					? jQuery("#all-payments-table").data("allpaymentcnt")
					: 0;

				const prodPayCount = jQuery("#audit-payment-table").length
					? jQuery("#audit-payment-table").data("paymentcnt")
					: 0;

				paymentData.set("allpaymentcnt", allPayCount);
				paymentData.set("prodpaymentcnt", prodPayCount);
				tasteMakePayment(paymentData, $modal, deleteMode, true);
			});
};

const buildOrdersPaymentTableRows = () => {
	let tbodyRows = "";

	for (const [prodId, prodInfo] of Object.entries(
		tasteVenue.paymentOrders.productList
	)) {
		tbodyRows += `
			<tr>
				<td>${prodId}</td>
				<td>${prodInfo.orderQty}</td>
				<td>${financial(prodInfo.netPayable)}</td>
			</tr>
		`;
	}
	tfootRow = `
		<tr>
			<td>Totals:</td>
			<td>${tasteVenue.paymentOrders.totalQty}</td>
			<td>${financial(tasteVenue.paymentOrders.totalNetPayable)}</td>
		</tr>
	`;
	return {
		tbodyRows,
		tfootRow,
	};
};

const tasteSortPaymentTable = () => {
	jQuery("#audit-payment-table").length &&
		tasteSortTableByColumn("audit-payment-table", "sort-by-date", true);
	jQuery("#all-payments-table").length &&
		tasteSortTableByColumn("all-payments-table", "sort-by-date", true);
	jQuery("#all-payments-table").length &&
		tasteSortTableByColumn("all-payments-table", "sort-by-product", false);
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
				let cutoffDate = jQuery("#venue_cutoff_date").val();
				let makePaymentsBelow = jQuery(this).data("payments-below");
				// console.log("cutoffDate: ", cutoffDate);
				tasteLoadVouchers(prodId, multiplier, cutoffDate, makePaymentsBelow);
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
	let offset = $("#voucher-list-div").offset().top - 500;
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
			scrollTop: $("#voucher-list-div").offset().top - 25,
		},
		600
	);
};

const financial = (num) => {
	return Number.parseFloat(num).toFixed(2);
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
function tasteDispMsg(msg) {
	// create own modal popup window
	// closeBtn is true if normal msg box..
	// false is for ajax temp disp

	/*
	if (closeBtn) {
		jQuery("#taste-msg-close").show();
	} else {
		jQuery("#taste-msg-close").hide();
	}
	*/

	jQuery("#taste-msg-text").html(msg);
	jQuery("#spinner-modal").modal("show");
	// jQuery("#taste-modal-layer").show();
}
/**
 * Closes the Modal msg box
 * @returns {void}
 */
function tasteCloseMsg() {
	// jQuery("#taste-modal-layer").hide();
	jQuery("#spinner-modal").modal("hide");
}
