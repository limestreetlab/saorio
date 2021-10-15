<?php
//PHP Script to handle posts

header("Content-Type: application/json"); //return json output
    
require_once "./../includes/ini.php"; //rel path to ini.php 


/*
script to send a text post
@return [bool success, array errors, string post] where post is the render-ready post string
*/
if ($_REQUEST["type"] == "text" && $_REQUEST["action"] == "send") {

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

/*
script to send an image post
@return [bool success, array errors, string post] where post is the render-ready post string
*/
if ($_REQUEST["type"] == "image" && $_REQUEST["action"] == "send") {

  $text = $_REQUEST["text"]; //string, text data
  $captions = $_REQUEST["captions"]; //array, img captions data
  $files = fixArrayFiles($_FILES["images"]); //array, img files data

  
  echo json_encode(["success" => true, "request arr is " => $_POST, "files arr is " => $files]);
  exit();

}

/*
default PHP has a rather strange and difficult-to-use arrangement for array of Files
instead of [ [0] => ["name", "type", "tmp_name", "error", "size"],  [1] => ["name", "type", "tmp_name", "error", "size"], ... ], it is arranged as
[ ["name"] => [0, 1, 2, ...],  ["type"] => [0, 1, 2, ...], ["tmp_name"] => [0, 1, 2, ...], ["error"] => [0, 1, 2, ...], ... ]
this function fixes the arrangement by converting it from the strange to the natural form
@param array the default strangely arranged files array
@return array the now naturally arranged files array
@see https://www.php.net/manual/en/features.file-upload.multiple.php
*/
function fixArrayFiles(&$files) {

  $file_arr = [];
  $file_count = count($files['name']);
  $file_keys = array_keys($files);

  for ($i = 0; $i < $file_count; $i++) {
      foreach ($file_keys as $key) {
          $file_arr[$i][$key] = $files[$key][$i];
      }
  }

  return $file_arr;
}


?>


