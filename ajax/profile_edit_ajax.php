<?php
//PHP Script to serve AJAX requests, for updating a user profile data to the database
//this script receives profile update data having fields and values and loop them one-by-one for sending to database
//in the update process, success/failure can occur for each data point, so it records a boolean for each field and when fails, a defined errorCode to flag what's wrong
//it returns a json string [success, [errorCodes], [newData]] for a success boolean, array of error codes which is empty if no failure, array of updated data values
//errors have defined as -1 for system failure, 1 for file upload size over, 2 for file upload type error, 

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 

$profile = new FullProfile($user); //profile object to update
$success = []; //to hold update success/failure for each field/value update
$errors = []; //to hold error codes for each field/value update when fails
$newData = []; //to hold the updated data for this field in an update

//loop through all request parameter(s) to update fields one-by-one
foreach($_POST as $field => $value) {

  switch($field) {

    case "photo": //photo upload request received
      
      $file = $_FILES['file-upload']; //the upload form file input uses id file-upload
      $profilePhoto = new UploadedProfileImageFile($file);
      if ($profilePhoto->upload(true)) { //file upload successful
        array_push($success, true);
        $newAbsPath = $profilePhoto->getFilePath(); //get this file's new permanent absolute path
        $newRelPath = $profile->convertPicturePathAbs2Rel($newAbsPath); //convert abs path to rel path, calling Profile's static method
        array_push($newData, $newRelPath); //new data to reflect for a photo is its relative path
      } else { //file upload failed
        array_push($success, false);
        $errors = array_merge($errors, $profilePhoto->getErrors()); //ImageFile obj getErrors() returns an array of error codes
      }
      break;

    case "city":

      if ($profile->updateCity($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "country": 

      if ($profile->updateCountry($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;
      
    case "gender": 

      if ($profile->updateGender($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;
       
    case "dob": //if date of birth, $value is an array [yyyy, mm, dd] or null

      if (is_array($value)) { //not a null input, [yyyy, mm, dd] format

        if ($profile->updateDob($value[0], $value[1], $value[2])) {
          array_push($success, true);
          array_push($newData, $value);
        } else { 
          array_push($success, false);
          array_push($errors, -1);
        } 
        
      } else { //null input

        if ($profile->updateDob(0, 0, 0)) {
          array_push($success, true);
          array_push($newData, $value);
        } else { 
          array_push($success, false);
          array_push($errors, -1);
        } 

      }
      
      break;

    case "job": 

      if ($profile->updateJob($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "company": 

      if ($profile->updateCompany($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      } 
      break;

    case "major":

      if ($profile->updateMajor($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "school":

      if ($profile->updateSchool($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "about":

      if ($profile->updateAbout($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;
    
    case "interests":
      
      if ($profile->updateInterests($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "quote":
      
      if ($profile->updateQuote($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "email":
      
      if ($profile->updateEmail($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "website":
      
      if ($profile->updateWebsite($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

    case "socialmedia":
      
      if ($profile->updateSocialMedia($value)) {
        array_push($success, true);
        array_push($newData, $value);
      } else { 
        array_push($success, false);
        array_push($errors, -1);
      }
      break;

  } //end switch

}//end foreach

//return json [success: bool, error: array of error codes, newData: mixed of updated data of this request]
echo json_encode( [ "success" => !in_array(false, $success), "errors" => $errors, "newData" => $newData ] ); 
exit();

?>