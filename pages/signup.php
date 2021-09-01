<?php


if (isset($_SESSION["user"])) {
  session_destroy();
}

$viewLoader->load("signup_form.phtml")->render();

if ( isset($_REQUEST["user"], $_REQUEST["password"], $_REQUEST["email"], $_REQUEST["firstname"], $_REQUEST["lastname"]) ) {
  
  $signup = new Signup($_REQUEST["user"], $_REQUEST["password"], $_REQUEST["passwordRepeat"], $_REQUEST["email"], $_REQUEST["firstname"], $_REQUEST["lastname"]);
  $signupResult = $signup->register();
  $signupMessages = $signup->getMessages();

  foreach ($signupMessages as $message) {
    echo $message;
  }

}

?>
