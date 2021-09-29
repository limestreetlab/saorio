<?php
//concrete class for post image posts


class UploadedPostImageFile extends UploadedImageFile {

  //new variables
  protected static $uploadDir = POST_UPLOAD_DIR;
  protected $mysql; //mysql db access obj
  protected $filename; //filename to save to, without ext

  /*
  @Override
  constructor, inherited from super
  id in the image posts table used as id to identify each posted image
  a record in image posts table must pre-exist before an image file can be added
  the entry id is used to identify the record to insert the file
  both id and file for new creation, id only for old reference  
  */
  public function __construct(int $id, $uploadedFile = null) {

    parent::__construct($uploadedFile); //super constructor
    $this->mysql = MySQL::getInstance();
    $this->id = $id;

    if (isset($uploadedFile)) { //new file creation
        
      $this->filename = $_SESSION["user"] . filemtime($this->tempFilePath) . mt_rand(0, 100); //<username><timestamp><0-100> as filename, where timestamp is unix upload time

    } else { //existing reference

      if (!$this->mysql->request($this->mysql->readImagePostImageQuery, [":id" => "$this->id"])) {
        throw new Exception("the provided id " . $this->id . " cannot be found.");
      }
      $this->permFilePath = $this->mysql->request($this->mysql->readImagePostImageQuery, [":id" => "$this->id"])[0]["imageURL"];
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
  function to persist image to database
  @param $id, the id of the record in the image posts table the image belongs to
  */
  protected function persist(): bool {

    $params = ["imageURL" => "$this->permFilePath", ":imageMIME" => "$this->mime", ":id" => "$this->id"];

    try {

      $this->mysql->request($this->mysql->updateImagePostImageQuery, $params);
      return true;

    } catch (Exception $ex) {

      return false;

    }

  }

  /*
  @Override
  */
  public function upload(): bool {

    try {

      $this->setPermFilePath(self::$uploadDir, $this->filename)->checkFile()->move();
      $this->useExifOrientation()->persist();
      return true;

    } catch (Exception $ex) {

      return false;

    }

  }

  /*
  Override
  function to delete the posted image file on server and db
  */
  public function delete(): bool {

    try {

      $this->mysql->request($this->mysql->deleteImagePostImageQuery, [":id" => "$this->id"]);

    } catch (Exception $ex) {

      return false;

    }

    return unlink($this->permFilePath);

  }

  /*
  @Override
  */
  public function getFileRelativePath(): string {

    $filename = basename($this->permFilePath);
    return REL_POST_UPLOAD_DIR . $filename;

  }








} //end class

?>
