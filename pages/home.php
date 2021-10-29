<?php
//done for now, add profile-edit JS and AJAX later

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

//extract current user's data
extract( $userObj->getProfile(true)->getData() );

//start of main menu
$viewLoader->load("home_start.html")->render();

//new post form view
$formData = ["profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname];
$viewLoader->load("profile_post_form.html")->bind($formData)->render();

//post feed view
$viewLoader->load("profile_posts_start.html")->render();
/*
$posts = ; GRAB DATA from Observed posts and load into view
  
  foreach ($posts as $post) {

    extract($post); //get data about this post
    $date = (new DateTime("@$timestamp"))->format("M d, Y"); //format timestamp to date
    $configs = is_null($images) ? null : $postManager::getImageCssClasses($images);

    $postData = ["id" => $id, "profile-picture" => "$profilePictureURL", "firstname" => "$firstname", "lastname" => "$lastname", "date" => $date, "options" => ["<i class='bi bi-pencil'></i> Edit post", "<i class='bi bi-trash'></i> Delete post"], "text" => $text, "images" => $images, "configs"=> $configs, "likes-stat" => $likes, "dislikes-stat" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked];
    $viewLoader->load("profile_post.html")->bind($postData)->render();
  
  }
*/
$viewLoader->load("profile_posts_end.html")->render();

//end of main-menu and start of side-menu
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






?>

<!--accompanying JS-->
<script src="js/home.js"></script>