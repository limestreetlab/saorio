<?php
//PHP Script to support members.js, which works for members.php

header("Content-Type: application/json"); //return json output
    
require_once INCLUDE_DIR . "ini.php"; 
require_once INCLUDE_DIR . "functions.php";
require_once INCLUDE_DIR . "queries.php";

if ( isset($_REQUEST["user1"], $_REQUEST["user2"], $_REQUEST["status"]) ) {
  $status = $_REQUEST["status"];
  $user1 = $_REQUEST["user1"];
  $user2 = $_REQUEST["user2"];

  switch($status) {
    case "confirmed":
      queryDB($confirmFriendRequestQuery, [":requestSender" => $user1, ":requestRecipient" => $user2]);
      break;
    case "rejected":
      queryDB($rejectFriendRequestQuery, [":requestSender" => $user1, ":requestRecipient" => $user2]);
      break;
  }

  $result = ["success" => true];   
  echo json_encode($result); 
  exit();
}


?>