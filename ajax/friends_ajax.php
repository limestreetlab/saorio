<?php
//PHP Script to support members.js, which works for members.php

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 

if ( isset($_REQUEST["unfriend"]) ) {

  $unfriend = $_REQUEST["unfriend"];
  $friendship = new Friendship($user, $unfriend);

  $success = $friendship->remove();

  echo json_encode(["success" => $success]);   
  exit();

}

?>