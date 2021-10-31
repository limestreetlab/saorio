<?php
//concrete class for profile wallpaper uploads

class UploadedWallpaperImageFile extends UploadedImageFile {

  //new variables
  const DIR = PROFILE_UPLOAD_DIR;
  protected $filename;

  /*@Override
  constructor, inherited from super
  id used to reference a created object is either its filename (with ext) or full absolute path
  */
  public function __construct($uploadedFile = null, $id = null) {

    parent::__construct($uploadedFile, $id);

    if (isset($uploadedFile)) { //new creation

      $this->filename = $_SESSION["user"] . "-wallpaper-" . filemtime($this->tempFilePath); //<username>-wallpaper-<wallpaper> as filename
  
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
      $this->useExifOrientation(); 
      
      if ($deleteExisting) { //if existing file should be removed, get the existing path, delete it after successful persistance
                  
        $oldFilePath = $this->mysql->request(MySQL::readProfileQuery, [":user" => $_SESSION["user"]])[0]["wallpaper"]; //abs path to existing file
        
        $this->persist();

        if (!empty($oldFilePath)) {
          if (!unlink($oldFilePath)) {
              error_log("Failed to delete a wallpaper file: " . $oldFilePath);
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
  function to persis wallpaper to database
  */
  protected function persist(): self {

    $params = [":wallpaper" => $this->permFilePath, ":user" => $_SESSION["user"] ];

    try {

        $this->mysql->request(MySQL::updateProfileWallpaperQuery, $params);
        return $this;

    } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        error_log("Cannot persist a wallpaper upload: " . $ex->getMessage());
        throw $ex;
        
    }

  }

  /*
  @Override
  */
  public function delete(): bool {

    try {

      $this->mysql->request(MySQL::updateProfileWallpaperToNullQuery, [":user" => $_SESSION["user"]]); //remove from db

    } catch (Exception $ex) {
      
      array_push($this->errorCodes, -1);
      return false;
  
    }

    return unlink($this->permFilePath); //removing from server 

  }

  /*
  @Override
  */
  public function getFileRelativePath(): string {

    $filename = basename($this->permFilePath); //filename with ext
    return PROFILE_UPLOAD_DIR_URL . $filename; //relative path


  }
  /*
  @Override
  */
  static public function convertFileRelativePath(string $absolutePath): string {

    $filename = basename($absolutePath); //filename with ext
    return PROFILE_UPLOAD_DIR_URL . $filename; //relative path

  }






} //end class

?>