<?php
    $target_dir = "images/";
    $target_file = $target_dir . "AbeilleLQI_MapData_Perso.png";

    $uploadOk = 1;

    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "Fichier image du type: " . $check["mime"] . ".<br>";
            $uploadOk = 1;
        } else {
            echo "Le fichier choisi n'est pas une image PNG.<br>";
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
        echo "Désolé. Votre image est trop grande.<br>";
        $uploadOk = 0;
    }

    // Allow certain file formats
    // if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"  && $imageFileType != "gif" ) {
    if( $imageFileType != "png" ) {
        echo "Désolé. Seule le format 'PNG' est supporté.<br>";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        // echo "Sorry, your file was not uploaded.<br>";
        // if everything is ok, try to upload file
    } else {
         if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "Le fichier ". basename( $_FILES["fileToUpload"]["name"]). " a été installé.<br>";
        } else {
            echo "Désolé. Une erreur a eu lieu pendant le transfert.<br>";
        }
    }
    echo "La page devrait se rafraichir dans 5 secondes avec le graph du reseau.</br>";
    echo "Si ca n'est pas le cas il vous faudra la recharger manuellement.</br>";

    header("Refresh:5; url=NetworkGraph.php");
?>
