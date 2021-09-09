<?php


class BasicProfile {

  public $user;
  public $firstname;
  public $lastname;
  public $profilePictureURL; //relative path
  protected $mysql; //object for mysql database access

  public function __construct(string $user) {
    
    $this->user = $user;
    $this->mysql = MySQL::getinstance();
    $profileData = $this->mysql->request($this->mysql->readBasicProfileQuery, [":user" => "$this->user"])[0]; //grab fname, lname, picture of this user
    $this->firstname = $profileData["firstname"];
    $this->lastname = $profileData["lastname"];
    $this->profilePictureURL = self::convertPicturePathAbs2Rel( $profileData["profilePictureURL"] ); //get the abs-path from db and convert it to root-rel path

  }


  /*
  getter for BasicProfile data
  @return associative array containing [user, firstname, lastname, profilePictureURL]
  */
  public function getData(): array {

    return ["user" => "$this->user", "firstname" => "$this->firstname", "lastname" => $this->lastname, "profilePictureURL" => $this->profilePictureURL];

  }

  /*
  function to upload an image file as profile picture
  @see UploadedProfileImageFile
  @param an image file
  @return success
  */
  public function updateProfilePicture($imgFile): bool {

    $uploadedImg = new UploadedProfileImageFile($imgFile);
    return $uploadedImg->upload();
    
  }

  /*
  class utility to clean string inputs, by trimming, sanitizing, and replacing double whitespaces
  @param pre-clean string
  @return post-clean string
  */
  protected static function cleanString(string $input): string {
    $input = preg_replace('/\s\s+/', ' ',$input); //replace double whitespaces to single
    $input = filter_var(trim($input), FILTER_SANITIZE_STRING); //trim, sanitize
    return $input;
  }

  /*
  class utility function to convert an absolute path of profile photo to a root-relative path
  @param $path: absolute path to profile photo
  @return root-relative path to profile photo
  */
  static protected function convertPicturePathAbs2Rel(string $absolutePath): string {

    $filename = basename($absolutePath); //the filename with ext
    return REL_UPLOAD_DIR . "$filename"; //root relative path to photo

  }




}

?>