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

    /*
    @Override
    constructor, inherited from super
    instantiate this class instance variables
    */
    public function __construct($uploadedFile) {

        parent::__construct($uploadedFile); //super constructor 
        $this->filename = $_SESSION["user"] . "-" . filemtime($this->tempFilePath); //<username>-<timestamp> as filename, where timestamp is unix upload time
        $this->mysql = MySQL::getinstance();

    }

    /*
    @Override
    function to perform necessary operations to upload the file
    @param $removeExisting true to remove old file
    */
    public function upload(bool $deleteExisting = false): bool {

        $success = false;

        if ( $this->setPermFilePath(self::$uploadedDir, $this->filename) ) { //call setter to set $permFilePath 

            if( $this->checkFile() ) { //if file is successfully checked

                if ( $this->move() ) { //if file is moved from temp to perm

                    if ($this->process() ) { //if file is successfully processed

                        if ($deleteExisting) { //if existing file should be removed
                            
                            $oldFilePath = $this->mysql->request($this->mysql->readBasicProfileQuery, [":user" => $_SESSION["user"]])[0]["profilePictureURL"];
                            
                            if( $this->persist() ) { //if file is successfully persisted

                                $success = true;
                                unlink($oldFilePath); 
                            
                            } 
                            
                        } else { //if no need to remove existing file

                            $success = $this->persist(); 

                        }

                    }

                }

            }

        }

        return $success;

    }

    /*
    @Override
    function to persist profile picture data to database
    */
    protected function persist(): bool {

        $params = [":url" => "$this->permFilePath", ":mime" => "$this->mime", ":user" => $_SESSION["user"] ];

        try {

            $this->mysql->request($this->mysql->updateProfilePictureQuery, $params);
            $success = true;

        } catch (Exception $ex) {

            $success = false;
            array_push($this->errorCodes, -1);
            error_log("Cannot persist a profile picture upload: " . $ex->getMessage());
            
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

    /*
    function to delete the current profile picture file of this user on server
    */
    public function deleteExisting(): bool {

        $existingFilePath = $this->mysql->request($this->mysql->readBasicProfileQuery, [":user" => $_SESSION["user"]])[0]["profilePictureURL"];
        return unlink($existingFilePath);

    }
}
?>