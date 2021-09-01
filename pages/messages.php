<?php

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

//REQUEST variable for selecting a particular chatter in list
if (isset($_REQUEST["select"])) { 
  $highlight = $_REQUEST["select"];
} else {
  $highlight = null;
}

$viewLoader->load("messages_list_start.phtml")->bind(["highlight" => $highlight])->render(); //start of view

$userObj = new User($user);
$chatters = $userObj->getChatWith(); //retrieve list of conversations has had with

foreach ($chatters as $chatter) { //for each User obj in the list

  //generate data and assign to variables
  $profile = $chatter->getProfile(true);
  $profileData = $profile->getData();
  $username = $profileData["user"];
  $fullname = $profileData["firstname"] . " " . $profileData["lastname"];
  $picture = $profileData["profilePictureURL"]; //root-relative path
  $conversation = $userObj->getConversationWith($username);
  $newestMessage = $conversation->getNewestMessage();
  $messageData = $newestMessage->read();
  $lastMessageSummary = substr($messageData["message"], 0, 100) . "...";
  $lastMessageTime = $messageData["timeElapsed"];

  //data array to bind
  $data = ["chatWith" => $username, "picture" => $picture, "name" => $fullname, "message" => $lastMessageSummary, "ago" => $lastMessageTime];

  $viewLoader->load("messages_list.phtml")->bind($data)->render();

}

$viewLoader->load("messages_list_end.phtml")->render();

$viewLoader->load("messages_conversation_display.phtml")->render(); //end of view

?>

<!--accompanying js-->
<script src="js/messages.js"></script>

