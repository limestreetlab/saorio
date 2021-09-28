<?php
//PHP Script to support members.js, which works for members.php

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 


/*
serving unfriend requests
*/
if ( isset($_REQUEST["unfriend"]) ) {

  $unfriend = $_REQUEST["unfriend"];
  $friendship = new Friendship($user, $unfriend);

  $success = $friendship->remove();

  echo json_encode(["success" => $success]);   
  exit();

}

/*
serving add notes requests
*/
if ( isset($_REQUEST["notesAbout"], $_REQUEST["notes"]) ) {

  $friend = $_REQUEST["notesAbout"];
  $notes = $_REQUEST["notes"];

  $friendship = new Friendship($user, $friend);

  $success = $friendship->addNotes($notes);

  echo json_encode(["success" => $success]);   
  exit();

}

/*
serving follow friend requests
*/
if ( isset($_REQUEST["follow"]) ) {

  $friend = $_REQUEST["follow"];

  $friendship = new Friendship($user, $friend);

  $success = $friendship->toggleFollowing();

  echo json_encode(["success" => $success]);   
  exit();
  
}

?>