<?php
//query database to check username availability for signup page

require_once INCLUDE_DIR . "ini.php"; 
require_once INCLUDE_DIR . "functions.php";

if (isset($_REQUEST["username"])) {
  $user = filter_var(trim($_REQUEST["username"]), FILTER_SANITIZE_STRING); //sanitize the user input
  $query = "SELECT * from members WHERE user = ?"; //ths SQL query to check if username already exists 
  $result = queryDB($query, [$user]); //call function to query database

  if (count($result)) { //if result isn't empty, so username already exists
    
    echo "false";
  
  } else { //username is available
    
    echo "true";
  
  }

}


?>