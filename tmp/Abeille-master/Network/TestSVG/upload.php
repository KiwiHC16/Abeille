<?php
    $target_dir = "images/";
    $target_file = $target_dir . "AbeilleLQI_MapData.png";
    
    $uploadOk = 1;
    
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".<br>";
            $uploadOk = 1;
        } else {
            echo "File is not an image.<br>";
            $uploadOk = 0;
        }
    }
    
    // Check if file already exists, in my case I don't let the choise I also write on the previous one.
    /*
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }
     */
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        echo "Sorry, your file is too large.<br>";
        $uploadOk = 0;
    }
    
    // Allow certain file formats
    // if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"  && $imageFileType != "gif" ) {
    if( $imageFileType != "png" ) {
        echo "Sorry, only PNG files are allowed.<br>";
        $uploadOk = 0;
    }
    
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.<br>";
        // if everything is ok, try to upload file
    } else {
         if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.<br>";
        } else {
            echo "Sorry, there was an error uploading your file.<br>";
        }
    }
    echo "La page va se rafraichir dans 5 secondes avec le graph du reseau.</br>";
    echo "Il faudra peut etre faire un refresh.</br>";
    
    header("Refresh:5; url=NetworkGraph.html");
    ?>
