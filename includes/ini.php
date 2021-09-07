<?php
/*
initialize autoloader for classes, variables for app-wide uses
*/

require_once "config.php"; //load config params

session_start(); //setting up a session

//autoloader for classes, 
spl_autoload_register( function ($classname) {

    require_once CLASS_DIR . $classname . ".php"; 

});

//variables declaration
$viewLoader = new Template(); //Template object for use by pages

if (isset($_SESSION["user"])) { //if user session variable exists = loggedin

    $user = $_SESSION["user"];
    $firstname = $_SESSION["firstname"];
    $userObj = new User($user); //User obj for current user, objects aren't set in session variables
    $isLoggedIn = true;

} else {

    $user = "Guest";
    $isLoggedIn = false;

}



?>