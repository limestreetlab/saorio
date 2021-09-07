<?php
//PHP Script to support profile_edit.js, which handles profile editing for the user's profile page
//this script receives data (fields and values) to be updated and loop them one-by-one for sending to database

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 

$profile = new FullProfile($user); //profile object to update
$success = []; //to hold successful update for each field/value update

//loop through all request parameter(s)
foreach($_REQUEST as $field => $value) {

  switch($field) {

    case "city":

      $profile->updateCity($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "country": 

      $profile->updateCountry($value) ? array_push($success, true) : array_push($success, false);
      break;
      
    case "gender": 

      $profile->updateGender($value) ? array_push($success, true) : array_push($success, false);
      break;
       
    case "dob": //if date of birth, $value is an array [yyyy, mm, dd]

      $profile->updateDob($value[0], $value[1], $value[2]) ? array_push($success, true) : array_push($success, false);
      break;

    case "job": 

      $profile->updateJob($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "company": 

      $profile->updateCompany($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "major":

      $profile->updateMajor($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "school":

      $profile->updateSchool($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "about":

      $profile->updateAbout($value) ? array_push($success, true) : array_push($success, false);
      break;
    
    case "interests":
      
      $profile->updateInterests($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "quote":
      
      $profile->updateQuote($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "email":
      
      $profile->updateEmail($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "website":
      
      $profile->updateWebsite($value) ? array_push($success, true) : array_push($success, false);
      break;

    case "socialmedia":
      
      $profile->updateSocialMedia($value) ? array_push($success, true) : array_push($success, false);
      break;

  } //end switch

}//end foreach

echo json_encode( !in_array(false, $success) ); //return false if any result is false, true if none is false
exit();

?>