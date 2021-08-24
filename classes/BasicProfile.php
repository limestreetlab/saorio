<?php

require_once INCLUDE_DIR . "queries.php";

class BasicProfile {

  public $user;
  public $firstname;
  public $lastname;
  public $profilePictureURL;

  public function __construct($user) {
    
    global $getBasicProfileQuery;

    $this->user = $user;

    $profileData = queryDB($getBasicProfileQuery, [":user" => "$this->user"])[0]; //grab fname, lname, picture of this user
    $this->firstname = $profileData["firstname"];
    $this->lastname = $profileData["lastname"];
    $absolutePath = $profileData["profilePictureURL"]; //abs path to profile picture
    $this->profilePictureURL = self::convertPhotoPath($absolutePath); //relative path

  }

  public function getData(): array {

    return ["user" => "$this->user", "firstname" => "$this->firstname", "lastname" => $this->lastname, "profilePictureURL" => $this->profilePictureURL];

  }

  public function updateProfilePicture(): bool {

    
  }


  /*
  Class function to convert an absolute path of profile photo to a relative path
  @param $path: absolute path to profile photo
  @return root-relative path to profile photo
  */
  static protected function convertPhotoPath(string $absolutePath): string {

    $filename = basename($absolutePath); //filename with ext
    $relativePath = REL_UPLOAD_DIR . "$filename"; //root relative path to photo
    return $relativePath;

  }




}

?>