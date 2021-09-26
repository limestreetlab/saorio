<?php
//PHP Script to support messages.js, which works for messages.php

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 

/*
script to retrieve and return a whole conversation between two persons to the Ajax caller
output is ["user": user, "conversation": [ ["timeElapsed" => v, "sender" => v, "recipient" => v, "message" => v], ["timeElapsed" => v, "sender" => v, "recipient" => v, "message" => v], ...] ]
*/
if ( isset($_REQUEST["chatRetrieve"], $_REQUEST["chatWith"]) ) {

  unset($_SESSION["lastChatUpdateTime"]); //unset session variable lastChatUpdateTime which is used in updating each conversation after one is loaded
  
  $chatWith = $_REQUEST["chatWith"]; //the user this conversation is with
  $id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : null; //message id if is set or null if unset, set when retrieve specific message
  $numberOfMessagesToGet = 12; //how many messages to retrieve

  $conversation = new Conversation($user, $chatWith);
  $messages = is_null($id) ? $conversation->getMessages($numberOfMessagesToGet) : $conversation->getMessages($numberOfMessagesToGet, $id, false, false); 
  
  //output is ["user", ["conversation"], "total"], where user is current user's username, total is total number of messages available to be loaded
  $result = ["user" => $user, "conversation" => $messages, "total" => $conversation->getNumberOfMessages()]; 
  echo json_encode($result); //return json
  exit();

}

/*
script for sending a message. 
receive the message and intended recipient from Ajax call
use current logged in user as sender and make timestamp here
*/
if ( isset($_REQUEST["sendMessage"], $_REQUEST["message"], $_REQUEST["recipient"]) ) {

  $message = $_REQUEST["message"];
  $recipient = $_REQUEST["recipient"];
  $sender = $user;
  
  $messageObj = new Message($sender, $recipient, time(), $message);
  $success = $messageObj->send();

  echo json_encode(["success" => $success]); //return status
  exit();

}

/*
script for updating the screen for new messages sent or received
uses a session variable to timestamp the last conversation update and retrieve any messages after that timestamp for display
requesting Ajax uses GET request with ?chatUpdate=[true/false]&chatWith=[username] to flag an update request along with whose the chat is with
*/

if ( isset($_REQUEST["chatUpdate"], $_REQUEST["chatWith"]) ) {
  
  $chatWith = $_REQUEST["chatWith"];
  $now = time(); //timestamp for now
  $lastChatUpdateTime = isset($_SESSION["lastChatUpdateTime"]) ? $_SESSION["lastChatUpdateTime"] : $now; //set for each conversation and unset when a new one clicked
  
  $newMessages = (new Conversation($user, $chatWith))->getMessagesSince($lastChatUpdateTime); //array of Message objects
  
  $result = ["user" => $user, "newMessages" => $newMessages]; //array of arrays ["user", ["newMessages"]]
  echo json_encode($result); //return json

  $_SESSION["lastChatUpdateTime"] = $now; //update the session var timer

  exit();

}



?>