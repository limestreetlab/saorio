<?php

require_once INCLUDE_DIR . "queries.php";
require_once CLASS_DIR . "BasicProfile.php";

//full profile class
class FullProfile extends BasicProfile {

  public $about;
  public $gender;
  public $dob;
  public $age;
  public $location;
  public $job;
  public $company;
  public $school;
  public $major;
  public $interests;
  public $quote;
  public $url;
  public $email;

  public function __construct($user) {

    parent::__construct($user);
    
  
  }


}

?>