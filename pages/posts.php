<?php

require_once INCLUDE_DIR . "queries.php";

if (!$isLoggedIn) {
  header( "Location: " . SITE_ROOT ) ;
  exit();
}


//php and html code block for retrieving and displaying messages

?>

