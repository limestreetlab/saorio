<?php 
/*containing configuration parameters, 
*/

$appName = "Saorio"; //pointed to in places like header and landing page

//directory constants
define("SITE_ROOT", $_SERVER["DOCUMENT_ROOT"] . "/Saorio/"); //absolute path to site root
define("INCLUDE_DIR", SITE_ROOT . "includes/"); //absolute path to includes directory
define("PROFILE_UPLOAD_DIR", SITE_ROOT . "uploads/profiles/"); //absolute path to profile uploads directory
define("POST_UPLOAD_DIR", SITE_ROOT . "uploads/posts/"); //absolute path to post uploads directory
define("CLASS_DIR", SITE_ROOT . "classes/"); //absolute path to the classes directory
define("TEMPLATE_DIR", SITE_ROOT . "templates/"); //absolute path to the html templates directory
define("REL_SITE_ROOT", "/Saorio/"); //root-relative path
define("REL_PROFILE_UPLOAD_DIR", REL_SITE_ROOT . "uploads/profiles/"); //root-relative path to profile uploads directory
define("REL_POST_UPLOAD_DIR", REL_SITE_ROOT . "uploads/posts/"); //root-relative path to post uploads directory


?>