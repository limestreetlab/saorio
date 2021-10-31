<?php

if (!$isLoggedIn) {
  header( "Location: " .  SITE_ROOT_URL);
  exit();
}

$friends = $userObj->getFriends(); //get a list of friends
$numberOfFriends = count($friends);

$viewLoader->load("friends_list_start.html")->bind(["firstname" => $firstname, "numberOfFriends" => $numberOfFriends])->render();

//loop block for each user
foreach ($friends as $friend) { 

  //data about this friend
  $hisProfileData = $friend->getProfile(true)->getData();
  $hisUsername = $hisProfileData["user"];
  $hisFirstname = $hisProfileData["firstname"];
  $hisLastname = $hisProfileData["lastname"];
  $hisProfilePicture = $hisProfileData["profilePictureURL"];
  $friendship = new Friendship($user, $hisUsername);
  $friendSinceEpoch = $friendship->getTimestamp();
  $friendSince = (new DateTime("@$friendSinceEpoch"))->format("M Y");
  $notes = $friendship->getNotes();
  $following = $friendship->getIsFollowing();

  $viewData = ["picture" => $hisProfilePicture, "username" => $hisUsername, "firstname" => $hisFirstname, "lastname" => $hisLastname, "friendSince" => $friendSince, "notes" => $notes, "following" => $following];

  $viewLoader->load("friends_card.html")->bind($viewData)->render();

}

$viewLoader->load("error_toast.html")->render(); //toast for errors
$viewLoader->load("friends_list_end.html")->render();


?>

<script src="js/friends.js"></script>