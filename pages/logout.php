<?php
//ADD a header to redirect back to index.php with a logout msg to be displayed in index
if (isset($_SESSION["user"])) {
  session_destroy();
  echo "You have been successfully logged out. Good bye.";
} else {
  echo "<div class='alert alert-warning'>You are not logged in.</div>";
}

?>