<?php

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

$userObj = new User($user);
$friends = $userObj->getFriends(); //get a list of friends
$numberOfFriends = count($friends);

$viewLoader->load("friends_list_start.phtml")->bind(["firstname" => $firstname, "numberOfFriends" => $numberOfFriends])->render();

//loop block for each user
foreach ($friends as $friend) { 

  //info about this friend
  $hisProfile = $friend->getProfile(true); 
  $hisProfileData = $hisProfile->getData();
  $hisUsername = $hisProfileData["user"];
  $hisFirstname = $hisProfileData["firstname"];
  $hisLastname = $hisProfileData["lastname"];
  $hisProfilePicture = $hisProfileData["profilePictureURL"];

  $viewLoader->load("friends_card.phtml")->bind(["picture" => $hisProfilePicture, "fullname" => $hisFirstname . "" . $hisLastname])->render();

}

$viewLoader->load("friends_list_end.phtml")->render();


?>