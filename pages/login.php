
<?php


//user and password data received from login form
if ( isset($_REQUEST["user"], $_REQUEST["password"]) ) {

  //variables of what's submitted as is
  $user = $_REQUEST["user"]; 
  $password = $_REQUEST["password"]; 
  
  $login = new Login($user, $password); //Login object to encapsulate
  $message = null; //message for the calling frontend view to display
  
  if (!$login->checkUsername()) { //the entered username is invalid

    $message = "invalidUser"; 

  } else { //valid username

    if (!$login->verifyPassword()) { //valid username but invalid password

      $message = "invalidPassword";
    
    } else { //valid credentials
  
      $message = "valid";
  
      $basicProfile = new BasicProfile($user);
      $firstname = $basicProfile->getData()["firstname"];
      
      //set session variables to indicate valid login
      $_SESSION["user"] = $user; 
      $_SESSION["firstname"] = $firstname; 
      
    } 

  }

}


$viewLoader->load("login_form.html")->bind(["message" => $message])->render();


?>