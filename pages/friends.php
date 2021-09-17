<?php

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

$friends = $userObj->getFriends(); //get a list of friends
$numberOfFriends = count($friends);

$viewLoader->load("friends_list_start.html")->bind(["firstname" => $firstname, "numberOfFriends" => $numberOfFriends])->render();

//loop block for each user
foreach ($friends as $friend) { 

  //info about this friend
  $hisProfileData = $friend->getProfile(true)->getData();
  $hisUsername = $hisProfileData["user"];
  $hisName = $hisProfileData["firstname"] . " " . $hisProfileData["lastname"];
  $hisProfilePicture = $hisProfileData["profilePictureURL"];
  $friendSinceTS = (new Friendship($user, $hisUsername))->getTimestamp();
  $friendSince = (new DateTime("@$friendSinceTS"))->format("M Y");
  $viewData = ["picture" => $hisProfilePicture, "username" => $hisUsername, "fullname" => $hisName, "friendSince" => $friendSincem, "notes" => null];

  $viewLoader->load("friends_card.html")->bind($viewData)->render();

}

$viewLoader->load("friends_list_end.html")->render();
$viewLoader->load("friends_toast.html")->render();


?>

