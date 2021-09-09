<?php
/*used to initialize necessary MySQL tables for this app, under the connection to DB saorio outlined in config.php.
Run this script before using the app.
This script simply sends table creation codes to MySQL.
Tables are members, messages, friends, profiles.
*/

require_once "./includes/config.php";
require_once INCLUDE_DIR . "ini.php";

echo "<!DOCTYPE html>
        <html><head><title>Database initialization for $appName </title></head>
            <body><h2>Creating databases, please wait...</h2>
     ";

$tables = [];

//the members table
$tablename = "members";
$columns = "user VARCHAR(20), 
password VARCHAR(100),
email VARCHAR(40) UNIQUE,
PRIMARY KEY(user),
INDEX(user(5))";
$tables["$tablename"] = $columns;


//the profiles table
$tablename = "profiles";
$columns = "user VARCHAR(20),
firstname VARCHAR(30) NOT NULL,
lastname VARCHAR(30) NOT NULL,
about VARCHAR(1000),
profilePictureURL VARCHAR(100) DEFAULT 'C:/Program Files/Ampps/www/saorio/uploads/avatar-profile.png' COMMENT 'absolute path',
profilePictureMIME VARCHAR(30) DEFAULT 'image/png' COMMENT 'full mime of image/xxx',
gender VARCHAR(10) COMMENT 'female, male, intersex',
dob INT COMMENT 'date of birth in epoch UTC',
city VARCHAR(30),
country VARCHAR(30),
job VARCHAR(30), 
company VARCHAR(30),
major VARCHAR(30),
school VARCHAR(30),
interests VARCHAR(30),
quote VARCHAR(100),
website VARCHAR(100) COMMENT 'his personal website',
socialmedia VARCHAR(100) COMMENT 'his social media account',
PRIMARY KEY(user),
index(user(5))";
$tables["$tablename"] = $columns;

//the messages table
$tablename = "messages";
$columns = "id INT UNSIGNED AUTO_INCREMENT,
timestamp INT UNSIGNED NOT NULL COMMENT 'unix epoch UTC timestamp',
sender VARCHAR(20) NOT NULL,
recipient VARCHAR(20) NOT NULL,
message VARCHAR(1000) NOT NULL,
PRIMARY KEY (id),
INDEX(sender(5)),
INDEX(recipient(5))";
$tables["$tablename"] = $columns;

//the friends table
$tablename = "friends";
$columns = " id INT(10) UNSIGNED AUTO_INCREMENT,
user1 VARCHAR(20) COMMENT 'requests are always from user1',
user2 VARCHAR(20) COMMENT 'user2 always receives requests',
status TINYINT(1) COMMENT '1 for confirmed, 2 for pending confirmation', 
PRIMARY KEY (id),
INDEX(user1(5)),
INDEX(user2(5))";
$tables["$tablename"] = $columns;

//create table for every member of $tables
$mysql = MySQL::getInstance();
foreach ($tables as $table => $columns) {
    $sql = "CREATE TABLE IF NOT EXISTS $table ($columns)";
    $mysql->request($sql);
    echo "The table $table is now in database ". DB_NAME. "<br>";
}

echo "<br> ...Table initialization completed. </body></html>";

?>
