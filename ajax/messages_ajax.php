<?php
//PHP Script to support messages.js, which works for messages.php

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php
require_once CLASS_DIR . "Conversation.php";
require_once CLASS_DIR . "Message.php";


//Script to retrieve and return a whole conversation between two persons to the Ajax caller
//who is the person I (user) has had a conversation with
//output is ["user" => user, "conversation" => array of each conversation]. User gives the username of the currently logged in user
//output is ["user": user, "conversation": [ ["timeElapsed" => v, "sender" => v, "recipient" => v, "message" => v], ["timeElapsed" => v, "sender" => v, "recipient" => v, "message" => v], ...] ]
if ( isset($_REQUEST["chatRetrieve"], $_REQUEST["chatWith"]) ) {

  unset($_SESSION["lastChatUpdateTime"]); //unset session variable lastChatUpdateTime which is used in updating each conversation after one is loaded
  $chatWith = $_REQUEST["chatWith"]; //the user this conversation is with
  
  $conversation = new Conversation($user, $chatWith);
  $messages = $conversation->getMessages(); //array of Message objects
  
  $result = ["user" => $user, "conversation" => $messages]; //array of arrays  
  echo json_encode($result); //return
  exit();

}

//Script for sending a message. 
//Receive the message and intended recipient from Ajax call
//Use current logged in user as sender and make timestamp here
if ( isset($_REQUEST["sendMessage"], $_REQUEST["message"], $_REQUEST["recipient"]) ) {

  $message = $_REQUEST["message"];
  $recipient = $_REQUEST["recipient"];
  $sender = $user;
  
  $messageObj = new Message($sender, $recipient, time(), $message);
  $success = $messageObj->send();

  echo json_encode(["success" => $success]); //return status
  exit();

}

//Script for updating the screen for new messages sent or received
//It uses a session variable to timestamp the last conversation update and retrieve any messages after that timestamp for display
//The requesting Ajax uses GET request with ?chatUpdate=[true/false]&chatWith=[username] to flag an update request along with whose the chat is with

if ( isset($_REQUEST["chatUpdate"], $_REQUEST["chatWith"]) ) {
  
  $chatWith = $_REQUEST["chatWith"];
  $now = time(); //timestamp now
  $lastChatUpdateTime = isset($_SESSION["lastChatUpdateTime"]) ? $_SESSION["lastChatUpdateTime"] : $now; //session var, set for each conversation and unset when a new one clicked on, assign last update timestamp to now if it's null
  
  $newConversation = new Conversation($user, $chatWith);
  $newMessages = $newConversation->getMessagesSince($lastChatUpdateTime); //array of Message objects
  
  $result = ["user" => $user, "newMessages" => $newMessages]; //array of arrays  
  echo json_encode($result); //return

  $_SESSION["lastChatUpdateTime"] = $now; //update the session var timer

  exit();

}



?>