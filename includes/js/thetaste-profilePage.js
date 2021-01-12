let update_profile_photo = document.getElementById("update_photo");
let profile_photo = document.getElementById("profile_photo");
let input_file = document.getElementById("prof_photo");
let img = document.getElementById("business_photo");
let url = window.location.protocol + "//" + window.location.host;

update_profile_photo.addEventListener("click", (e) => {
	input_file.click();
});

// profile_photo.addEventListener("mouseenter", (e) => {
// 	update_profile_photo.style.display = "unset";
// 	update_profile_photo.setAttribute(
// 		"class",
// 		"btn btn-primary animate__animated animate__bounceInDown"
// 	);
// });

// profile_photo.addEventListener("mouseleave", (e) => {
// 	update_profile_photo.style.display = "none";
// 	update_profile_photo.setAttribute("class", "btn btn-primary");
// });

input_file.addEventListener("change", (e) => {
	e.preventDefault();
	let file = e.target.files;

	if (file.length > 0) {
		let data = new FormData();
		data.append("prof_photo", file[0]);

		let xhttp = new XMLHttpRequest();

		if (/localhost/.test(window.location.href)) {
			xhttp.open(
				"POST",
				url +
					"/taste/wp-content/plugins/thetaste-venue/includes/ajax/profile_pic_upload.php",
				true
			);
			xhttp.onreadystatechange = function () {
				if (this.readyState === 4 && this.status === 200) {
					let sub_err = "An error occurred when uploading image.";
					let response = this.responseText;

					if (response.includes(sub_err)) {
						Swal.fire({
							icon: "error",
							title: "An error occurred when uploading image.",
						});
					} else {
						Swal.fire({
							icon: "success",
							title: "Profile picture updated",
						});

						img.setAttribute(
							"src",
							`${url}/taste/wp-content/plugins/thetaste-venue/includes/ajax/photos/${response}`
						);
						update_profile_photo.style.display = "none";
					}
				}
			};
		} else {
			// production code here
		}

		xhttp.send(data);
	}
});
