let cmDisplayMode;
const TASTE_ORDER_STATUS_PAID = 0; // (or-display-paid)
const TASTE_ORDER_STATUS_NOT_PAID_REDEEMED = 1; //  (or-display-pay-due)
const TASTE_ORDER_STATUS_NOT_PAID_UNREDEEMED = 2; // unredeemed, not expired  (or-display-unredeemed)
const TASTE_ORDER_STATUS_NOT_PAID_EXPIRED = 3; // unredeemed, expired  (or-display-expired)
const TASTE_PBO_NET_PAYABLE_THRESHOLD = 0.05;
jQuery(document).ready(function () {
  if ($("body").hasClass("campaign-manager")) {
    cmDisplayMode = tasteVenue?.displayMode;
    cmDisplayMode = cmDisplayMode || "redeem";
    setupToggleButtons();
    setDisplayForMode();
    buildPaymentOrders();
    displayOrderPaymentInfo();
    tasteLoadPaymentByOrdersModal();
    tasteLoadInvoiceButtons();
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
  orderPaymentChecklist = buildOrderPaymentChecklist(prodId) || [];
  let venueView = jQuery("#hidden_venue_view").val();
  // console.log(orderPaymentChecklist);
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
      order_payments_checklist: orderPaymentChecklist,
      edit_payment_id: tasteVenue.paymentOrders.editPaymentId,
      venue_view: venueView,
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

const buildOrderPaymentChecklist = (prodId) => {
  if (!tasteVenue.paymentOrders.productList[prodId]) {
    return [];
  }
  const orderList = tasteVenue.paymentOrders.productList[
    prodId
  ].orderItemList.map((orderInfo) => {
    return orderInfo.orderItemId;
  });
  return orderList;
};

const buildPaymentOrders = () => {
  // each paymentOrdrs object property will be a product id
  // containing an array of orders that are selected on the screen
  tasteVenue.paymentOrders = {
    totalNetPayable: 0,
    editPaymentId: 0,
    PBOMode: "insert",
    editOrigPayDate: "",
    editOrigPayStatus: "",
    paymentStatus: 1,
    paymentComment: "",
    commentVisibility: 1,
    attachInvoice: 1,
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

  jQuery(".order-payment-check").each(function () {
    jQuery(this).prop("checked", false);
  });

  jQuery("#payAllSelected").attr("disabled", true);
  jQuery("#orders-payment-id").val("");
  jQuery("#orders-payment-orig-amt").val("");
  jQuery("#orders-payment-orig-date").val("");
  jQuery("#orders-payment-date").val(getFormattedDate());
  jQuery("#select-orders-pay-total").text("0.00");
  jQuery("#payAllSelected").removeClass("pbo-over-warning");
};

const displayOrderPaymentInfo = (origNetPayableFlag = false) => {
  // re-calcs totals and displays on screen
  let totalPayments = 0;
  let totalQty = 0;
  for (const [prodId, prodInfo] of Object.entries(
    tasteVenue.paymentOrders.productList
  )) {
    // load vat, comm, and price
    const vatRate = jQuery(`#product-table-row-${prodId}`).data("vatrate");
    const commRate = jQuery(`#product-table-row-${prodId}`).data(
      "commissionrate"
    );
    const price = jQuery(`#product-table-row-${prodId}`).data("price");
    // run loop to get prodQtyTotal
    let prodQtyTotal = 0;
    prodInfo.orderItemList.forEach((orderItem) => {
      prodQtyTotal += orderItem.orderQty;
    });
    // call calc net payable
    let prodTotal = calc_net_payable(
      price,
      prodQtyTotal,
      commRate,
      vatRate,
      prodId
    );
    if (origNetPayableFlag) {
      tasteVenue.paymentOrders.productList[prodId].origNetPayable = prodTotal;
    }
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
  jQuery("#orders-payment-comment").text(
    tasteVenue.paymentOrders.paymentComment
  );
  setOrdersPaymentStatusRadio(tasteVenue.paymentOrders.paymentStatus);
  setOrdersCommentsVisibleCheck(tasteVenue.paymentOrders.commentVisibility);
  setOrdersAttachInvCheck(tasteVenue.paymentOrders.attachInvoice);
  buildOrdersPaymentTableRows();
};

const calc_net_payable = (price, qty, commRate, vatRate, prodId = 0) => {
  // due to floating point calc errors in js, convert all to whole numbers
  const priceBig = new Big(price);
  const revenueBig = qty ? priceBig.times(qty) : new Big(0);
  const commBig = commRate ? revenueBig.times(commRate).div(100) : new Big(0);
  const vatBig = vatRate ? commBig.times(vatRate).div(100) : new Big(0);
  // console.log("----------");
  // console.log("priceBig: ", priceBig.toString());
  // console.log("revenueBig: ", revenueBig.toString());
  // console.log("commBig: ", commBig.toString());
  // console.log("vatBig: ", vatBig.toString());
  const revenue = revenueBig.round(2);
  const commission = commBig.round(2);
  const vat = vatBig.round(2);
  // console.log("revenue: ", revenue.toString());
  // console.log("commission: ", commission.toString());
  // console.log("vat: ", vat.toString());
  let payable = revenueBig.minus(commission).minus(vat).round(2);
  // console.log("payable: ", payable.toString());
  // console.log("payable float: ", parseFloat(payable.toString()));
  // console.log("----------");
  payable = parseFloat(payable.toString());

  // check for a Balance Due Threshold to account for rounding
  // if | Balance Due - payable | < threshold
  // just set payable = to Balance Due.
  if (prodId) {
    let balanceDue = jQuery(`#product-table-row-${prodId}`).data("balancedue");
    balanceDue = Math.round(balanceDue * 100) / 100;
    if (Math.abs(balanceDue - payable) <= TASTE_PBO_NET_PAYABLE_THRESHOLD) {
      payable = balanceDue;
    }
  }

  return payable;
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
      let respObj = JSON.parse(responseText);
      if (respObj.error) {
        console.log(respObj);
        tasteCloseMsg();
        alert("error in redeem Voucher ajax code");
      } else {
        orderList.map((orderInfo) => {
          if (redeemFlg) {
            // jQuery("#td-btn-order-id-" + orderInfo.orderId).html(
            // 	'<button	class="btn btn-info order-unredeem-btn">Unredeem</button>'
            // );
            jQuery("#td-check-order-id-" + orderInfo.orderId).html("");
          } else {
            // jQuery("#td-btn-order-id-" + orderInfo.orderId).html(
            // 	'<button	class="btn btn-success order-redeem-btn">Redeem</button>'
            // );
            jQuery("#td-check-order-id-" + orderInfo.orderId).html(
              '<input type="checkbox" class="order-redeem-check">'
            );
          }
        });

        // update payment classes in case Payment Mode is toggled
        const orderItemIdList = orderList.map((orderInfo) => {
          return orderInfo.orderItemId;
        });

        let origOrderItemStatus = TASTE_ORDER_STATUS_NOT_PAID_UNREDEEMED;
        let orderItemStatus = TASTE_ORDER_STATUS_NOT_PAID_REDEEMED;

        if (!redeemFlg) {
          origOrderItemStatus = TASTE_ORDER_STATUS_NOT_PAID_REDEEMED;
          orderItemStatus = TASTE_ORDER_STATUS_NOT_PAID_UNREDEEMED;
        }

        tasteUpdatePaidOrderRows(
          orderItemIdList,
          origOrderItemStatus,
          orderItemStatus
        );

        respObj.emails.map((emailInfo) => {
          jQuery("#email-display-" + emailInfo.orderId).html(emailInfo.email);
        });

        updateOfferCalcs(respObj, productId);
        updateVenueCalcs(respObj, false);
        tasteLoadVoucherPaymentButtons();

        jQuery("#checkbox-all").prop("checked", false);
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
  let curProdInfo, productInfo;
  let postProductList = {};
  let postTotalAmount = 0;

  if (ordersFlag) {
    postProductList = Object.entries(
      tasteVenue.paymentOrders.productList
    ).filter((prod) => {
      return prod[1].orderQty;
    });

    productInfo = tasteGetProdListInfo(postProductList);
    curProdInfo = tasteGetProductInfo();
    postTotalAmount = tasteVenue.paymentOrders.totalNetPayable;
  } else {
    productInfo = curProdInfo = tasteGetProductInfo();
    postTotalAmount = parseFloat(paymentData.get("payment-amt"));
    const prodId = Object.keys(productInfo)[0];
    postProductList = [
      [
        prodId,
        {
          netPayable: postTotalAmount,
          orderQty: 0,
          orderItemList: [],
        },
      ],
    ];
  }

  const paymentId = paymentData.get("payment-id");
  let allPaymentCntForPayId,
    paymentOrigProdAmts = {};

  if (paymentId) {
    allPaymentCntForPayId = jQuery(`.all-pay-${paymentId}`).length;
    jQuery(`.all-pay-${paymentId}`).each((ndx, row) => {
      const $payRow = jQuery(row);
      const rowProdId = $payRow.data("prodid");
      const rowProdAmt = $payRow.data("prodamount");
      // get the balance due for that product
      const balanceDue = jQuery(`#product-table-row-${rowProdId}`).data(
        "balancedue"
      );
      const totalPaid = jQuery(`#product-table-row-${rowProdId}`).data(
        "paidamount"
      );
      paymentOrigProdAmts[rowProdId] = {
        amount: rowProdAmt,
        balancedue: balanceDue,
        totalpaid: totalPaid,
      };
    });
  } else {
    allPaymentCntForPayId = 0;
  }

  const productId = Object.keys(curProdInfo)[0];
  const editMode = paymentId;
  let venueInfo = tasteGetVenueInfo();
  let paymentInfo = {
    id: paymentId,
    amount: financial(postTotalAmount),
    payment_orig_amt: paymentData.get("payment-orig-amt"),
    payment_orig_date: paymentData.get("payment-orig-date"),
    payment_orig_prods: JSON.stringify(paymentOrigProdAmts),
    status: paymentData.get("payment-status"),
    timestamp: paymentData.get("payment-date"),
    comment: paymentData.get("payment-comment"),
    comment_visible_venues: paymentData.has("payment-comment-visibility")
      ? 1
      : 0,
    attach_vat_invoice: paymentData.has("payment-invoice-attachment") ? 1 : 0,
    delete_mode: deleteMode,
    all_payment_cnt: paymentData.get("allpaymentcnt"),
    all_payment_id_cnt: allPaymentCntForPayId,
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
      cur_prod_info: curProdInfo,
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

        tasteVenue.paymentOrders.PBOMode = "insert";
        updateVenueCalcs(respObj, true);
        if (respObj.updateCurrentProd) {
          jQuery(".total-payments-display").html(respObj.totalPaid);
          jQuery("#balance-due-display").html(respObj.balanceDue);
          jQuery(`#balance-due-display-${productId}`).html(
            respObj.balanceDue.split(" ")[1]
          );

          jQuery("#hidden-payment-values").html(respObj.hiddenPaymentValues);
          const prodCntDisp = respObj.prodPaymentCnt
            ? `Transaction Items (${respObj.prodPaymentCnt} Rows)`
            : "No Transactions Found";

          jQuery("#prod-transactions-cnt-display").html(prodCntDisp);
        }

        // update all products in the product display table

        let orderItemStatus, origOrderItemStatus;
        /**
         *  0- paid
         * 	1- not paid, redeemed
         * 	2- not paid, unredeemed, not expired
         * 	3- not paid, unredeemed, expired
         *
         * NAMED CONSTANTS AT TOP OF FILE
         *
         *  NOTE: Only 1 and 2 are possible in this routine,
         * 				but other routines may use all 4
         */
        jQuery(`.all-pay-${paymentInfo.id}`).length &&
          jQuery(`.all-pay-${paymentInfo.id}`).remove();
        if ("UPDATE" === respObj.editMode) {
          origOrderItemStatus = TASTE_ORDER_STATUS_PAID;
          orderItemStatus = TASTE_ORDER_STATUS_PAID;
          respObj.updateCurrentProd &&
            false &&
            jQuery(`#pay-${paymentInfo.id}`).replaceWith(respObj.paymentLine);
          jQuery("#all-payment-lines").append(respObj.allPaymentLine);
        } else if ("INSERT" === respObj.editMode) {
          origOrderItemStatus = TASTE_ORDER_STATUS_NOT_PAID_REDEEMED;
          orderItemStatus = TASTE_ORDER_STATUS_PAID;
          respObj.updateCurrentProd &&
            false &&
            jQuery("#payment-lines").append(respObj.paymentLine);
          jQuery("#all-payment-lines").append(respObj.allPaymentLine);
        } else {
          origOrderItemStatus = TASTE_ORDER_STATUS_PAID;
          orderItemStatus = TASTE_ORDER_STATUS_NOT_PAID_REDEEMED;
          //jQuery(`#pay-${paymentInfo.id}`).remove();
        }

        jQuery("#all-payments-cnt-disp").html(respObj.allPaymentCnt);
        jQuery("#all-payments-table").length &&
          jQuery("#all-payments-table").data(
            "allpaymentcnt",
            respObj.allPaymentCnt
          );

        jQuery("#audit-payment-table").length &&
          false &&
          jQuery("#audit-payment-table").data(
            "paymentcnt",
            respObj.prodPaymentCnt
          );

        respObj.updateCurrentProd &&
          false &&
          tasteUpdatePaidOrderRows(
            respObj.curProdOrdList,
            origOrderItemStatus,
            orderItemStatus
          );

        tasteUpdateProductRows(respObj.productInfo);
        tasteLoadPBOButtons();
        if (editMode) {
          clearOrdersForPayment();
        } else {
          buildPaymentOrders();
          //tasteLoadInvoiceButtons();
        }
        jQuery("#paySelectedModal").modal("hide");
        jQuery("#payAllSelected").html("Preview Payment");
        jQuery("#paySelectedModalLabel").html("Add Payment");
        jQuery("#orders-payment-submit").html("Make payment");
        tasteCloseMsg();
        if (jQuery("#taste-product-id").length) {
          // need to rerun the load vouchers routine as easiest approach to
          // reset the order statuses of the currently displayed product
          const prodId = jQuery("#taste-product-id").val();
          const multiplier = jQuery("#taste-product-multiplier").val();
          const cutoffDate = jQuery("#venue_cutoff_date").val();
          tasteLoadVouchers(prodId, multiplier, cutoffDate, false);
        }
      }

      if (jQuery(".edit-pbo-btn").length) {
        jQuery(".edit-pbo-btn").removeClass("fa-disabled");
        jQuery(".delete-pbo-btn").removeClass("fa-disabled");
      }
      jQuery("#historical-pbo-btn").removeClass("fa-disabled");
      jQuery(".delete-pbo-mode").hide();
      jQuery(".add-edit-pbo-mode").show();
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

const tasteEditPBO = (paymentId) => {
  let modalMsg = "Setting Up Edit Mode...";
  tasteDispMsg(modalMsg);
  allProductInfo = tasteGetAllProductInfo();
  jQuery.ajax({
    url: tasteVenue.ajaxurl,
    type: "POST",
    datatype: "json",
    data: {
      action: "retrieve_payment_json",
      security: tasteVenue.security,
      product_info: allProductInfo,
      payment_id: paymentId,
    },
    success: function (responseJson) {
      tasteCloseMsg();
      const paymentOrderInfo = JSON.parse(responseJson);

      tasteVenue.paymentOrders.editPaymentId = paymentId;
      tasteVenue.paymentOrders.PBOMode = "edit";
      tasteVenue.paymentOrders.editOrigPayDate =
        paymentOrderInfo.editOrigPayDate;
      tasteVenue.paymentOrders.totalNetPayable =
        paymentOrderInfo.totalNetPayable;
      tasteVenue.paymentOrders.totalQty = paymentOrderInfo.totalQty;
      tasteVenue.paymentOrders.editOrigPayStatus =
        paymentOrderInfo.editOrigPayStatus;
      tasteVenue.paymentOrders.paymentStatus =
        paymentOrderInfo.editOrigPayStatus;
      tasteVenue.paymentOrders.paymentComment =
        paymentOrderInfo.editOrigPayComment;
      tasteVenue.paymentOrders.commentVisibility =
        paymentOrderInfo.editCommentVisibleVenues;
      tasteVenue.paymentOrders.attachInvoice =
        paymentOrderInfo.editAttachVatInvoice;
      const editProdIds = Object.keys(paymentOrderInfo.productList);
      const venueProdIds = Object.keys(tasteVenue.paymentOrders.productList);
      venueProdIds.forEach((venueProdId) => {
        if (editProdIds.includes(venueProdId)) {
          tasteVenue.paymentOrders.productList[venueProdId] =
            paymentOrderInfo.productList[venueProdId];
        }
      });

      displayOrderPaymentInfo(true);

      // if status = 2, visible = 0, and attach inv = 0, then
      // it is an Historical PBO and those fields should be disabled
      if (
        2 == paymentOrderInfo.editOrigPayStatus &&
        !paymentOrderInfo.editAttachVatInvoice &&
        !paymentOrderInfo.editCommentVisibleVenues
      ) {
        jQuery("#orders-payment-comment-visible-checkbox").prop(
          "disabled",
          true
        );
        jQuery("#orders-payment-attach-invoice-checkbox").prop(
          "disabled",
          true
        );
        jQuery(".payment-status-radio").prop("disabled", true);
      }

      jQuery("#payAllSelected").html(`Preview Edit #${paymentId}`);
      jQuery("#paySelectedModalLabel").html(`Edit #${paymentId}`);
      jQuery("#orders-payment-id").val(paymentId);
      jQuery("#orders-payment-orig-amt").val(paymentOrderInfo.totalNetPayable);
      jQuery("#orders-payment-orig-date").val(paymentOrderInfo.editOrigPayDate);
      jQuery("#orders-payment-orig-status").val(
        paymentOrderInfo.editOrigPayStatus
      );
      jQuery("#orders-payment-date").val(paymentOrderInfo.editOrigPayDate);
      jQuery("#orders-payment-submit").html("Update payment");
      if (jQuery(".edit-pbo-btn").length) {
        jQuery(".edit-pbo-btn").addClass("fa-disabled");
        jQuery(".delete-pbo-btn").addClass("fa-disabled");
      }
      jQuery("#historical-pbo-btn").addClass("fa-disabled");

      if (jQuery("#taste-product-id").length) {
        // need to rerun the load vouchers routine as easiest approach to
        // reset the order statuses of the currently displayed product
        const prodId = jQuery("#taste-product-id").val();
        const multiplier = jQuery("#taste-product-multiplier").val();
        const cutoffDate = jQuery("#venue_cutoff_date").val();
        tasteLoadVouchers(prodId, multiplier, cutoffDate, false);
      }
      jQuery(".delete-pbo-mode").hide();
      jQuery(".add-edit-pbo-mode").show();
    },
    error: function (xhr, status, errorThrown) {
      tasteCloseMsg();
      console.log(errorThrown);
      alert(
        "Error setting up Payment Edit Mode. Your login may have timed out. Please refresh the page and try again."
      );
    },
  });
};

const tasteDeletePBO = (paymentId) => {
  let modalMsg = "Setting Up Delete Mode...";
  tasteDispMsg(modalMsg);
  allProductInfo = tasteGetAllProductInfo();
  jQuery.ajax({
    url: tasteVenue.ajaxurl,
    type: "POST",
    datatype: "json",
    data: {
      action: "retrieve_payment_json",
      security: tasteVenue.security,
      product_info: allProductInfo,
      payment_id: paymentId,
    },
    success: function (responseJson) {
      tasteCloseMsg();
      // console.log(responseJson);
      const paymentOrderInfo = JSON.parse(responseJson);

      tasteVenue.paymentOrders.editPaymentId = paymentId;
      tasteVenue.paymentOrders.PBOMode = "delete";
      tasteVenue.paymentOrders.editOrigPayDate =
        paymentOrderInfo.editOrigPayDate;
      tasteVenue.paymentOrders.totalNetPayable =
        paymentOrderInfo.totalNetPayable;
      tasteVenue.paymentOrders.totalQty = paymentOrderInfo.totalQty;
      tasteVenue.paymentOrders.editOrigPayStatus =
        paymentOrderInfo.editOrigPayStatus;
      tasteVenue.paymentOrders.paymentStatus =
        paymentOrderInfo.editOrigPayStatus;
      tasteVenue.paymentOrders.paymentComment =
        paymentOrderInfo.editOrigPayComment;
      tasteVenue.paymentOrders.commentVisibility =
        paymentOrderInfo.editCommentVisibleVenues;
      tasteVenue.paymentOrders.attachInvoice =
        paymentOrderInfo.editAttachVatInvoice;
      const delProdIds = Object.keys(paymentOrderInfo.productList);
      const venueProdIds = Object.keys(tasteVenue.paymentOrders.productList);
      venueProdIds.forEach((venueProdId) => {
        if (delProdIds.includes(venueProdId)) {
          tasteVenue.paymentOrders.productList[venueProdId] =
            paymentOrderInfo.productList[venueProdId];
        }
      });

      displayOrderPaymentInfo();
      jQuery("#payAllSelected").html(`Preview Delete #${paymentId}`);
      jQuery("#paySelectedModalLabel").html(`Delete #${paymentId}`);
      jQuery("#orders-payment-id").val(paymentId);
      jQuery("#orders-payment-orig-amt").val(paymentOrderInfo.totalNetPayable);
      jQuery("#orders-payment-orig-date").val(paymentOrderInfo.editOrigPayDate);
      jQuery("#orders-payment-orig-status").val(
        paymentOrderInfo.editOrigPayStatus
      );
      jQuery("#orders-payment-date").val(paymentOrderInfo.editOrigPayDate);
      jQuery("#orders-payment-submit").html("Update payment");
      if (jQuery(".edit-pbo-btn").length) {
        jQuery(".edit-pbo-btn").addClass("fa-disabled");
        jQuery(".delete-pbo-btn").addClass("fa-disabled");
      }
      jQuery("#historical-pbo-btn").addClass("fa-disabled");
      if (jQuery("#taste-product-id").length) {
        // need to rerun the load vouchers routine as easiest approach to
        // reset the order statuses of the currently displayed product
        const prodId = jQuery("#taste-product-id").val();
        const multiplier = jQuery("#taste-product-multiplier").val();
        const cutoffDate = jQuery("#venue_cutoff_date").val();
        tasteLoadVouchers(prodId, multiplier, cutoffDate, false);
      }

      jQuery(".add-edit-pbo-mode").hide();
      jQuery(".delete-pbo-mode").show();
      jQuery("#paySelectedModal").modal();
    },
    error: function (xhr, status, errorThrown) {
      tasteCloseMsg();
      console.log(errorThrown);
      alert(
        "Error setting up Payment Edit Mode. Your login may have timed out. Please refresh the page and try again."
      );
    },
  });
};

const tasteHistoricalPBO = (venueId) => {
  let modalMsg = "Checking Historical Payment Info...";
  tasteDispMsg(modalMsg);
  allProductInfo = tasteGetAllProductInfo();
  jQuery.ajax({
    url: tasteVenue.ajaxurl,
    type: "POST",
    datatype: "json",
    data: {
      action: "retrieve_historical_payments_json",
      security: tasteVenue.security,
      product_info: allProductInfo,
      venue_id: venueId,
    },
    success: function (responseJson) {
      tasteCloseMsg();
      const paymentOrderInfo = JSON.parse(responseJson);

      // if no products list, just reset
      if (!Object.keys(paymentOrderInfo.productList).length) {
        jQuery("#historical-pbo-btn").removeClass("fa-disabled");
        if (jQuery(".edit-pbo-btn").length) {
          jQuery(".edit-pbo-btn").removeClass("fa-disabled");
          jQuery(".delete-pbo-btn").removeClass("fa-disabled");
        }
        return;
      }

      tasteVenue.paymentOrders.editPaymentId = 0;
      tasteVenue.paymentOrders.PBOMode = "historical";
      tasteVenue.paymentOrders.editOrigPayDate = "";
      tasteVenue.paymentOrders.totalNetPayable =
        paymentOrderInfo.totalNetPayable;
      tasteVenue.paymentOrders.totalQty = paymentOrderInfo.totalQty;
      tasteVenue.paymentOrders.editOrigPayStatus = "";
      tasteVenue.paymentOrders.paymentStatus = paymentOrderInfo.payStatus;
      tasteVenue.paymentOrders.paymentComment = "";
      tasteVenue.paymentOrders.commentVisibility = 0;
      tasteVenue.paymentOrders.attachInvoice = 0;
      const editProdIds = Object.keys(paymentOrderInfo.productList);
      const venueProdIds = Object.keys(tasteVenue.paymentOrders.productList);
      venueProdIds.forEach((venueProdId) => {
        if (editProdIds.includes(venueProdId)) {
          tasteVenue.paymentOrders.productList[venueProdId] =
            paymentOrderInfo.productList[venueProdId];
        }
      });

      displayOrderPaymentInfo();
      jQuery("#orders-payment-comment-visible-checkbox").prop("disabled", true);
      jQuery("#orders-payment-attach-invoice-checkbox").prop("disabled", true);
      jQuery(".payment-status-radio").prop("disabled", true);

      let dateStr = getFormattedDate();
      jQuery("#payAllSelected").html(`Preview Payment`);
      jQuery("#paySelectedModalLabel").html("Add Payment");
      jQuery("#orders-payment-id").val("");
      jQuery("#orders-payment-orig-amt").val(0);
      jQuery("#orders-payment-orig-date").val(dateStr);
      jQuery("#orders-payment-orig-status").val(0);
      jQuery("#orders-payment-date").val(dateStr);
      jQuery("#orders-payment-submit").html("Make Historical PBO");
      if (jQuery(".edit-pbo-btn").length) {
        jQuery(".edit-pbo-btn").addClass("fa-disabled");
        jQuery(".delete-pbo-btn").addClass("fa-disabled");
      }
      jQuery("#historical-pbo-btn").addClass("fa-disabled");
      if (jQuery("#taste-product-id").length) {
        // need to rerun the load vouchers routine as easiest approach to
        // reset the order statuses of the currently displayed product
        const prodId = jQuery("#taste-product-id").val();
        const multiplier = jQuery("#taste-product-multiplier").val();
        const cutoffDate = jQuery("#venue_cutoff_date").val();
        tasteLoadVouchers(prodId, multiplier, cutoffDate, false);
      }
      jQuery(".delete-pbo-mode").hide();
      jQuery(".add-edit-pbo-mode").show();
    },
    error: function (xhr, status, errorThrown) {
      tasteCloseMsg();
      console.log(errorThrown);
      alert(
        "Error setting up Historical Payments. Your login may have timed out. Please refresh the page and try again."
      );
    },
  });
};

const setOrdersPaymentStatusRadio = (payStatus) => {
  jQuery(".payment-status-radio").prop("checked", false);
  let radioId;
  switch (payStatus) {
    case 1:
      radioId = "#orders-pay-status-paid";
      break;
    case 2:
      radioId = "#orders-pay-status-adj";
      break;
    case 3:
      radioId = "#orders-pay-status-pend";
      break;
    default:
      radioId = "#orders-pay-status-paid";
  }

  jQuery(radioId).prop("checked", true);
};

const setOrdersCommentsVisibleCheck = (visibilityStatus) => {
  let checkVal = visibilityStatus ? true : false;
  jQuery("#orders-payment-comment-visible-checkbox").prop("checked", checkVal);
};

const setOrdersAttachInvCheck = (attachInvoice) => {
  let checkVal = attachInvoice ? true : false;
  jQuery("#orders-payment-attach-invoice-checkbox").prop("checked", checkVal);
};

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
  jQuery("#product-table-row-" + productId).data(
    "balancedue",
    respObj.balanceDue.split(" ")[1]
  );

  jQuery("#hidden-values").html(respObj.hiddenValues);
};

const updateVenueCalcs = (respObj, paymentOnly = false) => {
  jQuery("#balance-due-total").html(respObj.sumBalanceDue);
  jQuery("#balance-due-table-total").html(respObj.sumBalanceDue.split(" ")[1]);
  jQuery(".paid-amount-total").html(respObj.sumTotalPaid);
  respObj.sumHiddenPaymentValues &&
    jQuery("#summary-hidden-payment-values").html(
      respObj.sumHiddenPaymentValues
    );
  respObj.hiddenPaymentValues &&
    jQuery("#hidden-payment-values").html(respObj.hiddenPaymentValues);

  if (paymentOnly) {
    return;
  }
  jQuery("#gr-value-total").html(respObj.sumGrValue);
  jQuery("#vouchers-total").html(respObj.sumRedeemedQty);
  jQuery("#served-total").html(respObj.sumNumServed);
  jQuery("#net-payable-total").html(respObj.sumNetPayable);
  respObj.sumHiddenValues &&
    jQuery("#summary-hidden-values").html(respObj.sumHiddenValues);
  jQuery("#gr-value-table-total").html(respObj.sumGrValue.split(" ")[1]);
  jQuery("#net-payable-table-total").html(respObj.sumNetPayable.split(" ")[1]);
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
    balance_due: jQuery("#taste-balance-due").val(),
    title: jQuery("#taste-product-title").val(),
  };

  return productInfo;
};

const tasteGetProdListInfo = (prodList) => {
  let prodListInfo = {};

  prodList.forEach((prodInfo) => {
    const prodId = prodInfo[0];
    const $productRow = jQuery(`#product-table-row-${prodId}`);
    prodListInfo[prodId] = {
      vat_value: $productRow.data("vatrate"),
      commission_value: $productRow.data("commissionrate"),
      price: $productRow.data("price"),
      total_paid: $productRow.data("paidamount"),
      balance_due: $productRow.data("balancedue"),
    };
  });

  return prodListInfo;
};

const tasteGetAllProductInfo = () => {
  let productAllProductInfo = {};

  jQuery(".product-info-row").each((ndx, prodRow) => {
    const $productRow = jQuery(prodRow);
    const prodId = $productRow.data("productid");
    productAllProductInfo[prodId] = {
      vat_value: $productRow.data("vatrate"),
      commission_value: $productRow.data("commissionrate"),
      price: $productRow.data("price"),
      total_paid: $productRow.data("paidamount"),
      balance_due: $productRow.data("balancedue"),
    };
  });

  return productAllProductInfo;
};

const tasteUpdateProductRows = (prodList) => {
  for (const [prodId, prodInfo] of Object.entries(prodList)) {
    let $productRow = jQuery(`#product-table-row-${prodId}`);
    $productRow.data("paidamount", prodInfo["total_paid"]);
    $productRow.data("balancedue", prodInfo["balance_due"]);
    jQuery(`#balance-due-display-${prodId}`).html(
      financial(prodInfo["balance_due"])
    );
    jQuery(`#selected-pay-amt-${prodId}`).text("0.00");
  }
};

const tasteUpdatePaidOrderRows = (
  curProdOrdItemList,
  origOrderItemStatus,
  orderItemStatus
) => {
  const statusClasses = {};
  statusClasses[TASTE_ORDER_STATUS_PAID] = "or-display-paid";
  statusClasses[TASTE_ORDER_STATUS_NOT_PAID_REDEEMED] = "or-display-pay-due";
  statusClasses[TASTE_ORDER_STATUS_NOT_PAID_UNREDEEMED] =
    "or-display-unredeemed";
  statusClasses[TASTE_ORDER_STATUS_NOT_PAID_EXPIRED] = "or-display-expired";

  curProdOrdItemList.forEach((orderItemId) => {
    const classToRemove = statusClasses[origOrderItemStatus];
    const classToAdd = statusClasses[orderItemStatus];
    tasteUpdateOrderRowStatusClass(orderItemId, classToRemove, classToAdd);
  });
};

const tasteUpdateOrderRowStatusClass = (
  orderItemId,
  classToRemove,
  classToAdd
) => {
  jQuery(`#order-table-row-${orderItemId}`).removeClass(classToRemove);
  jQuery(`#order-table-row-${orderItemId}`).addClass(classToAdd);
};

const tasteGetVenueInfo = () => {
  let venueInfo = {};
  venueInfo.venue_id = jQuery("#hidden_venue_id").val();
  venueInfo.venue_name = jQuery("#taste-venue-name").val();
  venueInfo.venue_addr1 = jQuery("#taste-venue-addr").val();
  venueInfo.venue_addr2 = jQuery("#taste-venue-addr2").val();
  venueInfo.venue_city = jQuery("#taste-venue-city").val();
  venueInfo.venue_postcode = jQuery("#taste-venue-postcode").val();
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
      jQuery("tr.or-display-pay-due .order-payment-check").each(function () {
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
              return payItem.orderItemId !== orderItemId;
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
        let paymentId = $invBtn.data("paymentid");
        let urlGetString = `?pay_id=${paymentId}`;
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
  // jQuery("#orders-payment-submit").length &&
  //   jQuery("#orders-payment-submit")
  jQuery("#orders-payment-add-form").length &&
    jQuery("#orders-payment-add-form")
      .off("submit")
      .submit(function (e) {
        e.preventDefault();
        // 3 inputs may have been set to disabled during Historical PBO
        jQuery("#orders-payment-comment-visible-checkbox").prop(
          "disabled",
          false
        );
        jQuery("#orders-payment-attach-invoice-checkbox").prop(
          "disabled",
          false
        );
        jQuery(".payment-status-radio").prop("disabled", false);
        const $paymentForm = jQuery(this);
        const $modal = $paymentForm.closest(".modal");
        // const formId = $submitBtn.attr("form");
        // const $paymentForm = jQuery(`#${formId}`);
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

  // load the Clear button for this modal
  jQuery("#orders-payment-clear").length &&
    jQuery("#orders-payment-clear")
      .off("click")
      .click(function (e) {
        e.preventDefault();
        // 3 inputs may have been set to disabled during Historical PBO
        jQuery("#orders-payment-comment-visible-checkbox").prop(
          "disabled",
          false
        );
        jQuery("#orders-payment-attach-invoice-checkbox").prop(
          "disabled",
          false
        );
        jQuery(".payment-status-radio").prop("disabled", false);
        const editId = tasteVenue.paymentOrders.editPaymentId;
        if (editId) {
          jQuery("#response-modal-msg").html(
            "This will exit you out of Edit Mode."
          );
        } else {
          jQuery("#response-modal-msg").html(
            "This will uncheck all Orders currently selected."
          );
        }
        jQuery("#responseModal").modal();
        jQuery("#response-modal-submit-yes").length &&
          jQuery("#response-modal-submit-yes")
            .off("click")
            .click(function (ev) {
              ev.preventDefault();
              clearOrdersForPayment();
              jQuery("#paySelectedModal").modal("hide");
              jQuery("#payAllSelected").html("Preview Payment");
              jQuery("#paySelectedModalLabel").html("Add Payment");
              jQuery("#orders-payment-submit").html("Make payment");
              if (jQuery(".edit-pbo-btn").length) {
                jQuery(".edit-pbo-btn").removeClass("fa-disabled");
                jQuery(".delete-pbo-btn").removeClass("fa-disabled");
              }
              jQuery("#historical-pbo-btn").removeClass("fa-disabled");
              if (editId && jQuery("#taste-product-id").length) {
                // need to rerun the load vouchers routine as easiest approach to
                // reset the order statuses of the currently displayed product
                const prodId = jQuery("#taste-product-id").val();
                const multiplier = jQuery("#taste-product-multiplier").val();
                const cutoffDate = jQuery("#venue_cutoff_date").val();
                tasteLoadVouchers(prodId, multiplier, cutoffDate, false);
              }
            });
      });

  // load the Delete button for this modal
  jQuery("#delete-pbo-btn").length &&
    jQuery("#delete-pbo-btn")
      .off("click")
      .click(function (e) {
        e.preventDefault();
        const $deleteBtn = jQuery(this);
        const $modal = $deleteBtn.closest(".modal");
        const formId = $deleteBtn.attr("form");
        const $paymentForm = jQuery(`#${formId}`);
        let paymentData = new FormData($paymentForm[0]);
        const deleteMode = true;
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

  // load the Delete Cancel button for this modal
  jQuery("#delete-pbo-cancel-btn").length &&
    jQuery("#delete-pbo-cancel-btn")
      .off("click")
      .click(function (e) {
        e.preventDefault();
        clearOrdersForPayment();
        jQuery("#paySelectedModal").modal("hide");
        jQuery("#payAllSelected").html("Preview Payment");
        jQuery("#paySelectedModalLabel").html("Add Payment");
        jQuery("#orders-payment-submit").html("Make payment");
        if (jQuery(".edit-pbo-btn").length) {
          jQuery(".edit-pbo-btn").removeClass("fa-disabled");
          jQuery(".delete-pbo-btn").removeClass("fa-disabled");
        }
        jQuery("#historical-pbo-btn").removeClass("fa-disabled");
        if (jQuery("#taste-product-id").length) {
          // need to rerun the load vouchers routine as easiest approach to
          // reset the order statuses of the currently displayed product
          const prodId = jQuery("#taste-product-id").val();
          const multiplier = jQuery("#taste-product-multiplier").val();
          const cutoffDate = jQuery("#venue_cutoff_date").val();
          tasteLoadVouchers(prodId, multiplier, cutoffDate, false);
        }
      });
};

const clearOrdersForPayment = () => {
  buildPaymentOrders();
  displayOrderPaymentInfo();
  jQuery("tr.or-display-pay-due .order-payment-check").length &&
    jQuery("tr.or-display-pay-due .order-payment-check").each(function () {
      jQuery(this).prop("checked", false);
    });
  jQuery("#checkbox-payment-all").length &&
    jQuery("#checkbox-payment-all").prop("checked", false);

  if (jQuery(".edit-pbo-btn").length) {
    jQuery(".edit-pbo-btn").removeClass("fa-disabled");
    jQuery(".delete-pbo-btn").removeClass("fa-disabled");
  }
  jQuery("#historical-pbo-btn").removeClass("fa-disabled");
};

const buildOrdersPaymentTableRows = () => {
  let tbodyRows = "";
  let overFlag = false;
  // for (const [prodId, prodInfo] of Object.entries(
  //   tasteVenue.paymentOrders.productList
  // )) {
  jQuery(".product-select-for-payments").each(function () {
    const prodId = $(this).data("prod-id");
    const prodInfo = tasteVenue.paymentOrders.productList[prodId];
    const payAmt = parseFloat(prodInfo.netPayable);
    if (!payAmt) {
      return true;
    }
    // const insertMode = "insert" === tasteVenue.paymentOrders.PBOMode;
    let balanceDue = jQuery(`#product-table-row-${prodId}`).data("balancedue");
    balanceDue = Math.round(balanceDue * 100) / 100;
    let trClass = "";
    let resultBalance;
    let balanceCheck = false;

    switch (tasteVenue.paymentOrders.PBOMode) {
      case "delete":
        resultBalance = balanceDue + payAmt;
        break;
      case "edit":
        const startingAmt = prodInfo.origNetPayable;
        resultBalance = balanceDue - (payAmt - startingAmt);
        balanceCheck = true;
        break;
      case "insert":
        resultBalance = balanceDue - payAmt;
        balanceCheck = true;
        break;
      case "historical":
        resultBalance = balanceDue;
    }

    if (0 > resultBalance && balanceCheck) {
      trClass = " class='pbo-over-warning' ";
      overFlag = true;
    }

    tbodyRows += `
			<tr ${trClass}>
				<td>${prodId}</td>
				<td>${prodInfo.orderQty}</td>
				<td>${financial(prodInfo.netPayable)}</td>
				<td>${financial(balanceDue)}</td>
				<td>${financial(resultBalance)}</td>
			</tr>
		`;
  });
  tfootRow = `
		<tr>
			<td>Totals:</td>
			<td>${tasteVenue.paymentOrders.totalQty}</td>
			<td>${financial(tasteVenue.paymentOrders.totalNetPayable)}</td>
      <td colspan="2">
      </td>
		</tr>
	`;

  if (overFlag && !jQuery("#payAllSelected").prop("disabled")) {
    jQuery("#payAllSelected").addClass("pbo-over-warning");
  } else {
    jQuery("#payAllSelected").removeClass("pbo-over-warning");
  }

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
        tasteLoadVouchers(prodId, multiplier, cutoffDate, makePaymentsBelow);
      }
    });

  tasteLoadPBOButtons();
};

const tasteLoadPBOButtons = () => {
  jQuery(".edit-pbo-btn")
    .off("click")
    .click(function (e) {
      e.preventDefault();
      let paymentId = jQuery(this).data("payment-id");
      tasteEditPBO(paymentId);
    });

  jQuery(".delete-pbo-btn")
    .off("click")
    .click(function (e) {
      e.preventDefault();
      let paymentId = jQuery(this).data("payment-id");
      tasteDeletePBO(paymentId);
    });

  jQuery("#historical-pbo-btn")
    .off("click")
    .click(function (e) {
      e.preventDefault();
      let venueId = jQuery(this).data("venue-id");
      tasteHistoricalPBO(venueId);
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

const getFormattedDate = () => {
  let date = new Date();
  return formatDate(date);
};

const formatDate = (dt) => {
  // dt must be an instance of Date class

  let year = dt.getFullYear();
  let month = dt.getMonth() + 1;
  let dateOfMonth = dt.getDate();
  month = month > 9 ? month : "0" + month;
  dateOfMonth = dateOfMonth > 9 ? dateOfMonth : "0" + dateOfMonth;
  return `${year}-${month}-${dateOfMonth}`;
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
