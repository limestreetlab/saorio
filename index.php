<?php
//landing page for the app, responsible for loading requested pages

require_once "./includes/ini.php"; //SITE_ROOT, INCLUDE_DIR are defined in config.php and imported through ini.php, so cannot be used before including ini.php
require_once INCLUDE_DIR . "functions.php"; //import functions

//html codes for landing page
$landingContents = "<main class='container'>
<div class='row justify-content-center'>
<div class='col-12' id='landing-img'></div>
<div class='col-10 offset-2'><h2 class='text-primary'>$appName</h2><h5>Connect with friends and the world around you on $appName.</h5></div>
</div>
</main>
";

//php code block for loading requested page
$reqPage = isset($_REQUEST["reqPage"]) ? $_REQUEST["reqPage"] : null; //set $reqPage to page requested
require_once INCLUDE_DIR . "header.php"; //must be after $reqPage is set, as $reqPage is used in setting .active nav-item

$protectedPage = ["posts", "profile", "friends", "members", "messages"]; //pages that are considered protected (loggin needed)
$isProtectedPage = in_array($reqPage, $protectedPage); //boolean to indicate if page requested is a protected page

if ($reqPage == null) { //page not requested

  echo "<br><br>";
  echo $landingContents;

} else { //a page is requested

  if ( $isProtectedPage == false || ($isProtectedPage && $isLoggedIn) ) { //authorized to requested page
    require_once SITE_ROOT . "pages/$reqPage.php";
  } else { //not logged in but requested page is protected
    echo "<span class='info'> Please log in to proceed.</span>";
  }

}

require_once INCLUDE_DIR . "footer.php";

?>
