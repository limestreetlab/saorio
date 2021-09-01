
<?php


$viewLoader->load("login_form.phtml")->render();

if ( isset($_REQUEST["user"], $_REQUEST["password"]) ) {

  $user = $_REQUEST["user"]; 
  $password = $_REQUEST["password"]; 
  
  $login = new Login($user, $password);
  $isVerified = $login->verify(); //check if credentials are valid
  $loginMessages = $login->getMessages();

  if ($isVerified) { //if valid credentials

    $basicProfile = new BasicProfile($user);
    $firstname = $basicProfile->getData()["firstname"];

    $_SESSION["user"] = $user; //set session variable
    $_SESSION["firstname"] = $firstname; //set session variable
    
  } 

  foreach ($loginMessages as $loginMessage) {
    echo $loginMessage;
  }


}

?>