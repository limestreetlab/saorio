<?php
//PHP Script to support members.js, which works for members.php

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 

/*
serving friend request confirmation requests
receives confirm/reject actions from user and reflects that in database
*/
if ( isset($_REQUEST["requestFrom"], $_REQUEST["action"]) ) {
  
  //assgin variables
  $actionBy = $_SESSION["user"]; //the user doing the accept or reject
  $actionTo = $_REQUEST["requestFrom"]; //request was sent by
  $action = $_REQUEST["action"]; 
  //create Friendship object
  $friendship = new Friendship($actionBy, $actionTo);

  switch ($action) {

    case "accept": 
      $success = $friendship->confirmRequest();
      break;
    
    case "reject":
      $success = $friendship->rejectRequest();
      break;

    default:
      $success = false;

  }  

  echo json_encode(["success" => $success]);   
  exit();

}

/*
serving friend request sending requests
*/
if ( isset($_REQUEST["sendRequestTo"]) ) {

  //assign variables
  $requestFrom = $_SESSION["user"];
  $requestTo = $_REQUEST["sendRequestTo"];

  $friendship = new Friendship($requestFrom, $requestTo);
  $success = $friendship->add();

  echo json_encode(["success" => $success]);
  exit();

}

/*
serving sent friend request cancelling requests
*/
if ( isset($_REQUEST["cancelRequestTo"]) ) {

  //assign variables
  $requestFrom = $_SESSION["user"];
  $requestTo = $_REQUEST["cancelRequestTo"];

  $friendship = new Friendship($requestFrom, $requestTo);
  $success = $friendship->remove();

  echo json_encode(["success" => $success]);
  exit();

}


?>