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

  if ($success) { //successfully posted to database, now display in frontend

    //notify followers
    $postId = $post->getData()["id"];
    $userObj->notifyFollowers($postId);

    //collect data for front-end view
    $profile = $userObj->getProfile(true);
    extract($profile->getData());
    $now = time();
    $date = (new DateTime("@$now"))->format("M d, Y");

    //organize view data for binding
    $postData = ["id" => $postId, "profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "date" => $date, "options" => ["<i class='bi bi-pencil'></i> Edit post", "<i class='bi bi-trash'></i> Delete post"], "text" => $post->getContent(), "images" => null, "configs" => null, "likes-stat" => 0, "dislikes-stat" => 0, "haveAlreadyLiked" => false, "haveAlreadyDisliked" => false];
    $postView = $viewLoader->load("./../templates/profile_post.html")->bind($postData)->getView(); //get view string

  }

  echo json_encode(["success" => $success, "errors" => $post->getErrors(), "postView" => $postView]);
  exit();

}

/*
script for handling requests of post updates by returning a render-ready edit view
@return [bool success, string view] where view is the render-ready post string
*/
if ( $_REQUEST["action"] == "update" && isset($_REQUEST["id"]) && !isset($_REQUEST["type"]) ) {

  //collect data to prepare for the view
  $profile = $userObj->getProfile(true);
  extract($profile->getData());
  
  $pm = new PostManager($user);
  
  $postData = $pm->getData($_REQUEST["id"]);
  
  $text = $postData["text"];
  $images = $postData["images"];
  $descriptions = $postData["descriptions"];
  $configs = empty($images) ? null : PostManager::getImageCssClasses($images);

  $bind = ["profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "text" => $text, "images" => $images, "configs" => $configs, "descriptions" => $descriptions];
  $postView = $viewLoader->load("./../templates/profile_post_edit_form.html")->bind($bind)->getView(); //get view string

  echo json_encode(["success" => $success, "postView" => $postView]);
  exit();

}

/*
script to handing updating a text post
@return [bool success, array errors, int photosNum, string newId] where photosNum is the number of photos involved and newId is new post id if post type is changed
*/
if ( $_REQUEST["action"] == "update" && isset($_REQUEST["id"]) && $_REQUEST["type"] == "text" ) {

  $pm = new PostManager($user);
  $postData = $pm->getData($_REQUEST["id"]);
  $postType = $postData["type"];

  if ($postType == 2) { //originally an image post now becoming text

    //delete the original image post and create a new text post
    $photosNum = count($postData["images"]);   
    
    try {
      
    } catch (Exception $ex) {
      $success = false;
    }
  
  } else { //originally a text post
      
    $post = new PostOfText(null, $_REQUEST["id"]); 
    $photosNum = 0;
    $newId = null;

    try {
      $post->update($_REQUEST["text"]);
      $success = true;
    } catch (Exception $ex) {
      $success = false;
    }

  }

  echo json_encode(["success" => $success, "errors" => $post->getErrors(), "photosNum" => $photosNum, "newId" => $newId]);
  exit();

}

/*
script to handing updating an image post
it retrieves the original post and compares with received data to check what's changed to inform frontend for update
1 if nothing is changed, 2 if text changed, 3 if captions changed, 4 if photos changed, 5 if text and captions changed, 6 if text and photos changed, 7 if post type changed 
@return [int changeCode, bool success, array errors, int photosNum, string newId] where photosNum is the number of photos involved and newId is new post id if post type is changed
*/
if ( $_REQUEST["action"] == "update" && isset($_REQUEST["id"]) && $_REQUEST["type"] == "image" ) {

  //collect current variables
  $id = $_REQUEST["id"]; //id of updating post
  $new_text = !empty($_REQUEST["text"]) ? trim($_REQUEST["text"]) : null; //string, text data
  $new_images = $_REQUEST["images"]; //src of images
  $new_captions = $_REQUEST["captions"]; //array, img captions data
  $files = fixArrayFiles($_FILES["files"]); //array, img files data, if any
  //collect original variables
  $pm = new PostManager($user);
  $original_data = $pm->getData($id);
  $original_type = $original_data["type"];
  $original_text = $original_data["text"];
  $original_images = $original_data["images"];
  $original_captions = $original_data["descriptions"];
  //data comparason, all boolean values
  $type_changed = $original_type != 2 ? true : false; //check whether originally a non-image post
  $text_changed = ( $new_text === $original_text ); //string equality check
  $images_changed = ( $new_images === $original_images ); //array equality check, 1 if same size, same values, same order
  $captions_changed = ( $new_captions === $original_captions ); //array equality check
  $changes = [$type_changed, $text_changed, $captions_changed, $images_changed]; //arr to aggregate changes
  
  switch ($changes) {

    case [0, 0, 0, 0]: //nothing is changed
      $changed = 1;
      break;
    case [0, 1, 0, 0]: //text changed only
      $changed = 2;
      $post = new PostOfImage(null, $id);
      $success = $post->update(4, [$new_text]);
      $photosNum = 0;
      $newId = null;
      break;
    case [0, 0, 1, 0]: //captions changed only, so both old and new caption arrays must have same lengths
      $changed = 3;
      $post = new PostOfImage(null, $id);
      //identify which photo has its caption changed and the new caption
      $changed_keys_values = [];
      foreach ($original_captions as $key => $value) {
        if ($value !== $new_captions[$key]) {
          $changed_keys_values[] = [$key, $new_captions[$key]];
        }
      }
      $success = $post->update(1, $changed_keys_values);
      $photosNum = 0;
      $newId = null;
      break;
    case [0, 0, 1, 1]: //captions and img changed
    case [0, 0, 0, 1]: //img changed
      $changed = 4;
      break;
    case [0, 1, 1, 0]: //text and captions changed
      $changed = 5;
      break;
    case [0, 1, 1, 1]: //text, captions, img
    case [0, 1, 0, 1]: //text, img
      $changed = 6;
      break;
    default: //2^3 = 8 arragements when post type hasn't changed are covered above
      $changed = 7;
      
  }

  echo json_encode(["change" => $changed, "success" => $success, "errors" => $post->getErrors(), "photosNum" => $photosNum, "newId" => $newId]);
  exit();

}


/*
script to delete an existing post
@return [bool success, int photosNum] where photosNum is the number of images involved
*/
if ( $_REQUEST["action"] == "delete" && isset($_REQUEST["id"]) ) {

  $pm = new PostManager($user);
  $images = $pm->getData($_REQUEST["id"])["images"];
  $numberOfPhotosDeleted = empty($images) ? 0 : count($images); 
  $success = $pm->remove($_REQUEST["id"]);

  echo json_encode(["success" => $success, "photosNum" => $numberOfPhotosDeleted]);
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
      array_push($images, $el[0]->getFileWebPath()); //relative paths for data bind
      array_push($descriptions, $el[1]); //photo descriptions for data bind
    }
    
    $configs = PostManager::getImageCssClasses($images);

    //organize view data for binding
    $postData = ["id" => $postId, "profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "date" => $date, "options" => ["<i class='bi bi-pencil'></i> Edit post", "<i class='bi bi-trash'></i> Delete post"], "text" => $text, "images" => $images, "configs" => $configs, "descriptions" => $descriptions, "likes-stat" => 0, "dislikes-stat" => 0, "haveAlreadyLiked" => false, "haveAlreadyDisliked" => false];
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

  if (empty($files)) {
    return null;
  }

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

function countNumberOfImg(string $view): int {

  $regex = " /(?:\G(?!^)|\bpost-attachment\b).*?\K\bimg\b /si "; //regex for 'img' occurring after 'post-attachment'
  preg_match_all($regex, $view, $matches);
  return count($matches[0]);

}

?>


