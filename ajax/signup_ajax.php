<?php
//query database to check username availability for signup page
header("Content-Type: application/json"); //return json output

require_once "./../includes/ini.php"; //rel path to ini.php

$mysql = MySQL::getinstance(); //object for mysql database access

if (isset($_REQUEST["username"])) {
  
  $user = filter_var(trim($_REQUEST["username"]), FILTER_SANITIZE_STRING); //sanitize the user input
  $result = $mysql->request($mysql->readMembersTableQuery, [":user" => $user]); 
  
  if (count($result)) { //if result isn't empty, so username already exists
   
    $availability = false;
    
  } else { //username is available
    
    $availability = true;
  
  }
  
  echo json_encode(["availability" => $availability]);
  exit();

}

if (isset($_REQUEST["email"])) {

  $email = filter_var(trim($_REQUEST["email"]), FILTER_SANITIZE_STRING); //sanitize user input
  $query = "SELECT * FROM members where email = '$email'"; //SQL to select if any data exist for this email
  $result = $mysql->request($query); 

  if (count($result)) { //if result isn't empty, so email already exists
   
    $emailExists = true;
    
  } else { 
    
    $emailExists = false;
  
  }

  echo json_encode(["emailExists" => $emailExists]);
  exit();

}


?>