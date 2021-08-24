<?php

if (isset($_SESSION["user"])) {

  session_unset();
  session_destroy();
  header( "Location: " .  REL_SITE_ROOT);
  exit;

} else {

  echo "<div class='alert alert-warning'>You are not logged in.</div>";

}

?>