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
  $postId = $post->getData()["id"];

  $userObj->notifyFollowers($postId);

  if ($success) { //successfully posted to database, now display in frontend

    //collect data for front-end view
    $profile = $userObj->getProfile(true);
    extract($profile->getData());
    $now = time();
    $date = (new DateTime("@$now"))->format("M d, Y");

    //organize view data for binding
    $postData = ["id" => $postId, "profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "date" => $date, "text" => $post->getContent(), "images" => null, "configs" => null, "likes-stat" => 0, "dislikes-stat" => 0, "haveAlreadyLiked" => false, "haveAlreadyDisliked" => false];
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

  $text = !empty($_REQUEST["text"]) ? $_REQUEST["text"] : null; //string, text data
  $captions = $_REQUEST["captions"]; //array, img captions data
  $files = fixArrayFiles($_FILES["images"]); //array, img files data
  
  $content = [$text]; //first element is text, follows by arrays of file and description
  for ($i = 0; $i < count($files); $i++) {

    array_push( $content, [$files[$i], $captions[$i]] );

  }
  
  $post = new PostOfImage($content, null); //create post obj
  $success = $post->post(); //post it
  $postId = $post->getData()["id"];

  $userObj->notifyFollowers($postId);

  if ($success) { //successfully posted to database, now display in frontend
    
    //collect data for front-end view
    $profile = $userObj->getProfile(true);
    extract($profile->getData());
    $now = time();
    $date = (new DateTime("@$now"))->format("M d, Y");
    //image data
    $content = $post->getContent();
    $images = []; //array of image file relative paths
    $descriptions = []; //array of image captions
    
    foreach ($content as $el) {
      array_push($images, $el[0]->getFileRelativePath()); //relative paths for data bind
      array_push($descriptions, $el[1]); //photo descriptions for data bind
    }
    
    $configs = PostManager::getImageCssClasses($images);

    //organize view data for binding
    $postData = ["id" => $postId, "profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "date" => $date, "text" => $text, "images" => $images, "configs" => $configs, "likes-stat" => 0, "dislikes-stat" => 0, "haveAlreadyLiked" => false, "haveAlreadyDisliked" => false];
    $postView = $viewLoader->load("./../templates/profile_post.html")->bind($postData)->getView(); //get view string

  }
   
  echo json_encode(["success" => $success, "errors" => $post->getErrors(), "postView" => $postView]);
  exit();

}

/*
script to perform pagination
@return string of paginated contents
*/
if ($_REQUEST["action"] == "pagination" && isset($_REQUEST["page"])) {

  $page = $_REQUEST["page"]; //page requested

  extract($userObj->getProfile(false)->getData()); //obtain firstname lastname pictureURL variables of user
  
  try {

    $pm = new PostManager($user);
    $posts = $pm->getPage($page); //data of posts
    $postView = ""; //string to accumulate HTML for each post
    //get view of individual post and concatenate them
    foreach ($posts as $post) {

      extract($post); //get data about this post
      $date = (new DateTime("@$timestamp"))->format("M d, Y"); //format timestamp to a date
      $configs = is_null($images) ? null : $pm::getImageCssClasses($images);

      $postData = ["id" => $id, "profile-picture" => "$profilePictureURL", "firstname" => "$firstname", "lastname" => "$lastname", "date" => $date, "text" => $text, "images" => $images, "configs"=> $configs, "likes-stat" => $likes, "dislikes-stat" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked];
      $postView .= $viewLoader->load("./../templates/profile_post.html")->bind($postData)->getView();
    
    }
    //get view of updated pagination and concatenate
    $pagination = $pm->paginate($page);
    $paginationView = $viewLoader->load("./../templates/profile_posts_pagination.html")->bind(["pages" => $pagination, "activePage" => $page])->getView();
  
    $success = true;

  } catch (Exception $ex) {

    $success = false;

  }

  echo json_encode(["success" => $success, "errors" => [-1], "postView" => $postView, "paginationView" => $paginationView]);
  exit();

}

/*
script to perform post liking/disliking
*/
if ($_REQUEST["id"] && $_REQUEST["vote"]) {

  $id = $_REQUEST["id"];
  $vote = intval($_REQUEST["vote"]);

  $pm = new PostManager($user);
  $result = $pm->vote($id, $vote);
  $success = $result[0]; //true false
  $message = $result[1]; //arbitrarily defined, -1 system err, 1 same user, 2 like, 3 dislike, 4 unlike, 5 undislike, 6 undislike then like, 7 unlike then dislike

  echo json_encode(["success" => $success, "message" => $message]);
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


