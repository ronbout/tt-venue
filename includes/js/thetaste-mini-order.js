/**
 *  thetaste-mini-order-.js
 *
 *  The mini order template is a partial template that will give
 *  a summary of information for an order item and allow
 *  the venue to quickly Redeem the order item.  It is currently used
 *  by the mini-order-page.php, but was written so that it could be
 *  called from anywhere.
 *
 *  Script included in :
 *      mini-order-page.php
 *
 * 	4/14/2022  Ron Boutilier
 *
 */

jQuery(document).ready(function ($) {
  tasteLoadMiniRedeemBtns();
});

const tasteRedeemMiniPageVoucher = (orderItemId) => {
  let modalMsg = "Redeeming Voucher...";
  tasteDispMsg(modalMsg);
  jQuery.ajax({
    url: tasteVenue.ajaxurl,
    type: "POST",
    datatype: "JSON",
    data: {
      action: "redeem_voucher_mini",
      security: tasteVenue.security,
      order_item_id: orderItemId,
    },
    success: function (responseText) {
      tasteCloseMsg();
      let respObj = JSON.parse(responseText);
      if (respObj.error) {
        console.log(respObj);
        alert("error in redeem Voucher code");
      } else {
        console.log(respObj);
        // success, update the Order Item Card
        $statusText = $(`#redeem-status-${orderItemId}`);
        $statusText.text("Redeemed!");
        $statusText.addClass("text-success");
        $statusText.removeClass("text-primary");
        $(`#redeem-btn-${orderItemId}`).hide();
      }
    },
    error: function (xhr, status, errorThrown) {
      tasteCloseMsg();
      console.log(errorThrown);
      alert(
        "Error redeeming voucher. Your login may have timed out. Please refresh the page and try again."
      );
    },
  });
};

const tasteLoadMiniRedeemBtns = () => {
  // this sets up click event for the ajax returned html
  // as well as any other processing required post voucher load
  jQuery(".order-redeem-btn")
    .off("click")
    .click(function (e) {
      e.preventDefault();
      let orderItemId = $(this).data("order-item-id");
      tasteRedeemMiniPageVoucher(orderItemId);
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
