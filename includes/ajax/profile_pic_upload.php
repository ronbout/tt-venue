<?php
if(isset($_FILES['prof_photo']['name'])){
    // filename
    $filename = $_FILES['prof_photo']['name'];

    // * ===========  I had an issue getting /uploads folder so for production we will need to change it  ============= *
    $location = dirname(__FILE__).'/photos/'.$filename;

    // file extension
    $file_extension = pathinfo($location, PATHINFO_EXTENSION);
    $file_extension = strtolower($file_extension);

    // Valid extensions
    $valid_ext = array("jpg","png","jpeg");

    $response = $_FILES['prof_photo']['name'];

    if(in_array($file_extension, $valid_ext)){
        move_uploaded_file($_FILES['prof_photo']['tmp_name'], $location);
    }else {
        $response = "An error occurred when uploading image.";
    }

    echo $response;
}
