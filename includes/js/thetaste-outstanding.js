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

	jQuery("#export")
		.unbind("click")
		.click(function (event) {
			// var outputFile = 'export'
			let year = jQuery("#outstanding-year").val();
			let yearType = jQuery("#outstanding-year-type").val();
			let outputFile = `export_${yearType}_year_${year}.csv`;

			// CSV
			exportTableToCSV.apply(this, [jQuery("#out-product-table"), outputFile]);

			// IF CSV, don't do event.preventDefault() or return false
			// We actually need this to be a typical hyperlink
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

/**
 * code for exporting table to csv
 */
function exportTableToCSV($table, filename) {
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
	csv += formatRows($headers.map(grabRow));
	csv += rowDelim;
	csv += formatRows($rows.map(grabRow)) + '"';

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
	// Helper Functions
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
	function grabRow(i, row) {
		let $row = jQuery(row);
		//for some reason $cols = $row.find('td') || $row.find('th') won't work...
		let $cols = $row.find("td");
		if (!$cols.length) $cols = $row.find("th");

		return $cols.map(grabCol).get().join(tmpColDelim);
	}
	// Grab and format a column from the table
	function grabCol(j, col) {
		let $col = $(col),
			$text = $col.text().trim();

		return $text.replace('"', '""'); // escape double quotes
	}
}
