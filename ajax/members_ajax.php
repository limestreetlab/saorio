<?php
//PHP Script to support members.js, which works for members.php

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 

if ( isset($_REQUEST["requestFrom"], $_REQUEST["action"]) ) {
  
  //assgin variables
  $actionBy = $_SESSION["user"]; //the user doing the accept or reject
  $actionTo = $_REQUEST["requestFrom"]; //request was sent by
  $action = $_REQUEST["action"]; //>0 for accept, <0 for reject
  //create Friendship object
  $friendship = new Friendship($actionBy, $actionTo);

  if ($action > 0) {

    $friendship->confirmRequest();

  } else {

    $friendship->rejectRequest();
    
  }

  echo json_encode(["success" => true]);   
  exit();
}


?>