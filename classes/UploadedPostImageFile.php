<?php
/*
concrete class for the image of an image post
note it represents the image, not the post per se
hence, a post (image post) must exist separately that contains an image (class composition)
this is enforced by requiring a post to pre-exist before a post image can be constructed using an id parameter in constructor
*/


class UploadedPostImageFile extends UploadedImageFile {

  //new variables
  const DIR = POST_UPLOAD_DIR;
  protected $filename; //filename to save to, without ext

  /*
  @Override
  constructor, inherited from super
  id to specify which post the image belongs to; here it is the id (primary key) in the database image posts table (primary key)
  a record in image posts table must pre-exist before an image file can be added
  both id and file for new creation, id only for old reference  
  */
  public function __construct(int $id, $uploadedFile = null) {

    parent::__construct($uploadedFile, $id); //super constructor
    $this->id = $id;

    if (isset($uploadedFile)) { //new file creation
        
      $this->filename = $_SESSION["user"] . filemtime($this->tempFilePath) . mt_rand(0, 99); //<username><timestamp><0-99> as filename, where timestamp is unix upload time

    } else { //existing reference

      if ( !$this->mysql->request($this->mysql->readImagePostImageQuery, [":id" => "$this->id"]) ) { //id defined is database table primary key, existence checked by database query
        array_push($this->errorCodes, 4);
        throw new Exception("the provided id " . $this->id . " cannot be found.");
      }

      $this->permFilePath = $this->mysql->request($this->mysql->readImagePostImageQuery, [":id" => "$this->id"])[0]["image"];
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

      array_push($this->errorCodes, -1);
      error_log("Cannot persist a post image upload: " . $ex->getMessage());
      return false;

    }

  }

  /*
  @Override
  */
  public function upload(): bool {

    try {

      $this->setPermFilePath(self::DIR, $this->filename)->checkFile()->move();
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

      $this->mysql->request($this->mysql->deleteImagePostQuery, [":id" => "$this->id"]);

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
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

  /*
  id getter
  */
  public function getId(): int {

    return $this->id;

  }








} //end class

?>
