<?php
//landing page for the app, responsible for loading requested pages

require_once "./includes/ini.php"; //SITE_ROOT, INCLUDE_DIR are defined in config.php and imported through ini.php, so cannot be used before including ini.php

ob_start(); //turn on the output buffer

$viewLoader->load("header.html")->bind(["appName" => $appName])->render(); //header html

$viewLoader->load("navbar_open.html")->render(); //navbar open html

//php code block for loading requested page
$reqPage = isset($_REQUEST["reqPage"]) ? $_REQUEST["reqPage"] : null; //set $reqPage to page requested

$nav = new NavMenu(); //NavMenu obj, must be after $reqPage is set as it is used in navmenu to set active page
echo $nav->getNavMenu($isLoggedIn);

$viewLoader->load("navbar_close.html")->render(); //navbar end html

$protectedPage = ["posts", "profile", "friends", "members", "messages"]; //pages that are considered protected (loggin needed)
$isProtectedPage = in_array($reqPage, $protectedPage); //boolean to indicate if page requested is a protected page

if ($reqPage == null) { //page not requested

  $viewLoader->load("index_landing.html")->bind(["appName" => $appName])->render();

} else { //a page is requested

  if ( $isProtectedPage == false || ($isProtectedPage && $isLoggedIn) ) { //authorized to requested page
    require_once SITE_ROOT . "pages/$reqPage.php";
  } else { //not logged in but requested page is protected
    echo "<span class='info'> Please log in to proceed.</span>";
  }

}

$viewLoader->load("footer.html")->bind(["year" => date("Y")])->render(); //footer html

ob_end_flush();

?>
