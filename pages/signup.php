
<!--the signup form-->
<br><br>
<section class="container">
<div class="row justify-content-center mb-3">
  <div class="col-6 text-center"><span class="h4">Sign Up</span><div class='text-secondary'>It's quick and easy.</div></div>
</div>

<form method="post" action="index.php?reqPage=signup">

  <div class="row mb-0 justify-content-center">
    <div class="col-6">
      <input type="text" class="form-control" id="user" name="user" minlength="5" maxlength="20" pattern="[a-z0-9]{5,20}" title="username must be between 5 and 20 characters long and include only lowercase letters and digits" onBlur="checkUsername()" placeholder="Pick a username" required>
    </div>
  </div>

  <div class="row mb-3 justify-content-center">
    <div class="col-6">
      <span id="availability" class="form-text" rows="1"></span>
    </div>
  </div>

  <div class="row mb-3 justify-content-center">
    <div class="col-6">
      <input type="password" class="form-control" id="password" name="password" minlength="5" maxlength="20" placeholder="Password" required>
    </div>
  </div>

  <div class="row mb-3 justify-content-center">
    <div class="col-6">
      <input type="email" class="form-control" id="email" name="email" minlength="8" maxlength="40" placeholder="Email" required>
    </div>
  </div>

  <div class="row mb-3 justify-content-center">
    <div class="col-3">
      <input type="text" class="form-control" id="firstname" name="firstname" minlength="2" maxlength="20" placeholder="First Name" required>
    </div>
    <div class="col-3">
      <input type="text" class="form-control" id="lastname" name="lastname" minlength="2" maxlength="20" placeholder="Last Name" required>
    </div>
  </div>
  
  <div class="row justify-content-center mb-3">
    <div class="col-6">
      <button type="submit" class="btn btn-primary col-12" id="signupBtn">Sign Up</button>
    </div>
  </div>

</form>
</section>
<!--end of signup form-->

<script src="js/signup.js"></script><!--script to ajax-check username availability-->

<?php

require_once INCLUDE_DIR . "queries.php";

if (isset($_SESSION["user"])) {
  session_destroy();
}

if ( isset($_REQUEST["user"], $_REQUEST["password"]) ) {
  $user = strtolower(filter_var(trim($_REQUEST["user"]), FILTER_SANITIZE_STRING)); //usernames are lowercase
  $password = filter_var(trim($_REQUEST["password"]), FILTER_SANITIZE_STRING); //raw password
  $password_hash = password_hash($password, PASSWORD_DEFAULT); //hashed password
  $email = filter_var(trim($_REQUEST["email"]), FILTER_SANITIZE_STRING);
  $firstname = filter_var(trim($_REQUEST["firstname"]), FILTER_SANITIZE_STRING);
  $lastname = filter_var(trim($_REQUEST["lastname"]), FILTER_SANITIZE_STRING);
  
  //input data into database
  queryDB($signupQuery, [":user" => $user, ":password" => $password_hash, ":email" => $email]); //create a new user
  queryDB($initializeProfileQuery, [":user" => $user, ":firstname" => $firstname, ":lastname" => $lastname]); //initialize a profile for this new user
  
  echo "<div class='alert alert-success'>Account has been successfully created. Please log in.</div>";
}

?>
