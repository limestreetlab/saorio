<?php
//query database to check username availability for signup page
header("Content-Type: application/json"); //return json output

require_once "./../includes/ini.php"; //rel path to ini.php

$mysql = MySQL::getInstance(); //object for mysql database access

if (isset($_REQUEST["username"])) {
  
  $signup = new Signup( $_REQUEST["username"] );
  $signup->checkUsername();
  
  if ( in_array(0, $signup->getErrorCodes()) ) { 
   
    $availability = false;
    
  } else { 
    
    $availability = true;
  
  }
  
  echo json_encode(["availability" => $availability]);
  exit();

}

if (isset($_REQUEST["email"])) {

  $signup = new Signup(null, null, null, $_REQUEST["email"]);
  $signup->checkEmail();

  if ( in_array(10, $signup->getErrorCodes()) ) { 
   
    $emailExists = true;
    
  } else { 
    
    $emailExists = false;
  
  }

  echo json_encode(["emailExists" => $emailExists]);
  exit();

}


?>