<?php 
/*containing configuration parameters, 
*/

$appName = "Saorio"; //pointed to in places like header and landing page

//directory constants
define("SITE_ROOT", $_SERVER["DOCUMENT_ROOT"] . "/Saorio/"); //filesystem path to site root
define("INCLUDE_DIR", SITE_ROOT . "includes/"); //filesystem path to includes directory
define("PROFILE_UPLOAD_DIR", SITE_ROOT . "uploads/profiles/"); //filesystem path to profile uploads directory
define("POST_UPLOAD_DIR", SITE_ROOT . "uploads/posts/"); //filesystem path to post uploads directory
define("CLASS_DIR", SITE_ROOT . "classes/"); //filesystem path to the classes directory
define("TEMPLATE_DIR", SITE_ROOT . "templates/"); //filesystem path to the html templates directory
define("SITE_ROOT_URL", "/Saorio/"); //web path (URL) to root
define("PROFILE_UPLOAD_DIR_URL", SITE_ROOT_URL . "uploads/profiles/"); //web path (URL) to profile uploads directory
define("POST_UPLOAD_DIR_URL", SITE_ROOT_URL . "uploads/posts/"); //web path (URL) to post uploads directory


?>