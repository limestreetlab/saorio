
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

if ( isset($_REQUEST["user"], $_REQUEST["password"]) ) {
  $user = filter_var(trim($_REQUEST["user"]), FILTER_SANITIZE_STRING);
  $password = filter_var(trim($_REQUEST["password"]), FILTER_SANITIZE_STRING);

  $password_hash = queryDB($loginPasswordQuery, [":user" => $user])[0]["password"]; //retrieve the hashed password of this user
  $isValidPassword = password_verify($password, $password_hash); //verify if entered password matches the hashed password
  
  if ($isValidPassword) { //if entered password matches the hash
    $data = queryDB($loginQuery, [":user" => $user]); //retrieve all data of this user
    $firstname = ucfirst(strtolower(queryDB("SELECT firstname FROM profiles WHERE user = '$user'")[0]["firstname"])); //get the firstname
    $_SESSION["user"] = $user; //set session variable
    $_SESSION["firstname"] = $firstname; //set session variable
    echo "<div class='alert alert-success'>Welcome back $firstname. Please <a href='index.php?reqPage=members&view=$user'>click here</a> to continue.</div>";
  } else {
    echo "<div class='alert alert-warning'>Invalid username/password.</div>";
  }
}

?>