<?php

if (!$isLoggedIn) {
  header( "Location: " . SITE_ROOT ) ;
  exit();
}
require_once INCLUDE_DIR . "queries.php";

//php code block for unfriending
if (isset($_REQUEST["unfriend"])) {
    
  $unfriend = filter_var($_REQUEST["unfriend"], FILTER_SANITIZE_STRING);

  $param = [":a" => $user, ":b" => $unfriend];
  queryDB($removeAFriendQuery, $param);
}

//php and html code block to retrieve and display all friends, almost identical to members page
$friends = queryDB($getAllFriendsQuery, [":user" => $user]); //get all friends
$numberOfFriends = count($friends);

//the profile display frame
echo "<main class='container'>";
echo "<br><div class='h4 text-primary'>$firstname, you have $numberOfFriends friends.</div><br>";
echo "<section class='row row-cols-4'>"; //row-cols-* assigns number of items per row

//loop block to display each friend profile
for ($f = 0; $f < $numberOfFriends; $f++) { //loop through all friends

  //retrieve info about this friend
  $fUser = $friends[$f]["user"]; //user
  $fFirstname = ucfirst(strtolower($friends[$f]["firstname"])); //firstname
  $fLastname = ucfirst(strtolower($friends[$f]["lastname"])); //lastname
  $profilePicture = getPhotoPath($friends[$f]["profilePictureURL"]); //rel path for img

  //defining buttons underneath each profile
  $unfriendBtn = "<a href='index.php?reqPage=friends&unfriend=$fUser'><button type='button' class='my-2 btn btn-primary btn-sm w-50'>Unfriend</button></a>";
  $messageBtn = "<a href='index.php?reqPage=messages&viewChat=$fUser'><button type='button' class='my-2 btn btn-primary btn-sm w-50'>Messages</button></a>"; 
  
  echo "<div class='col'><div class='card h-100'>"; //this friend's profile starts
  echo "<img src='$profilePicture' class='card-img-top img-fluid img-thumbnail'>"; //the profile image
  echo "<div class='card-body text-center'><h5 class='card-title'>$fFirstname $fLastname</h5>"; //profile body
  echo $messageBtn;
  echo "<br>";
  echo $unfriendBtn;
  echo "</div></div></div>"; //profile ends
  
}
echo "</section></main>";





?>