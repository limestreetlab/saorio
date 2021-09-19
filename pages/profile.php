
<?php
//done for now, add profile-edit JS and AJAX later

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

if ( isset($_REQUEST["viewProfile"]) ) {

  //retrieve profiel data of the user to view
  $view = $_REQUEST["viewProfile"];
  $viewObj = new User($view);
  $profile = $viewObj->getProfile(false);
  extract($profile->getData()); //use key names as variable names
  $profileData = ["picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "city" => $city, "country" => $country, "gender" => $gender, "age" => $age, "dob" => is_null($dob) ? null : (new DateTime("@$dob"))->format("m-d-Y"), "job" => $job, "company" => $company, "major" => $major, "school" => $school, "about" => $about, "interests" => $interests, "quote" => $quote, "email" => $email, "website" => $website, "socialmedia" => $socialmedia];
  $viewLoader->load("profile_view.html")->bind($profileData)->render(); //load profile view

} else {

  //retrieve profile data of current user
  $profile = $userObj->getProfile(false);
  extract($profile->getData()); //use key names as variable names
  $profileData = ["picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "city" => $city, "country" => $country, "gender" => $gender, "age" => $age, "dob" => is_null($dob) ? null : (new DateTime("@$dob"))->format("m-d-Y"), "job" => $job, "company" => $company, "major" => $major, "school" => $school, "about" => $about, "interests" => $interests, "quote" => $quote, "email" => $email, "website" => $website, "socialmedia" => $socialmedia];

  $viewLoader->load("profile_edit.html")->bind($profileData)->render(); //load profile edit view

}

?>
