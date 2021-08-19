<?php
/*initialize database connection and user-related variables.
initialized 1. $dbh for database, 2. $user and $isLoggedIn for username and logged in status
*/

require_once $SERVER["DOCUMENT_ROOT"] . "includes/config.php"; //load config params

session_start(); //setting up a session

//connecting to database
try {
    $dsn = "mysql:host=". DB_HOST .";dbname=". DB_NAME .";port=". DB_PORT;
    $dbh = new PDO($dsn, DB_USER, DB_PASSWORD);
} catch (PDOException $ex) {
    die("Connection failed: " . $ex->getMessage());
}

//setting app-wide session variables
if (isset($_SESSION["user"])) { //if user session variable already exists
    $user = $_SESSION["user"];
    $firstname = $_SESSION["firstname"];
    $isLoggedIn = true;
} else {
    $user = "Guest";
    $isLoggedIn = false;
}


?>