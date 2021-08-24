
<br><br>

<section class="container">

<form method="post" action="index.php?reqPage=login">

  <div class="row mb-3 justify-content-center">
    <div class="col-6">
      <input type="text" class="form-control" name="user" id="user" placeholder="Username" minlength="5" maxlength="20" pattern="[a-z0-9]{5,20}" title="username is between 5 and 20 characters long and include only lowercase letters and digits" required> 
    </div>
  </div>

  <div class="row mb-3 justify-content-center">
    <div class="col-6">
      <input type="password" class="form-control" name="password" id="password" name="password" minlength="5" maxlength="20" placeholder="Password" required>
    </div>
  </div>
  
  <div class="row mb-3 justify-content-center">
    <div class="col-6">
      <button type="submit" class="btn btn-primary col-12" id="signupBtn">Log in</button>
    </div>
  </div>

</form>
</section>

<?php

require_once INCLUDE_DIR . "queries.php";
require_once CLASS_DIR . "Login.php";

if ( isset($_REQUEST["user"], $_REQUEST["password"]) ) {

  $user = $_REQUEST["user"]; 
  $password = $_REQUEST["password"]; 
  
  $login = new Login($user, $password);
  $isVerified = $login->verify(); //check if credentials are valid
  $loginMessages = $login->getMessages();

  if ($isVerified) { //if valid credentials
    $firstname = ucfirst(strtolower(queryDB("SELECT firstname FROM profiles WHERE user = '$user'")[0]["firstname"])); //get the firstname
    $_SESSION["user"] = $user; //set session variable
    $_SESSION["firstname"] = $firstname; //set session variable
  } 

  foreach ($loginMessages as $loginMessage) {
    echo $loginMessage;
  }


}

?>