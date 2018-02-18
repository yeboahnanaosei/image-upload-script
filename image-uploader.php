<?php
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload']) && isset($_FILES['pic']) && !empty($_FILES['pic'])) {

        // Grab particulars of uploaded file
        $imgName  = md5($_FILES['pic']['name']); // md5() to generate a different name from the original file name.
        $imgSize  = $_FILES['pic']['size'];
        $imgExt   = pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION); // Very trivial
        $imgError = $_FILES['pic']['error'];
        $imgTmp   = $_FILES['pic']['tmp_name'];

        // Grab the mime type of the file.
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        @$imgMime = $finfo->file($imgTmp); // <-- '@' Error suppressor here. Watch out

        // Restrictions
        $allowedExt   = ['jpg', 'jpeg', 'png', 'gif'];
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $allowedSize  = 5000000; // 5MB I think

        // Upload destination. Better if you keep this destination outside your web direcctory
        $destination  = "./uploads/{$imgName}.{$imgExt}"; // You might want to store this in a database

        // Validation of the file can now begin
        // Check if anything is uploaded in the first place
        if ($imgError === 4) {
            throw new Exception(
                'UPLOAD FAILED: No image uploaded'
            );

        // Check the extension of the file. This is very trivial, its just for redundancy
        } elseif (!in_array($imgExt, $allowedExt)) {
            throw new Exception(
                'UPLOAD FAILED: NOT A VALID IMAGE <br>
                Only jpg, jpeg, png or gif allowed'
            );

        // Check mime type of image using finfo class. See line 12/13
        } elseif (!in_array($imgMime, $allowedMimes)) {
            throw new Exception(
                'UPLOAD FAILED: NOT A VALID IMAGE <br>
                Only jpg, jpeg, png or gif allowed'
            );

        // Check mime type using GD library. This is just another redundancy here
        } elseif (!getimagesize($imgTmp)) {
            throw new Exception(
                'UPLOAD FAILED: NOT A VALID IMAGE <br>
                Only jpg, jpeg, png or gif allowed'
            );

        // Ensure the picture is not above size limit
        } elseif ($imgSize > $allowedSize) {
            throw new Exception(
                'UPLOAD FAILED: IMAGE SIZE TOO BIG <br>
                Maximum of 5MB allowed. Current size: ' . $imgSize
            );

        // Check if the file already exists on the server. Trivial in some situations
        } elseif (file_exists($destination)) {
            throw new Exception(
                'UPLOAD FAILED: FILE ALREADY EXISTS <br>
                The file you are attempting to upload seems to have been uploaded already'
            );
        } else {
            /*
                We can go ahead to upload the file if all the checks pass successfully
                1. Check if the file was uploaded via the form through an
                HTTP POST method - is_uploaded_file()
                
                2. If it is indeed an uploaded file then remove executable permissions
                from the file - chmod()
                
                3. If removing the executable permissions passes successfully, the file
                can then be move the file to its destination - move_uploaded_file

                For each of the above operations, there is a corresponding error message if
                there is a failure
            */
            if (is_uploaded_file($imgTmp)) {

                // Remove executable permissions on the file. Only read/write for owner.
                if (!chmod($imgTmp, 0644)) {
                    // This exception is perhaps not necessary for the user to see. Consider what you can do here.
                    throw new Exception('UPLOAD FAILED: Could not change file permissions');
                }
                
                if (@move_uploaded_file($imgTmp, $destination)) { // <-- '@' Error suppressor here. Watch out
                        echo 'SUCCESS: Your image was successfully uploaded!';
                } else {
                    throw new Exception(
                        'UPLOAD FAILED: COULD NOT MOVE YOUR FILE <br>
                        Perhaps the destination folder does not exist
                        or you don\'t have permission to write to that folder <br>'
                    );
                }
            } else {
                // This just a bluff. You might want to do something else here.
                throw new Exception(
                    'WARNING: MALICIOUS ACTIVITY DETECTED <br>
                    Upload a picture using only the form.<br> Your IP has been recorded!'
                );
            }
        }
    } else {
        header('Location: ./');
    }
} catch (Exception $e) {
    die($e->getMessage());
}
