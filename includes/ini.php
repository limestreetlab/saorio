<?php
/*initialize database connection and user-related variables.
initialized 1. $dbh for database, 2. $user and $isLoggedIn for username and logged in status
*/

require_once "config.php"; //load config params

session_start(); //setting up a session

//database connection
try {
    $dsn = "mysql:host=". DB_HOST .";dbname=". DB_NAME .";port=". DB_PORT;
    $dbh = new PDO($dsn, DB_USER, DB_PASSWORD);
} catch (PDOException $ex) {
    die("Connection failed: " . $ex->getMessage());
}


//app-wide session variables and objects
if (isset($_SESSION["user"])) { //if user session variable already exists
    $user = $_SESSION["user"];
    $firstname = $_SESSION["firstname"];
    $isLoggedIn = true;
} else {
    $user = "Guest";
    $isLoggedIn = false;
}


//autoloader for classes
spl_autoload_register( function ($classname) {
    require_once CLASS_DIR . $classname . ".php";
});

//create a Template object for use across pages
$viewLoader = new Template();


?>