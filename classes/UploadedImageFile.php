<?php
//abstract class for image uploads

abstract class UploadedImageFile {

  //static variables
  protected static $maxUploadSize = "2500000"; //max allowed size in bytes
  protected static $imageMIME = ["image/jpeg", "image/png", "image/gif", "image/webp"]; //mime allowed
  //instance variables
  protected $errorCodes = []; //array to append issues to. -1 for system error, 1 for over max-size, 2 for non-mime format, 
  protected $mime;
  protected $fileExtension;
  protected $fileSize; //size in bytes
  protected $width; //width in px
  protected $height; //height in px
  protected $tempFilePath; //full path including basename and ext
  protected $permFilePath = null; //full path including basename and ext, to be set using setter

  /*
  constructor
  @param the file to upload
  */
  public function __construct($uploadedFile) {

    $this->tempFilePath = $uploadedFile["tmp_name"];
    $this->fileExtension = strtolower(pathinfo($uploadedFile["name"], PATHINFO_EXTENSION)); //file ext
    $this->fileSize = $uploadedFile["size"];

    $sizeInfo = getimagesize($this->tempFilePath); //getimagesize($file) where $file is path
    
    $this->mime = $sizeInfo["mime"]; //mime type info in getimagesize()
    $this->width = $sizeInfo[0];
    $this->height = $sizeInfo[1];

    if ( !is_file($this->tempFilePath) || !is_array($sizeInfo) || $this->width == 0 || $this->height == 0 ) {
      throw new Exception("Uploaded file doesn't appear to be an image.");
    }

  }

  /*
  function to set full path of the file
  @param $dir absolute path to the directory storing the file
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

  /*
  abstract method to factory call other methods for file checking, moving, processing, saving
  */
  abstract public function upload(): bool;

  /*
  function to check user uploaded file for size, type
  @return boolean if all criteria are passed
  */
  protected function checkFile(): bool {

    //check file size
    $isSizeChecked = ($this->fileSize <= self::$maxUploadSize);
    if (!$isSizeChecked) {
      array_push($this->errorCodes, 1);
    }

    //check file type
    $isTypeChecked = in_array($this->mime, self::$imageMIME);
    if (!$isTypeChecked) {
      array_push($this->errorCodes, 2);
    }

    return ($isSizeChecked && $isTypeChecked);

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
  protected function move(): bool {
    
    if ( move_uploaded_file($this->tempFilePath, $this->permFilePath) ) {

      $success = true;

    } else {

      $success = false;
      array_push($this->errorCodes, -1);

    }

    return $success;

  }

  /*
  database persistence function to be implemented
  */
  abstract protected function persist(): bool;


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

    $meta = ["size" => $this->fileSize, "extension" => $this->fileExtension, "mime" => $this->mime, "width" => $this->width, "height" => $this->height];
    return $meta;

  }

  /*
  permanent file path getter
  */
  public function getFilePath(): string {

    return $this->permFilePath;

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