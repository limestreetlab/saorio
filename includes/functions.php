<?php
//files to contain general functions for use throughout the app.
require_once INCLUDE_DIR . "config.php";
require_once INCLUDE_DIR . "queries.php";
/*
Helper function to query database and return the full resultset.
@param $query: the SQL query string, can be either a straight query (without any external inputs) or a prepared statement using either named parameters (:param) or positional (?)
@param $param: the values, in array, to bind to a prepared statement, [value1, value2, ...] or ["name1" => value1, "name2" => value2, ...] for positional or named params
@return full resultset of the query
*/
function queryDB(string $query, array $param=null): array {
  
  global $dbh; //reference the db handle declared in ini.php 

  if (isset($param)) { //query params provided, so a prepared statement
    
    $stmt = $dbh->prepare($query); //set up the prepared statement

    $isAssocArray = count(array_filter(array_keys($param), "is_string")) == 0 ? false : true; //boolean flag for associative array (dict, with keys) versus sequential array (list, without keys)  
    
    if ($isAssocArray) { //the prepared statement uses named parameters (:name1, :name2, ...)
      
      foreach ($param as $key => &$value) {  //bind the parameters 1-by-1
        if (substr($key, 0, 1) != ":") { //if the provided parameter isn't prefixed with ':' which is required in bindParam()
          $name = ":".$key; //prefix it with ':'
        }

        $stmt->bindParam($key, $value);
      }

    } else { //the prepared statement uses unnamed parameters (?, ?, ...) 
      
      for($i = 1; $i <= count($param); $i++) { //bind the parameters 1-by-1
        $stmt->bindParam($i, $param[$i-1]); 
      }

    } //the prepared statement has its values bound and ready for execution

    $stmt->execute();

  } else { //not a prepared statement, a straight query

    $stmt = $dbh->query($query);   

  }

  $resultset = $stmt->fetchAll(); //grab the entire resultset
  return $resultset;

}//end function

/*
Function to convert an absolute path of profile photo to a relative path
@param $path: absolute path to profile photo
@return root-relative path to profile photo
*/
function getPhotoPath(string $path): string {

  $filename = basename($path); //filename with ext
  $photoPath = REL_UPLOAD_DIR . "$filename"; //root relative path to photo
  return $photoPath;

}


/*
Function to get and display a user's picture and about contents
@param $user: username 
@return void
*/
function showProfile(string $user): void {

  $result = queryDB("SELECT * FROM profiles WHERE user='$user'"); 
  $profile = $result[0]; //the result array has only one element, of the current user
  //grabbing profile items
  $about = stripslashes($profile["about"]); //the user's about contents
  $profilePictureAbsoluteURL = $profile["profilePictureURL"]; //abs path for img src
  $profilePictureRelativeURL = getPhotoPath($profilePictureAbsoluteURL); //relative path for img src
  $firstname = ucfirst(strtolower($profile["firstname"]));
  $lastname = ucfirst(strtolower($profile["lastname"]));
  $gender = strtolower($profile["gender"]); //CHAR(1)
  switch ($gender) { //assign an icon and related pronouns for gender
    case "m":
      $genderIcon = "bi bi-gender-male";
      $pronoun = "He";
      $possessivePronoun = "His";
      break;
    case "f":
      $genderIcon = "bi bi-gender-female";
      $pronoun = "She";
      $possessivePronoun = "Her";
      break;
    default:
      $genderIcon = "bi bi-gender-ambiguous"; 
      $pronoun = "He/she";
      $possessivePronoun = "His/her";
  }
  $ageGroup = $profile["ageGroup"]; //TINYINT(1)
  switch ($ageGroup) { //assign an age variable for ageGroup
    case 1: 
      $age = "10s";
      break;
    case 2:
      $age = "20s";
      break;
    case 3: 
      $age = "30s";
      break;
    case 4:
      $age = "40s";
      break;
    case 5:
      $age ="golden age";
      break;
    default:
      $age = "secret age";
  }
  $location = ucwords(strtolower($profile["location"]));
  $job = ucfirst(strtolower($profile["job"]));
  $company = ucfirst(strtolower($profile["company"]));
  $major = $profile["major"];
  $school = $profile["school"];
  $interests = ucwords(strtolower($profile["interests"]));
  $quote = $profile["quote"];

  //display the profile items
  echo "<section class='row mb-3'><div class='col-4'>";
  if (file_exists($profilePictureAbsoluteURL)) {
    echo "<img class='rounded-circle' id='profilePicture' src='$profilePictureRelativeURL' width='200' height='200'>"; //echo the profile picture
  }
  echo "</div>"; 
  echo "<div class='col-8'>";
  echo "<div class='col-12 mb-2'><span class='h3'>$firstname $lastname</span>, <i class='$genderIcon h5'></i></div>";
  echo "<div class='col-12 mb-1'>$firstname is in the $age";
  if (!empty($location)) {
    echo " and lives in $location";
  }
  echo ".</div>";
  if (!empty($job) || !empty($company)) {
    echo "<div class='col-12 mb-1'>$pronoun works";
    if (!empty($job)) {echo " as a $job";}
    if (!empty($company)) {echo " at $company";}
    echo ".</div>";
  }
  if (!empty($major) || !empty($school)) {
    echo "<div class='col-12 mb-1'>$firstname studied";
    if (!empty($major)) {echo " $major";}
    if (!empty($school)) {echo " at $school";}
    echo ".</div>";
  }
  if (!empty($interests)) {
    echo "<div class='col-12 mb-1'>$possessivePronoun interests include $interests.</div>";
  }
  if (!empty($quote)) {
    echo "<div class='col-12 mb-1'>$possessivePronoun favorite quote is $quote.</div>";
  }
  echo "</div>"; //close the col-8 div
  echo "<section class='row mt-3'><div class='col-12 border border-primary rounded-pill'>$about</div></section>";
  
}//end function


?>