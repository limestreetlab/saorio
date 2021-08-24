<?php
/*used to initialize necessary MySQL tables for this app, under the connection to DB saorio outlined in config.php.
Run this script before using the app.
This script simply sends table creation codes to MySQL.
Tables are members, messages, friends, profiles.
*/

require_once "includes/config.php";
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
email VARCHAR(30) UNIQUE,
PRIMARY KEY(user),
INDEX(user(5))";
$tables["$tablename"] = $columns;


//the profiles table
$tablename = "profiles";
$columns = "user VARCHAR(20),
firstname VARCHAR(20),
lastname VARCHAR(20),
about VARCHAR(1000) DEFAULT 'I am a yellow labrador retriever!',
profilePictureURL VARCHAR(100) DEFAULT 'C:/Program Files/Ampps/www/saorio/uploads/avatar-profile.png',
profilePictureMIME VARCHAR(30) DEFAULT 'image/png',
gender CHAR(1) DEFAULT 'o',
ageGroup TINYINT(1),
location VARCHAR(30),
job VARCHAR(30), 
company VARCHAR(30),
major VARCHAR(20),
school VARCHAR(30) DEFAULT 'Saorio College',
interests VARCHAR(30),
quote VARCHAR(100),
PRIMARY KEY(user),
index(user(5))";
$tables["$tablename"] = $columns;

//the messages table
//timestamp is unix epoch UTC time
$tablename = "messages";
$columns = "id INT UNSIGNED AUTO_INCREMENT,
timestamp INT UNSIGNED NOT NULL,
sender VARCHAR(20) NOT NULL,
recipient VARCHAR(20) NOT NULL,
message VARCHAR(1000) NOT NULL,
PRIMARY KEY (id),
INDEX(sender(5)),
INDEX(recipient(5))";
$tables["$tablename"] = $columns;

//the friends table
//status col for friend status where requested is always from user1 to user2
$tablename = "friends";
$columns = " id INT(10) UNSIGNED AUTO_INCREMENT,
user1 VARCHAR(20),
user2 VARCHAR(20),
status ENUM('requested', 'confirmed', 'rejected'), 
PRIMARY KEY (id),
INDEX(user1(5)),
INDEX(user2(5))";
$tables["$tablename"] = $columns;

//create table for every member of $tables
foreach ($tables as $table => $columns) {
    $sql = "CREATE TABLE IF NOT EXISTS $table ($columns)";
    $dbh->query($sql);
    echo "The table $table is now in database ". DB_NAME. "<br>";
}

echo "<br> ...Table initialization completed. </body></html>";

?>
