// let $update_profile_photo = $('#update_photo');
// let $profile_photo = $('#profile_photo');
// let $input_file = $('#prof_photo');
// let $img = $('#business_photo');
// let $url = window.location.protocol + "//" + window.location.host;

// $update_profile_photo.on("click", () => $input_file.trigger('click'));

// $input_file.on("change", e => {
// 	e.preventDefault();
// 	let $file = e.target.files;

// 	if ($file.length > 0) {
// 		let $data = new FormData();
// 		$data.append("prof_photo", $file[0]);

// 		let $xhttp = new XMLHttpRequest();

// 		if (/localhost/.test(window.location.href)) {
// 			$xhttp.open(
// 				"POST",
// 				url +
// 					"/taste/wp-content/plugins/thetaste-venue/includes/ajax/profile_pic_upload.php",
// 				true
// 			);
// 			$xhttp.onreadystatechange = function () {
// 				if (this.readyState === 4 && this.status === 200) {
// 					let $sub_err = "An error occurred when uploading image.";
// 					let $response = this.responseText;

// 					if ($response.includes($sub_err)) {
// 						Swal.fire({
// 							icon: "error",
// 							title: "An error occurred when uploading image.",
// 						});
// 					} else {
// 						Swal.fire({
// 							icon: "success",
// 							title: "Profile picture updated",
// 						});

// 						$img.attr(
// 							"src",
// 							`${$url}/taste/wp-content/plugins/thetaste-venue/includes/ajax/photos/${response}`
// 						);
// 						$update_profile_photo.css('display','none');
// 					}
// 				}
// 			};
// 		} else {
// 			// production code here
// 		}

// 		$xhttp.send($data);
// 	}
// });
