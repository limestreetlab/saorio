<?php
/*used to initialize necessary MySQL tables for this app, under the DB connection outlined in config.php.
Run this script before using the app.
This script simply sends table creation codes to MySQL.
*/

require_once "./includes/config.php";
require_once INCLUDE_DIR . "ini.php";

echo "<!DOCTYPE html>
        <html><head><title>Database initialization for $appName </title></head>
            <body><h2>Creating databases, please wait...</h2>
     ";

$tables = []; //to hold tables to be created in name:columns pairs

//the members table
$tablename = "members";
$columns = "user VARCHAR(20), 
password VARCHAR(100),
email VARCHAR(50) UNIQUE,
PRIMARY KEY(user),
INDEX(user(5))";
$tables["$tablename"] = $columns;


//the profiles table
$tablename = "profiles";
$columns = "user VARCHAR(20),
firstname VARCHAR(30) NOT NULL,
lastname VARCHAR(30) NOT NULL,
about VARCHAR(1000),
profilePictureURL VARCHAR(300) DEFAULT 'C:/Program Files/Ampps/www/saorio/uploads/avatar0.png' COMMENT 'absolute path',
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
FOREIGN KEY (user) REFERENCES members(user) ON DELETE CASCADE,
index(user(5))";
$tables["$tablename"] = $columns;

//the messages table
$tablename = "messages";
$columns = "id INT UNSIGNED AUTO_INCREMENT COMMENT 'timestamps can overlap so id more reliable',
timestamp INT UNSIGNED NOT NULL COMMENT 'unix epoch UTC timestamp',
sender VARCHAR(20) NOT NULL,
recipient VARCHAR(20) NOT NULL,
message VARCHAR(1000) NOT NULL,
PRIMARY KEY (id),
FOREIGN KEY (sender) REFERENCES members(user) ON DELETE CASCADE,
FOREIGN KEY (recipient) REFERENCES members(user) ON DELETE CASCADE,
INDEX(sender(5)),
INDEX(recipient(5))";
$tables["$tablename"] = $columns;

//the friends table
$tablename = "friends";
$columns = " id INT(10) UNSIGNED AUTO_INCREMENT,
user1 VARCHAR(20) NOT NULL COMMENT 'requests are always from user1',
user2 VARCHAR(20) NOT NULL COMMENT 'user2 always receives requests',
status TINYINT(1) NOT NULL COMMENT '1 for confirmed, 2 for pending confirmation', 
timestamp TIMESTAMP DEFAULT NOW() ON UPDATE NOW() COMMENT 'epoch timestamp to current time whenever a row is added or modified', 
PRIMARY KEY (id),
FOREIGN KEY (user1) REFERENCES members(user) ON DELETE CASCADE,
FOREIGN KEY (user2) REFERENCES members(user) ON DELETE CASCADE,
INDEX(user1(5)),
INDEX(user2(5))";
$tables["$tablename"] = $columns;

//friends_data, friends table is mutual (two-way), whilst friends data are one-way, how A stores data about B <> how B stores about A
$tablename = "friends_data";
$columns = "user1 VARCHAR(20) NOT NULL COMMENT 'data creator',
user2 VARCHAR(20) NOT NULL COMMENT 'data about this user',
following TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'boolean whether user1 follows user2',
notes VARCHAR(1000) COMMENT 'notes by user1 about user2',
PRIMARY KEY (user1, user2),
FOREIGN KEY (user1) REFERENCES members(user) ON DELETE CASCADE,
FOREIGN KEY (user2) REFERENCES members(user) ON DELETE CASCADE";
$tables["$tablename"] = $columns;


//posts table, to store data common to all posts made by a user, but posts have their more specific contents stored elsewhere and post reactions are stored elsewhere too
$tablename = "posts";
$columns = "id INT UNSIGNED AUTO_INCREMENT,
user VARCHAR(20) NOT NULL COMMENT 'user making the post',
timestamp TIMESTAMP DEFAULT NOW() COMMENT 'epoch timestamp to now whenever a post is made or modified',
content_type TINYINT(1) NOT NULL COMMENT 'different types of posts can have different contents and post-to-content relationships so put into own tables',
PRIMARY KEY (id),
FOREIGN KEY (user) REFERENCES members(user) ON DELETE CASCADE";
$tables["$tablename"] = $columns;

//post contents for simple text-based posts, one-to-one
$tablename = "text_posts";
$columns = "id INT UNSIGNED AUTO_INCREMENT,
post_id INT UNSIGNED NOT NULL COMMENT 'id of the post the text attached to',
content VARCHAR(500) NOT NULL,
PRIMARY KEY (id),
FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE";
$tables["$tablename"] = $columns;

//post contents for image-based posts, one-to-many (one post, many images) 
$tablename = "image_posts";
$columns = "id INT UNSIGNED AUTO_INCREMENT,
post_id INT UNSIGNED NOT NULL COMMENT 'id of the post the image attached to',
imageURL VARCHAR(300) NOT NULL COMMENT 'absolute path to the image',
imageMIME VARCHAR(30) NOT NULL COMMENT 'full mime of image/xxx',
PRIMARY KEY (id),
FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE"; 

//post comments, one-to-many (one post, many comments)
$tablename = "post_comments";
$columns = "comment_id INT UNSIGNED AUTO_INCREMENT,
post_id INT UNSIGNED NOT NULL COMMENT 'id of the post commenting on',
user VARCHAR(20) NOT NULL COMMENT 'user making the comment',
comment VARCHAR(200) NOT NULL COMMENT 'the comment body',
PRIMARY KEY (comment_id),
FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
FOREIGN KEY (user) REFERENCES members(user) ON DELETE CASCADE";
$tables["$tablename"] = $columns;

//post likes and dislikes, one-to-many (one post, many likes/dislikes)
$tablename = "post_reactions";
$columns = "reaction_id INT UNSIGNED AUTO_INCREMENT,
post_id INT UNSIGNED NOT NULL COMMENT 'id of the post reacting to',
user VARCHAR(20) NOT NULL COMMENT 'user making the reaction, once per post',
reaction TINYINT(1) NOT NULL COMMENT 'boolean flag, use negative and positive numbers for negative and position reactions',
PRIMARY KEY (reaction_id),
FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
FOREIGN KEY (user) REFERENCES members(user) ON DELETE CASCADE";
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
