jQuery(document).ready(function () {
	tasteLoadFormSubmit();
	tasteLoadFilterEvents();
	jQuery("#topbutton").length && tasteLoadScrollUp();
	let $datepickers = jQuery(
		"#product-date-start, #product-date-end, #order-date-start, #order-date-end"
	);
	let prodStartDateDefault = jQuery("#product-date-start").val();
	let prodEndDateDefault = jQuery("#product-date-end").val();
	let ordStartDateDefault = jQuery("#order-date-start").val();
	let ordEndDateDefault = jQuery("#order-date-end").val();
	$datepickers.datepicker();
	$datepickers.datepicker("option", {
		showAnim: "slideDown",
		dateFormat: "yy-mm-dd",
	});
	jQuery("#product-date-start").datepicker("setDate", prodStartDateDefault);
	jQuery("#product-date-end").datepicker("setDate", prodEndDateDefault);
	jQuery("#order-date-start").datepicker("setDate", ordStartDateDefault);
	jQuery("#order-date-end").datepicker("setDate", ordEndDateDefault);
});

const tasteLoadFormSubmit = () => {
	jQuery("#load-products")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			let formData = new FormData(jQuery("#audit-filter-form")[0]);
			// for (var pair of formData.entries()) {
			// 	console.log(pair[0] + ", " + pair[1]);
			// }
			let prodFilterData = tasteGetProductFilterData(formData);
			tasteLoadProducts(prodFilterData);
		});
};

const tasteGetProductFilterData = (formData) => {
	let filterData = {};
	// if prod id list, turn off all other filters
	let prodIdList = jQuery("#product-id-list").val();
	if (prodIdList) {
		filterData = {
			prodIdList,
			prodSelectType: "all",
			orderSelectType: "all",
			venueSelectType: "any",
			balanceSelectType: "any",
			recurringProductCheck: 0,
		};
	} else {
		filterData = {
			prodSelectType: formData.get("product-select-type"),
			orderSelectType: formData.get("order-select-type"),
			venueSelectType: formData.get("venue-select-type"),
			balanceSelectType: formData.get("balance-select-type"),
			recurringProductCheck: formData.get("recurring-product-check"),
		};

		if ("year" === filterData.prodSelectType) {
			filterData.prodYear = formData.get("product-year-select");
		} else {
			filterData.prodStartDt = jQuery("#product-date-start").datepicker(
				"getDate"
			);
			filterData.prodEndDt = jQuery("#product-date-end").datepicker("getDate");
		}

		if ("year" === filterData.orderSelectType) {
			filterData.orderYear = formData.get("order-year-select");
		} else {
			filterData.orderStartDt = jQuery("#order-date-start").datepicker(
				"getDate"
			);
			filterData.orderEndDt = jQuery("#order-date-end").datepicker("getDate");
		}

		if ("venue" === filterData.venueSelectType) {
			filterData.venueId = formData.get("venue-id");
		}
	}

	// have to pull all checkboxes for the product columns
	let $customCols = jQuery("[id^=custom-prod-col]:checked");
	filterData.prodCols = jQuery.map($customCols, (col) => {
		$col = jQuery(col);
		return $col.attr("id").replace("custom-prod-col-", "");
	});

	return filterData;
};

const tasteGetOrderColumnData = () => {
	// have to pull all checkboxes for the order columns
	$customCols = jQuery("[id^=custom-order-col]:checked");
	const orderCols = jQuery.map($customCols, (col) => {
		$col = jQuery(col);
		return $col.attr("id").replace("custom-order-col-", "");
	});
	return orderCols;
};

const tasteLoadFilterEvents = () => {
	let $prodYear = jQuery("#product-year-select-container");
	let $prodRange = jQuery("#product-date-range-container");
	jQuery("#product-select-type").change(function () {
		let prodSelectType = jQuery(this).val();
		if ("year" === prodSelectType) {
			$prodRange.hide(300);
			$prodYear.show(300);
		} else if ("range" === prodSelectType) {
			$prodYear.hide();
			$prodRange.show(300);
		} else {
			$prodYear.hide(300);
			$prodRange.hide(300);
		}
	});
	let $ordYear = jQuery("#order-year-select-container");
	let $ordRange = jQuery("#order-date-range-container");
	jQuery("#order-select-type").change(function () {
		let orderSelectType = jQuery(this).val();
		if ("year" === orderSelectType) {
			$ordRange.hide(300);
			$ordYear.show(300);
		} else if ("range" === orderSelectType) {
			$ordYear.hide(300);
			$ordRange.show(300);
		} else {
			$ordYear.hide(300);
			$ordRange.hide(300);
		}
	});
	let $venueSelect = jQuery("#venue-select-container");
	jQuery("#venue-select-type").change(function () {
		let venueSelectType = jQuery(this).val();
		if ("venue" === venueSelectType) {
			$venueSelect.show(300);
		} else {
			$venueSelect.hide(300);
		}
	});
	let $prodArrowSpan = jQuery("#custom-products-columns-arrow");
	let $customProdColumns = jQuery("#custom-products-columns-list-div");
	jQuery("#custom-products-columns-toggle-btn")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			if ($prodArrowSpan.hasClass("glyphicon-menu-down")) {
				$prodArrowSpan
					.removeClass("glyphicon-menu-down")
					.addClass("glyphicon-menu-up");
				$customProdColumns.show(300);
			} else {
				$prodArrowSpan
					.removeClass("glyphicon-menu-up")
					.addClass("glyphicon-menu-down");
				$customProdColumns.hide(300);
			}
		});
	let $ordArrowSpan = jQuery("#custom-orders-columns-arrow");
	let $customOrdColumns = jQuery("#custom-orders-columns-list-div");
	jQuery("#custom-orders-columns-toggle-btn")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			if ($ordArrowSpan.hasClass("glyphicon-menu-down")) {
				$ordArrowSpan
					.removeClass("glyphicon-menu-down")
					.addClass("glyphicon-menu-up");
				$customOrdColumns.show(300);
			} else {
				$ordArrowSpan
					.removeClass("glyphicon-menu-up")
					.addClass("glyphicon-menu-down");
				$customOrdColumns.hide(300);
			}
		});

	let $prodIdEntry = jQuery("#product-id-entry");
	jQuery("#add-product-id-btn")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			let id = $prodIdEntry.val();
			id && addProductIdToList(id.toString());
			$prodIdEntry.val("");
		});
	jQuery("#clear-product-list-btn")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			jQuery("#product-id-list").val("");
		});
};

const addProductIdToList = (id) => {
	$prodListTextArea = jQuery("#product-id-list");
	let prodList = $prodListTextArea.val();
	prodList = prodList ? prodList + ", " + id : id;
	$prodListTextArea.val(prodList);
};

const tasteLoadProducts = (filterData) => {
	let modalMsg = "Loading Products...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "html",
		data: {
			action: "outstanding_load_products",
			security: tasteVenue.security,
			filterData,
		},
		success: function (responseText) {
			tasteCloseMsg();
			// console.log(responseText);
			jQuery("#product-list-div").html(responseText);
			jQuery("#voucher-list-div").html("");
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
				const orderColData = tasteGetOrderColumnData();
				tasteLoadVouchers([prodId], orderColData);
			}
		});

	jQuery("#export-products")
		.unbind("click")
		.click(function (event) {
			let outputFile = "export-products.csv";
			// CSV
			exportTableToCSV.apply(this, [
				jQuery("#out-product-table"),
				outputFile,
				"products",
			]);
		});

	jQuery("#checkbox-all")
		.unbind("click")
		.click(function (e) {
			let checkVal = jQuery(this).prop("checked");
			jQuery(".product-view-check").prop("checked", checkVal);
			checkViewAllDisable();
		});

	jQuery(".product-view-check")
		.unbind("click")
		.click(function (e) {
			checkViewAllDisable();
		});

	jQuery(".product-view-checked-btn")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			let productIdList = [];
			jQuery(".product-view-check:checked").each((ndx, chckbox) => {
				let productId = jQuery(chckbox).data("productid");
				productIdList.push(productId);
			});
			const orderColData = tasteGetOrderColumnData();
			tasteLoadVouchers(productIdList, orderColData);
		});
};

const tasteLoadVouchers = (productIdList, orderColData) => {
	let modalMsg = "Loading Vouchers...";
	tasteDispMsg("<br><br>" + modalMsg, false);
	jQuery.ajax({
		url: tasteVenue.ajaxurl,
		type: "POST",
		datatype: "html",
		data: {
			action: "outstanding_load_vouchers",
			security: tasteVenue.security,
			product_ids: productIdList,
			order_columns: orderColData,
		},
		success: function (responseText) {
			tasteCloseMsg();
			//console.log(responseText);
			jQuery("#voucher-list-div").html(responseText);
			tasteScrollToVouchers();
			tasteLoadOrderCSVButtons();
		},
		error: function (xhr, status, errorThrown) {
			tasteCloseMsg();
			alert("Error loading vouchers: " + errorThrown);
		},
	});
};

const tasteLoadOrderCSVButtons = () => {
	jQuery("#export-orders")
		.unbind("click")
		.click(function (event) {
			let outputFile = "export-orders.csv";
			// CSV
			exportTableToCSV.apply(this, [
				jQuery("#out-order-table"),
				outputFile,
				"orders",
			]);
		});

	jQuery("#export-payments")
		.unbind("click")
		.click(function (event) {
			let outputFile = "export-payments.csv";
			// CSV
			exportTableToCSV.apply(this, [
				jQuery("#audit-payment-table"),
				outputFile,
				"payments",
			]);
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
					scrollTop: $("#audit-filter-container").offset().top,
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

const checkViewAllDisable = () => {
	if (jQuery(".product-view-check:checked").length) {
		jQuery(".product-view-checked-btn").prop("disabled", false);
	} else {
		jQuery(".product-view-checked-btn").prop("disabled", true);
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

/**
 * code for exporting table to csv
 */
function exportTableToCSV($table, filename, tableType) {
	let $headers = $table.find("tr:has(th)"),
		$rows = $table.find("tr:has(td)"),
		// Temporary delimiter characters unlikely to be typed by keyboard
		// This is to avoid accidentally splitting the actual contents
		tmpColDelim = String.fromCharCode(11), // vertical tab character
		tmpRowDelim = String.fromCharCode(0), // null character
		// actual delimiter characters for CSV format
		colDelim = '","',
		rowDelim = '"\r\n"';

	// Grab text from table into CSV formatted string
	var csv = '"';
	csv += formatRows($headers.map((i, row) => grabRow(i, row, tableType)));
	csv += rowDelim;
	csv += formatRows($rows.map((i, row) => grabRow(i, row, tableType))) + '"';

	// Data URI
	let csvData = "data:application/csv;charset=utf-8," + encodeURIComponent(csv);

	// For IE (tested 10+)
	if (window.navigator.msSaveOrOpenBlob) {
		let blob = new Blob([decodeURIComponent(encodeURI(csv))], {
			type: "text/csv;charset=utf-8;",
		});
		navigator.msSaveBlob(blob, filename);
	} else {
		$(this).attr({
			download: filename,
			href: csvData,
			//,'target' : '_blank' //if you want it to open in a new window
		});
	}

	//------------------------------------------------------------
	// CSV Helper Functions
	//------------------------------------------------------------
	// Format the output so it has the appropriate delimiters
	function formatRows(rows) {
		return rows
			.get()
			.join(tmpRowDelim)
			.split(tmpRowDelim)
			.join(rowDelim)
			.split(tmpColDelim)
			.join(colDelim);
	}
	// Grab and format a row from the table
	function grabRow(i, row, tableType) {
		let $row = jQuery(row);
		//for some reason $cols = $row.find('td') || $row.find('th') won't work...
		let $cols = $row.find("td");
		if (!$cols.length) $cols = $row.find("th");
		// the first and last columns in the product table
		// are inputs and should be ignored
		$cols = "products" === tableType ? $cols.not(":first").not(":last") : $cols;

		return $cols.map(grabCol).get().join(tmpColDelim);
	}
	// Grab and format a column from the table
	function grabCol(j, col) {
		let $col = $(col),
			$text = $col.text().trim();

		return $text.replace('"', '""'); // escape double quotes
	}
}
