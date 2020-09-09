jQuery(document).ready(function () {
	userForms();
	tasteLoadVenueFormEvents();
	if (typeof products != "undefined") {
		// console.log("products ", products);
		let selectedProds = [];
		loadSelectProducts(selectedProds);
	} else {
		// console.log("no products");
	}
});

const userForms = () => {
	if (
		jQuery("body").hasClass("user-new-php") ||
		jQuery("body").hasClass("user-edit-php")
	) {
		let $roleSelect = jQuery("#role");
		$roleSelect.length &&
			$roleSelect.change(function () {
				let selectRole = this.value;
				if ("venue" === selectRole) {
					jQuery("#user-venue-fields").css("display", "block");
				} else {
					jQuery("#user-venue-fields").css("display", "none");
				}
			});
	}
};

const tasteLoadVenueFormEvents = () => {
	if (jQuery("body").hasClass("product_page_venue-assign-products")) {
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
	}
};

const loadSelectProducts = (selectedProds) => {
	jQuery("#product-input").autocomplete({
		source: products,
		select: function (event, ui) {
			event.preventDefault();
			// jQuery(this).val("");
			let ndx = products.findIndex((p) => {
				return p.value === ui.item.value;
			});
			let selected = products.splice(ndx, 1);
			selectedProds = selectedProds.concat(selected);
			let selectTbody = buildSelectedRows(selectedProds);
			jQuery("#select-products-body").html(selectTbody);
			jQuery("#product-input").autocomplete("option", "source", products);
			// add click events to freshly created buttons in selected table
			setupUnSelect(selectedProds, products);
		},
	});

	jQuery("#assign-products-btn").click(function (e) {
		// no use for this now...later can use with ajax call
	});
};

const setupUnSelect = (selectedProds, products) => {
	jQuery(".unselect-btn").click(function (e) {
		e.preventDefault();
		let ndx = jQuery(this).data("ndx");
		let unSelected = selectedProds.splice(ndx, 1);
		products = products.concat(unSelected);
		let selectTbody = buildSelectedRows(selectedProds);
		jQuery("#select-products-body").html(selectTbody);
		jQuery("#product-input").autocomplete("option", "source", products);
		setupUnSelect(selectedProds, products);
	});
};

const buildSelectedRows = (selectedProds) => {
	let selectTbody = selectedProds.reduce((bodyRows, prod, ndx) => {
		bodyRows =
			bodyRows +
			`
		<tr>
		<td>${prod.prodId}</td>
		<td>${prod.sku}</td>
		<td>${prod.prodTitle}</td>
		<td>${prod.prodDate}</td>
		<td>${prod.expired}</td>
		<td>
			<input type="hidden" name="prod_ids[]" value="${prod.prodId}" >
			<button data-ndx="${ndx}" class="unselect-btn button button-secondary">UnSelect</button>
		</td>
	</tr>
		`;
		return bodyRows;
	}, "");
	if (selectedProds.length) {
		jQuery("#assign-products-btn").prop("disabled", false);
	} else {
		jQuery("#assign-products-btn").prop("disabled", true);
	}
	return selectTbody;
};
