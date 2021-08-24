<?php 
/*containing configuration parameters, 
such as app's name and database credentials.
*/

$appName = "Saorio"; //pointed to in places like header and landing page

//database credentials, adjust to suit server settings
define("DB_HOST", "127.0.0.1");
define("DB_NAME", "saorio");
define("DB_USER", "root");
define("DB_PASSWORD", "007hk007");
define("DB_PORT", "3307");

define("SITE_ROOT", $_SERVER["DOCUMENT_ROOT"] . "/Saorio/"); //absolute path to site root
define("INCLUDE_DIR", SITE_ROOT . "includes/"); //absolute path to includes directory
define("UPLOAD_DIR", SITE_ROOT . "uploads/"); //absolute path to uploads directory
define("CLASS_DIR", SITE_ROOT . "classes/"); //absolute path to the classes directory
define("TEMPLATE_DIR", SITE_ROOT . "templates/"); //absolute path to the html templates directory
define("REL_SITE_ROOT", "/Saorio/"); //root-relative path
define("REL_UPLOAD_DIR", REL_SITE_ROOT . "uploads/"); //root-relative path to uploads directory

?>