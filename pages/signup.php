<?php


if (isset($_SESSION["user"])) {
  session_destroy();
}


if ( isset($_REQUEST["user"], $_REQUEST["password"], $_REQUEST["email"], $_REQUEST["firstname"], $_REQUEST["lastname"]) ) {
  
  $signup = new Signup($_REQUEST["user"], $_REQUEST["password"], $_REQUEST["passwordRepeat"], $_REQUEST["email"], $_REQUEST["firstname"], $_REQUEST["lastname"]);
  $signupResult = $signup->register();
  $success = $signupResult[0]; //[0] is success, [1] is errorCodes[]
  $errorCodes = $signupResult[1];  

}

$viewLoader->load("signup_form.html")->bind(["success" => $success, "errorCodes" => $errorCodes])->render();


?>
