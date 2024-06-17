
<?php


//user and password data received from login form
if ( isset($_REQUEST["user"], $_REQUEST["password"]) ) {

  //variables of what's submitted as is
  $user = $_REQUEST["user"]; 
  $password = $_REQUEST["password"]; 
  
  $login = new Login($user, $password); //Login object to encapsulate
  $message = null; //message for the calling frontend view to display
  

  try {

    $login->checkUsername()->verifyPassword();

    $msg = 0;

    $basicProfile = new BasicProfile($user);
    $firstname = $basicProfile->getData()["firstname"];
    
    //set session variables to indicate valid login
    $_SESSION["user"] = $user; 
    $_SESSION["firstname"] = $firstname; 

  } catch (Exception $ex) {

    $msg = $login->getError();

  }

}


$viewLoader->load("login_form.html")->bind(["message" => $msg])->render();


?>