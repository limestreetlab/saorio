
<?php
//done for now, add profile-edit JS and AJAX later

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

//retrieve profile data of the user
$userObj = new User($user);
$profile = $userObj->getProfile(false);
extract($profile->getData()); //use key names as variable names
$profileData = ["picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "city" => $city, "country" => $country, "gender" => $gender, "age" => $age, "job" => $job, "company" => $company, "major" => $major, "school" => $school, "about" => $about, "interests" => $interests, "quote" => $quote, "email" => $email, "website" => $website, "socialmedia" => $socialmedia];

$viewLoader->load("profile_edit.html")->bind($profileData)->render(); //load profile edit view


?>
