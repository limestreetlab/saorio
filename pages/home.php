<?php
//done for now, add profile-edit JS and AJAX later

if (!$isLoggedIn) {
  header( "Location: " .  SITE_ROOT_URL);
  exit();
}

//extract current user's data
extract( $userObj->getProfile(true)->getData() );

//start of main menu
$viewLoader->load("home_start.html")->render();

//new post form view
$formData = ["profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname];
$viewLoader->load("profile_post_form.html")->bind($formData)->render();

//starting post feed view
$viewLoader->load("profile_posts_start.html")->render();

//collecting data about each post feed and render to view
$mysql = MySQL::getInstance();
$ids = $userObj->getPostFeed();
foreach ($ids as $id) {
  //skip this iteration if post id is empty
  if (empty($id)) {
    continue;
  }

  $type = $mysql->request(MySQL::readPostQuery, [":id" => $id])[0]["type"];

  if ($type == 1) {

    $post = new PostOfText(null, $id);
    $postData = $post->getData();
    $text = $postData["content"];
    $images = null;
    $descriptions = null;
    $configs = null;
  
  } elseif ($type == 2) {

    $post = new PostOfImage(null, $id); 
    $postData = $post->getData();
    $text = is_null($postData["text"]) ? null : $postData["text"]->getContent();
    $content = $postData["content"];
    $images = []; //rel path
    $descriptions = []; //caption
    foreach ($content as $row) {
      array_push($images, ($row[0])->getFileRelativePath());
      array_push($descriptions, $row[1]);
    }
    $configs = PostManager::getImageCssClasses($images);

  }
  
  $poster = $postData["user"];
  $posterData = (new BasicProfile($poster))->getData();
  $posterPicture = $posterData["profilePictureURL"];
  $posterFirstname = $posterData["firstname"];
  $posterLastname = $posterData["lastname"];
  $timestamp = $postData["timestamp"];
  $date = (new DateTime("@$timestamp"))->format("M d, Y"); //format timestamp to date
  $postOptions = ["<i class='bi bi-bookmark-plus'></i> Save this post"];
  $likes = $postData["likes"];
  $dislikes = $postData["dislikes"];
  $haveAlreadyLiked = $post->haveAlreadyLiked($user);
  $haveAlreadyDisliked = $post->haveAlreadyDisliked($user);

  $bind = ["id" => $id, "profile-picture" => $posterPicture, "firstname" => $posterFirstname, "lastname" => $posterLastname, "date" => $date, "options" => $postOptions, "text" => $text, "images" => $images, "configs"=> $configs, "likes-stat" => $likes, "dislikes-stat" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked];
  $viewLoader->load("profile_post.html")->bind($bind)->render();

}

//ending post feed view
$viewLoader->load("profile_posts_end.html")->render();

//ending main-menu and starting side-menu
$viewLoader->load("home_main_menu_end.html")->render();

//potential friends view
$strangerObjs = $userObj->getStrangers(4);

$users = [];
$photos = [];
$names = [];
foreach ($strangerObjs as $strangerObj) {

  extract( $strangerObj->getProfile(true)->getData() );
  array_push($users, $user);
  array_push($photos, $profilePictureURL);
  array_push($names, $firstname . ' ' . $lastname);

}

$suggestionData = compact("users", "photos", "names");
$viewLoader->load("home_friend_suggestion.html")->bind($suggestionData)->render();

//new members view
$newMemberObjs = (new MemberManager())->getNewMembers(4);

$members = [];
$photos = [];
foreach ($newMemberObjs as $newMemberObj) {

  extract( $newMemberObj->getProfile(true)->getData() );
  array_push($members, $firstname . ' ' . $lastname);
  array_push($photos, $profilePictureURL);

}

$memberData = compact("members", "photos");
$viewLoader->load("home_new_members.html")->bind($memberData)->render();

//end of side-menu
$viewLoader->load("home_end.html")->render();

//toast for errors
$viewLoader->load("error_toast.html")->render(); 




?>

<!--accompanying JS-->
<script src="js/profile_post.js"></script>