<?php
//PHP Script to support members.js, which works for members.php

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 
require_once CLASS_DIR . "Friendship.php";
require_once INCLUDE_DIR . "queries.php";

if ( isset($_REQUEST["requestFrom"], $_REQUEST["requestTo"], $_REQUEST["status"]) ) {
  
  $friendship = new Friendship($_REQUEST["requestTo"], $_REQUEST["requestFrom"]);
  $status = $_REQUEST["status"];

  switch($status) {
    case "confirmed":
      $friendship->confirmRequest();
      break;
    case "rejected":
      $friendship->rejectRequest();
      break;
  }

  $result = ["success" => true];   
  echo json_encode($result); 
  exit();
}


?>