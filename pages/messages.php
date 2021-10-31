<?php

if (!$isLoggedIn) {
  header( "Location: " .  SITE_ROOT_URL);
  exit();
}

//REQUEST variable for selecting a particular chatter in list
if (isset($_REQUEST["select"])) { 
  $highlight = $_REQUEST["select"];
} else {
  $highlight = null;
}

$viewLoader->load("messages_list_start.html")->bind(["highlight" => $highlight])->render(); //start of view

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
  $lastMessageSummary = strlen($messageData["message"]) > 30 ? substr($messageData["message"], 0, 30) . "..." : $messageData["message"];
  $lastMessageTime = $messageData["timeElapsed"];

  //data array to bind
  $data = ["chatWith" => $username, "picture" => $picture, "name" => $fullname, "message" => $lastMessageSummary, "ago" => $lastMessageTime];

  $viewLoader->load("messages_list.html")->bind($data)->render();
  
}

$viewLoader->load("messages_list_end.html")->render();

$viewLoader->load("messages_conversation_display.html")->render(); //end of view

?>

<!--accompanying js-->
<script src="js/messages.js"></script>

