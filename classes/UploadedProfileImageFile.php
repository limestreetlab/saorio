<?php
//concreate class for profile image uploads

class UploadedProfileImageFile extends UploadedImageFile {

    //new static variables
    const DIR = PROFILE_UPLOAD_DIR;
    const MAX_WIDTH = 300; //max width allowed in px
    const MAX_HEIGHT = 300; //max height allowed in px
    //new instance variables
    protected $filename; //filename to save to, without ext

    /*
    @Override
    constructor, inherited from super
    id used to reference a created object is either its filename (with ext) or full absolute path
    */
    public function __construct($uploadedFile = null, $id = null) {

        parent::__construct($uploadedFile, $id); //super constructor 

        if (isset($uploadedFile)) { //new creation

            $this->filename = $_SESSION["user"] . "-avatar-" . filemtime($this->tempFilePath); //<username>-avatar-<timestamp> as filename, where timestamp is unix upload time
        
        } else { //existing reference

            //check if only filename provided, if so make it full path
            $this->id = dirname($id) == "." ? self::DIR . $id : $id; //full path as id
            
            if (file_exists($this->id)) { //id defined is full path, so existence checked using file existence in system
                array_push($this->errorCodes, 4);
                throw new Exception("the provided id " . $this->id . " cannot be found.");
            }

            //re-create its variables
            $this->permFilePath = $this->id;             
            $this->fileExtension = strtolower(pathinfo($this->permFilePath, PATHINFO_EXTENSION)); 
            $this->fileSize = filesize($this->permFilePath); //bytes 
            $sizeInfo = getimagesize($this->permFilePath);        
            $this->width = $sizeInfo[0]; 
            $this->height = $sizeInfo[1];
            $this->mime = $sizeInfo["mime"]; 
            $this->exifOrientation = exif_read_data($this->permFilePath)['Orientation'];   
            
        }

    }

    /*
    @Override
    function to upload file after related procedures 
    operations include setting and moving to permanent path, checking file properties, image processing, database persistance, optionally deleting existing profile photo 
    @param $deleteExisting true to remove old profile img file or false to leave it untouched
    */
    public function upload(bool $deleteExisting = false): bool {

        try {

            $this->setPermFilePath(self::DIR, $this->filename)->checkFile()->move();
            $this->square()->useExifOrientation(); //must square first using non-exif width and height before rotating, because even after rotating width and height are based on non-exif
            
            if ($deleteExisting) { //if existing file should be removed, get the existing path, delete it after successful persistance
                        
                $oldFilePath = $this->mysql->request(MySQL::readBasicProfileQuery, [":user" => $_SESSION["user"]])[0]["profilePictureURL"]; //abs path to existing file
                $oldFileName = basename($oldFilePath); //filename including ext
                $noDeleteFiles = ["avatar0.png", "avatar1.png", "avatar2.png", "avatar3.png", "avatar4.png", "avatar5.png", "avatar6.png", "avatar7.png", "avatar8.png"]; //files that should not be deleted
                
                $this->persist();

                if (in_array($oldFileName, $noDeleteFiles)) {
                    return true;
                }

                if (!empty($oldFilePath)) {
                    if (!unlink($oldFilePath)) {
                        error_log("Failed to delete a profile photo: " . $oldFilePath);
                    }
                }

            } else {

                $this->persist();

            }

            return true;

        } catch (Exception $ex) {

            return false;

        }

    }

    /*
    @Override
    function to persist profile picture data to database
    */
    protected function persist(): self {

        $params = [":url" => "$this->permFilePath", ":mime" => "$this->mime", ":user" => $_SESSION["user"] ];

        try {

            $this->mysql->request(MySQL::updateProfilePictureQuery, $params);
            return $this;

        } catch (Exception $ex) {

            array_push($this->errorCodes, -1);
            error_log("Cannot persist a profile picture upload: " . $ex->getMessage());
            throw $ex;
            
        }
        
    }
    
    /*
    function to resize the oversized image to a square of MAX_WIDTH/MAX_HEIGHT, using PHP GD functions
    it first resizes the image's smaller dimension to MAX, maintaining aspect ratio
    it then fits the larger dimension to MAX using its centre, cropping away its two sides
    it overwrites the original image to file and then destroys all image resources in memory
    IN THE FUTURE, ADD JS FUCNTIONALITY TO CHOOSE CROPPING and CONFIRM like FB
    */
    protected function square(): self {

        //use the MIME-based functions to create an image resource handle of this file
        switch ($this->mime) {

            case "image/jpeg":
                $photo_src = imagecreatefromjpeg($this->permFilePath);
                break;
            case "image/png":
                $photo_src = imagecreatefrompng($this->permFilePath);
                break;
            case "image/gif":
                $photo_src = imagecreatefromgif($this->permFilePath);
                break;
            case "image/webp":
                $photo_src = imagecreatefromwebp($this->permFilePath);
                break;
            default:
                array_push($this->errorCodes, 2);
                throw new Exception("file type is not a supported image type.");

        }        

        //scale the photo, smaller dimension side to MAX
        if ($this->height > $this->width) { //portrait photo

            $scaledHeight = $this->height * ( self::MAX_WIDTH / $this->width );
            $photo_scaled = imagescale($photo_src, self::MAX_WIDTH, $scaledHeight, IMG_BICUBIC);
            $this->width = self::MAX_WIDTH;
            $this->height = $scaledHeight;

        } else { //landscape photo, or already square

            $scaledWidth = $this->width * ( self::MAX_HEIGHT / $this->height );
            $photo_scaled = imagescale($photo_src, $scaledWidth, self::MAX_HEIGHT, IMG_BICUBIC);
            $this->width = $scaledWidth;
            $this->height = self::MAX_HEIGHT;

        }

        imagedestroy($photo_src); //release used img handle

        //check scaling success
        if (!$photo_scaled) { //if scaling fails, it returns false
            array_push($this->errorCodes, -1);
            error_log("Error occurred in scaling a profile photo.");
            throw new Exception("image scaling failed.");
        }


        //crop the over-sized dimension to MAX (square needs no cropping)
        if ($this->height > $this->width) { //portrait, so crop height

            //calc crop dimension
            $fat = ( $this->height - self::MAX_HEIGHT ) / 2; //excess dimension to cut off a side
            $trimStart = 0 + $fat; //y-dimension start from top
            
            //crop
            $dimen = ["x" => 0, "y" => $trimStart, "width" => $this->width, "height" => self::MAX_HEIGHT] ;
            $photo_cropped = imagecrop($photo_scaled, $dimen);

        } elseif ($this->height < $this->width) { //landscape, so crop width

            //calc crop dimension
            $fat = ( $this->width - self::MAX_WIDTH ) / 2;
            $trimStart = 0 + $fat; //x-dimension start from left

            //crop
            $dimen = ["x" => $trimStart, "y" => 0, "width" => self::MAX_WIDTH, "height" => $this->height];
            $photo_cropped = imagecrop($photo_scaled, $dimen);

        }

        imagedestroy($photo_scaled);

        //check cropping success
        if (!$photo_cropped) { //if cropping fails, it returns false
            array_push($this->errorCodes, -1);
            error_log("Error occurred in cropping a profile photo.");
            throw new Exception("image cropping failed.");
        }

        //saving file, to JPEG format regardless of what format it was
        if (!imagejpeg($photo_cropped, $this->permFilePath, 100)) {
            array_push($this->errorCodes, -1);
            error_log("Error occurred in saving a processed photo to file.");
            throw new Exception("image file saving failed.");
        }

        imagedestroy($photo_cropped);
    
        return $this;

    } //end square function

    /*
    @Override
    function to delete the current profile picture file of this user on server and db
    */
    public function delete(): bool {

        try {

            $this->mysql->request(MySQL::updateProfilePictureToDefaultQuery, [":user" => $_SESSION["user"]]); //remove from db

        } catch (Exception $ex) {
            
            array_push($this->errorCodes, -1);
            return false;
        
        }

        return unlink($this->permFilePath); //removing from server 

    }

    /*
    @Override
    */
    public function getFileWebPath(): string {

        $filename = basename($this->permFilePath); //filename with ext
        return PROFILE_UPLOAD_DIR_URL . $filename; //relative path

    }

    /*
    @Override
    */
    static public function ConvertFileWebPath(string $fileSystemPath): string {

        $filename = basename($fileSystemPath); //filename with ext
        return PROFILE_UPLOAD_DIR_URL . $filename; //relative path

    }



} //end class


?>