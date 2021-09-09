<?php
//concreate class for profile image uploads

class UploadedProfileImageFile extends UploadedImageFile {

    //new static variables
    protected static $uploadedDir = UPLOAD_DIR;
    const MAX_WIDTH = 300; //max width allowed in px
    const MAX_HEIGHT = 300; //max height allowed in px
    //new instance variables
    protected $filename; //filename to save to, without ext
    protected $mysql; //object for mysql database access

    //@Override
    public function __construct($uploadedFile) {

        parent::__construct($uploadedFile); //super constructor
        $this->filename = $_SESSION["user"] . "-profile"; //<username>-profile as filename
        $this->mysql = MySQL::getinstance();

    }

    //@Override
    public function upload(): bool {

        $success = false;

        if ( $this->setPermFilePath(self::$uploadedDir, $this->filename) ) { //call setter to set $permFilePath 

            if( $this->checkFile() ) { //if file is checked

                if ( $this->move() ) { //if file is moved from temp to perm

                    if ($this->process() ){ //if file is processed

                        if( $this->persist() ){ //if file is persisted

                            $success = true; //checked, moved, processed, persisted

                        }

                    }

                }

            }

        }

        return $success;

    }

    //@Override
    protected function persist(): bool {

        $params = [":url" => "$this->permFilePath", ":mime" => "$this->mime", ":user" => $_SESSION["user"] ];

        try {

            $this->mysql->request($this->mysql->updateProfilePictureQuery, $params);
            $success = true;

        } catch (Exception $ex) {
            $success = false;
            error_log("Cannot persist a profile picture upload: " . $ex);
        }

        return $success;
        
    }

    /*
    function to crop the image to a square of MAX_WIDTH/MAX_HEIGHT, using PHP GD functions
    it first resizes the image's smaller dimension to MAX, maintaining aspect ratio
    it then crops the larger dimension (which now is above MAX) at centre to MAX, eliminating the excessive parts
    it overwrites the original image to file and then destroys all image resources in memory
    */
    protected function process(): bool {
        //gotta think about how to implement
        return true;
    }

}
?>