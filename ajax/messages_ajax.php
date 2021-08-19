<?php
//PHP Script to support messages.js, which works for messages.php

header("Content-Type: application/json"); //return json output
    
require_once INCLUDE_DIR . "ini.php"; 
require_once INCLUDE_DIR . "functions.php";
require_once INCLUDE_DIR . "queries.php";

//Script to retrieve and return a whole conversation between two persons to the Ajax caller
//who is the person I (user) has had a conversation with
//output is ["user" => user, "conversation" => array of each conversation]. User gives the username of the currently logged in user
//output is ["user": user, "conversation": [ ["timeElapsed" => v, "sender" => v, "recipient" => v, "message" => v], ["timeElapsed" => v, "sender" => v, "recipient" => v, "message" => v], ...] ]
if ( isset($_REQUEST["chatRetrieve"], $_REQUEST["chatWith"]) ) {

  unset($_SESSION["lastChatUpdateTime"]); //unset session variable lastChatUpdateTime which is used in updating each conversation after one is loaded
  $who = $_REQUEST["chatWith"]; //the user this conversation is with
  
  $conversation = queryDB($getMyConversationWithQuery, [":who" => $who, ":me" => $user]); //get my entire conversation with $who 
  $numberOfMessages = count($conversation); //how many individual message we exchanged
  $messages = []; //array to store each message exchanged

  for ($i = 0; $i < $numberOfMessages; $i++) {
    $conversation[$i]["timeElapsed"] = getDateTimeElapsed($conversation[$i]["timestamp"]); //add a new key-value to represent how long has passed since timestamp
    array_push($messages, $conversation[$i]); //append this one conversation to the entire conversation array
  }

  $result = ["user" => $user, "conversation" => $messages]; //array of arrays  
  echo json_encode($result); //return
  exit();

}

//Script for sending a message. 
//Receive the message and intended recipient from Ajax call
//Use current logged in user as sender and make timestamp here
if ( isset($_REQUEST["sendMessage"], $_REQUEST["message"], $_REQUEST["recipient"]) ) {

  $message = filter_var(trim($_REQUEST["message"]), FILTER_SANITIZE_STRING);
  $recipient = $_REQUEST["recipient"];
  $sender = $user;
  
  $result = sendMessage($sender, $recipient, $message);
  $success = $result["success"];

  echo json_encode(["success" => $success]); //return status
  exit();

}

//Script for updating the screen for new messages sent or received
//It uses a session variable to timestamp the last conversation update and retrieve any messages after that timestamp for display
//The requesting Ajax uses GET request with ?chatUpdate=[true/false]&chatWith=[username] to flag an update request along with whose the chat is with

if ( isset($_REQUEST["chatUpdate"], $_REQUEST["chatWith"]) ) {
  
  $who = $_REQUEST["chatWith"];
  $now = time(); //timestamp now
  $lastChatUpdateTime = isset($_SESSION["lastChatUpdateTime"]) ? $_SESSION["lastChatUpdateTime"] : $now; //session var, set for each conversation and unset when a new one clicked on, assign last update timestamp to now if it's null
  
  $conversation = queryDB($getMyConversationWithSinceQuery, [":who" => $who, ":me" => $user, ":since" => $lastChatUpdateTime]); //get the conversation since last timestamp 
  
  //if there is any conversation, process and return to caller, using a boolean in json to flag new chats
  if ($conversation) {
    $numberOfMessages = count($conversation); //number of messages since last timestamp
    $messages = []; //array to store new messages since

    for ($i = 0; $i < $numberOfMessages; $i++) {
      $conversation[$i]["timeElapsed"] = getDateTimeElapsed($conversation[$i]["timestamp"]); //add a new key-value to represent how long has passed since timestamp
      array_push($messages, $conversation[$i]); //append this one conversation to the entire conversation array
    }

    $result = ["hasNewChats" => true, "user" => $user, "conversation" => $messages]; //array of arrays  
    //echo json_encode($result); 

  } else {
    $result = ["hasNewChats" => false]; //flag no new chats
  }
  
  $_SESSION["lastChatUpdateTime"] = $now; //update the session var timer

  echo json_encode($result);
  exit();

}



?>