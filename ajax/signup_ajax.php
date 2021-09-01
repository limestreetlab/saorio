<?php
//query database to check username availability for signup page
header("Content-Type: application/json"); //return json output

require_once "./../includes/ini.php"; //rel path to ini.php
require_once INCLUDE_DIR . "queryDatabase.php";

if (isset($_REQUEST["username"])) {
  $user = filter_var(trim($_REQUEST["username"]), FILTER_SANITIZE_STRING); //sanitize the user input
  $query = "SELECT * from members WHERE user = '$user'"; //ths SQL query to check if username already exists 
  $result = queryDB($query); 

  if (count($result)) { //if result isn't empty, so username already exists
   
    $availability = false;
    
  } else { //username is available
    
    $availability = true;
  
  }
  
  $output = ["availability" => $availability];
  echo json_encode($output);
  exit();

}

if (isset($_REQUEST["email"])) {

  $email = filter_var(trim($_REQUEST["email"]), FILTER_SANITIZE_STRING); //sanitize user input
  $query = "SELECT * FROM members where email = '$email'"; //SQL to select if any data exist for this email
  $result = queryDB($query); 

  if (count($result)) { //if result isn't empty, so email already exists
   
    $emailExists = true;
    
  } else { 
    
    $emailExists = false;
  
  }

  $output = ["emailExists" => $emailExists];
  echo json_encode($output);
  exit();

}


?>