<?php
//landing page for the app, responsible for loading requested pages

require_once "./includes/ini.php"; //SITE_ROOT, INCLUDE_DIR are defined in config.php and imported through ini.php, so cannot be used before including ini.php
require_once INCLUDE_DIR . "functions.php"; //import functions
require_once CLASS_DIR . "Template.php";

ob_start(); //turn on the output buffer

$header = new Template("header.phtml", ["appName" => $appName]);
echo $header->render();

$navbar = new Template("navbar_open.phtml");
echo $navbar->render();

//php code block for loading requested page
$reqPage = isset($_REQUEST["reqPage"]) ? $_REQUEST["reqPage"] : null; //set $reqPage to page requested
require_once INCLUDE_DIR . "navbarMenu.php"; //must be after $reqPage is set, as $reqPage is used in setting .active nav-item

$navbar_end = new Template("navbar_close.phtml");
echo $navbar_end->render();

$protectedPage = ["posts", "profile", "friends", "members", "messages"]; //pages that are considered protected (loggin needed)
$isProtectedPage = in_array($reqPage, $protectedPage); //boolean to indicate if page requested is a protected page

if ($reqPage == null) { //page not requested

  $landing_hero = new Template("landing_hero.phtml", ["appName" => $appName]);
  echo $landing_hero->render();

} else { //a page is requested

  if ( $isProtectedPage == false || ($isProtectedPage && $isLoggedIn) ) { //authorized to requested page
    require_once SITE_ROOT . "pages/$reqPage.php";
  } else { //not logged in but requested page is protected
    echo "<span class='info'> Please log in to proceed.</span>";
  }

}

$footer = new Template("footer.phtml", ["year" => date("Y")]);
echo $footer->render();

ob_end_flush();

?>
