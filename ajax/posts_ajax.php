<?php
//PHP Script to handle posts

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 


/*
script to send a text post
@return [bool success, array errors, string post] where post is the render-ready text post string
*/
if (isset($_REQUEST["text"]) && $_REQUEST["action"] == "send") {

  $post = new PostOfText($_REQUEST["text"], null); //create post obj
  $success = $post->post(); //post it

  if ($success) {

    //collect data for front-end view
    $profile = $userObj->getProfile(true);
    extract($profile->getData());
    $now = time();
    $date = (new DateTime("@$now"))->format("M d, Y");

    //organize view data for binding
    $postData = ["profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "date" => $date, "text" => $post->getContent(), "images" => null, "likes-stat" => 0, "dislikes-stat" => 0];
    $postView = $viewLoader->load("./../templates/profile_post.html")->bind($postData)->getView(); //get view string

  }

  echo json_encode(["success" => $success, "errors" => $post->getErrors(), "postView" => $postView]);
  exit();

}


?>


