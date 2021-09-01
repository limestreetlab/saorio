<?php
//abstract class for image uploads

abstract class UploadedImageFile {

  //static variables
  protected static $maxUploadSize = "1500000"; //max allowed size in bytes
  protected static $imageMIME = ["image/jpeg", "image/png", "image/gif", "image/svg+xml", "image/webp"]; //mime allowed
  //instance variables
  protected $messages = [];
  protected $mime;
  protected $fileExtension;
  protected $fileSize; //size in bytes
  protected $width; //width in px
  protected $height; //height in px
  protected $tempFilePath; //full path including basename and ext
  protected $permFilePath = null; //full path including basename and ext, set using setter

  //constructor
  public function __construct($uploadedFile) {

    $sizeInfo = getimagesize($uploadedFile);

    if ( !is_file($uploadedFile) || !is_array($sizeInfo) || $sizeInfo[0] == 0 || $sizeInfo[1] ==0 ) {
      throw new Exception("Uploaded file doesn't appear to be an image.");
    }
    
    $this->mime = $sizeInfo["mime"]; //mime type info in getimagesize()
    $this->fileExtension = strtolower(pathinfo($uploadedFile["name"], PATHINFO_EXTENSION)); //file ext
    $this->fileSize = $uploadedFile["size"];
    $this->tempFilePath = $uploadedFile["tmp_name"];
    $this->width = $sizeInfo[0];
    $this->height = $sizeInfo[1];

  }

  /*
  function to set full path of the file
  @param $dir absolute path to the directory
  @param $filename new name for the file, without extension
  */
  protected function setPermFilePath(string $dir, string $filename): bool {

    if ( self::checkDirectory($dir) && self::checkName($filename) ) {

      $this->permFilePath = self::ensureDirectorySlash($dir) . $filename . "." . $this->fileExtension; //permanent path placeholder
      return true;

    } else {

      return false;

    }

  }

  //abstract method to factory call other methods for file checking, moving, processing, saving
  abstract public function upload(): bool;

  //function to check user file for size, type
  protected function checkFile(): bool {

    //check file size
    $isSizeChecked = ($this->fileSize <= self::$maxUploadSize);
    if (!$isSizeChecked) {
      $msg = "<div class='alert alert-warning'>The uploaded file exceeds " . self::$maxUploadSize/1000000 . " MB.</div>";
      array_push($this->messages, $msg);
    }

    //check file type
    $isTypeChecked = in_array($this->mime, self::$imageMIME);
    if (!$isTypeChecked) {
      $msg = "<div class='alert alert-warning'>The uploaded file isn't an allowed format.</div>";
      array_push($this->messages, $msg);
    }

    return ($isSizeChecked && $isTypeChecked);

  }

  //function to check upload directory is valid
  static protected function checkDirectory(string $dir): bool {
    
    return ( is_dir($dir) && is_writable($dir) );

  }

  //function to check new filename is valid, free of special characters except _ and -
  static protected function checkName(string $filename): bool {

      $allowedPattern = '/^[-\w]+$/'; //alphanumeric plus - and _ 
      return preg_match($allowedPattern, $filename);

  }

  //function to move from temp to permanent destination
  protected function move(): bool {
    
    return move_uploaded_file($this->tempFilePath, $this->permFilePath);

  }

  //database persistence function to be implemented
  abstract protected function persist(): bool;


  //messages getter
  public function getMessages(): array {

    return $this->messages;

  }

  //class method to check if a slash is at end of path, if not add one
  protected static function ensureDirectorySlash(string $dir): string {

    $lastChar = substr($dir, -1);
    if ($lastChar != "/" || $lastChar != "\\") { //if doesn't end with a slash
      $dir .= "/"; //add one
    }

    return $dir;

  }

} //end class

?>