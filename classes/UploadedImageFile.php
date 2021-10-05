<?php
//abstract class for image uploads

abstract class UploadedImageFile {

  //static variables
  const MAX_UPLOAD_SIZE = "2500000"; //max allowed size in bytes
  const IMAGE_MIME = ["image/jpeg", "image/png", "image/gif", "image/webp"]; //mime allowed
  //instance variables
  protected $errorCodes = []; //array to append errors to. -1 for system error, 1 for over max-size, 2 for non-mime format, 3 for unknown photo orientation, 4 for unfound image file id when referencing an existing file,  
  protected $mime;
  protected $fileExtension;
  protected $fileSize; //size in bytes
  protected $width; //width in px
  protected $height; //height in px
  protected $tempFilePath; //full path including basename and ext
  protected $permFilePath; //full path including basename and ext, to be set using setter
  protected $exifOrientation; //exif orientation values range 1-8 or null of exif data null
  protected $id; //used to reference an existing object
  protected $mysql; //object for mysql database access

  /*
  constructor, used to create a new object or reference a created object
  some id is used to gain handle to a created object, that can be database id, filename, etc
  for new creation, file is provided; for old reference, file is null
  @param the file to upload
  @param the id of an existing file
  */
  public function __construct($uploadedFile = null, $id = null) {

    if (is_null($uploadedFile) && is_null($id)) {
      throw new Exception("parameters cannot all be null.");
    }

    $this->mysql = MySQL::getInstance();

    if (isset($uploadedFile)) { //new file creation

      $this->tempFilePath = $uploadedFile["tmp_name"];
      $this->fileExtension = strtolower(pathinfo($uploadedFile["name"], PATHINFO_EXTENSION)); //file ext
      $this->fileSize = $uploadedFile["size"];
      $this->exifOrientation = exif_read_data($this->tempFilePath)['Orientation'];  

      $sizeInfo = getimagesize($this->tempFilePath); //getimagesize($file) where $file is path
      
      $this->mime = $sizeInfo["mime"]; //mime type info in getimagesize()
      $this->width = $sizeInfo[0];
      $this->height = $sizeInfo[1];
      $this->id = null;

      if ( !is_file($this->tempFilePath) || !is_array($sizeInfo) || $this->width == 0 || $this->height == 0 ) {
        throw new Exception("Uploaded file doesn't appear to be an image.");
      }
    
    } else { //old file reference

      $this->id = $id; 
      $this->mime = null; //set in concrete class
      $this->fileExtension = null; //set in concrete class
      $this->fileSize = null; //set in concrete class
      $this->permFilePath; //set in concrete class
      $this->width = null; //set in concrete class
      $this->height = null; //set in concrete class
      $this->exifOrientation = null; //set in concrete class

    }

  }

  /*
  function to set full path of the file
  @param $dir absolute path to the directory storing the file
  @param $filename new name for the file, without extension
  */
  protected function setPermFilePath(string $dir, string $filename): self {

    if ( self::checkDirectory($dir) && self::checkName($filename) ) {

      $this->permFilePath = self::ensureDirectorySlash($dir) . $filename . "." . $this->fileExtension; //permanent path placeholder
      
      return $this;

    } else {

      array_push($this->errorCodes, -1);
      throw new Exception("Failed to set file permanent path. Check the input directory or filename.");

    }

  }

  /*
  abstract method to factory call other methods for file checking, moving, processing, saving
  */
  abstract public function upload();
  
  /*
  database persistence function to be implemented
  */
  abstract protected function persist();

  /*
  remove an uploaded file
  */
  abstract protected function delete();

  /*
  function to check user uploaded file for size, type
  @return boolean if all criteria are passed
  */
  protected function checkFile(): self {

    //check file size
    $isSizeChecked = ($this->fileSize <= self::MAX_UPLOAD_SIZE);
    if (!$isSizeChecked) {
      array_push($this->errorCodes, 1);
    }

    //check file type
    $isTypeChecked = in_array($this->mime, self::IMAGE_MIME);
    if (!$isTypeChecked) {
      array_push($this->errorCodes, 2);
    }

    if ($isSizeChecked && $isTypeChecked) {

      return $this;

    } else {

      throw new Exception("File size too large or type unsupported. ");

    }

  }

  /*
  function to check upload directory is valid
  */
  static protected function checkDirectory(string $dir): bool {
    
    return ( is_dir($dir) && is_writable($dir) );

  }

  //function to check new filename is valid, free of special characters except _ and -
  static protected function checkName(string $filename): bool {

      $allowedPattern = '/^[-\w]+$/'; //alphanumeric plus - and _ 
      return preg_match($allowedPattern, $filename);

  }

  /*
  function to move uploaded file from temporary to permanent destination
  */
  protected function move(): self {

    if (!isset($this->tempFilePath, $this->permFilePath)) {

      throw new Exception("both source and destination must be set to move a file.");

    }
    
    if ( move_uploaded_file($this->tempFilePath, $this->permFilePath) ) {

      return $this;

    } else {

      array_push($this->errorCodes, -1);
      throw new Exception("Failed to move file from temporary to permanent path of " . $this->permFilePath);

    }

  }

  /*
  errorCodes getter
  */
  public function getErrors(): array {

    return array_unique($this->errorCodes);

  }

  /*
  file meta getter
  */
  public function getFileMeta(): array {

    return ["size" => $this->fileSize, "extension" => $this->fileExtension, "mime" => $this->mime, "width" => $this->width, "height" => $this->height, "exifOrientation" => $this->exifOrientation];

  }

  /*
  permanent file path getter
  */
  public function getFilePath(): string {

    return $this->permFilePath;

  }
  
  /*
  function to convert the file's absolute path to relative one
  @return relative file path
  */
  abstract protected function getFileRelativePath(): string;

  /*
  function to rotate/flip the image according to its Exif orientation tag
  The 8 EXIF orientation values are numbered 1 to 8.
  1 = 0 degrees: the correct orientation, no adjustment is required.
  2 = 0 degrees, mirrored: image has been flipped back-to-front.
  3 = 180 degrees: image is upside down.
  4 = 180 degrees, mirrored: image has been flipped back-to-front and is upside down.
  5 = 90 degrees: image has been flipped back-to-front and is on its side.
  6 = 90 degrees, mirrored: image is on its side.
  7 = 270 degrees: image has been flipped back-to-front and is on its far side.
  8 = 270 degrees, mirrored: image is on its far side.
  @see https://sirv.com/help/articles/rotate-photos-to-be-upright/
  @return self
  */
  protected function useExifOrientation(): self {

    if ($this->exifOrientation != 1 && !is_null($this->exifOrientation)) {

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
      
      switch ($this->exifOrientation) {

        case 3:
          $photo_new = imagerotate($photo_src, 180, 0); //rotate 180dg
          break;

        case 6:
          $photo_new = imagerotate($photo_src, -90, 0); //rotate 90dg clockwise
          break;

        case 8:
          $photo_new = imagerotate($photo_src, 90, 0); //rorate 90dg counter-clockwise
          break;

        default:
          array_push($this->errorCodes, 3);
          throw new Exception("file's orientation is unknown.");

      }

      imagedestroy($photo_src); //release used img handle

      //saving the rotated image
      //use the MIME-based functions to save the image resource to file
      switch ($this->mime) {

        case "image/jpeg":
            $saved = imagejpeg($photo_new, $this->permFilePath, 100);
            break;
        case "image/png":
            $saved = imagepng($photo_new, $this->permFilePath, 9);
            break;
        case "image/gif":
            $saved = imagegif($photo_new, $this->permFilePath);
            break;
        case "image/webp":
            $saved = imagewebp($photo_new, $this->permFilePath, 100);
            break;

      }
      
      imagedestroy($photo_new); //release used img handle

      //check save success
      if (!$saved) {
        array_push($this->errorCodes, -1);
        error_log("Error occurred in saving a rotated photo to file.");
        throw new Exception("Failed to save file");
      }
    
    }

    return $this;

  } //end function

  /*
  class method to check if a slash is at end of path, if not add one
  */
  protected static function ensureDirectorySlash(string $dir): string {

    $lastChar = substr($dir, -1);
    if ($lastChar != "/" || $lastChar != "\\") { //if doesn't end with a slash
      $dir .= "/"; //add one
    }

    return $dir;

  }


} //end class

?>