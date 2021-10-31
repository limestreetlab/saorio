
<?php
//done for now, add profile-edit JS and AJAX later

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

//route script to load based on request string (viewing one's homepage, viewing one's profile, viewing own homepage, viewing own editable profile)
IF ( isset($_REQUEST["viewUser"]) ) { //viewing another user's home page (summary, photos, posts, with link to his profile)

  
} ELSEIF ( isset($_REQUEST["viewProfile"]) ) { //viewing another user's profile 

  $viewProfile = (new User($_REQUEST["viewProfile"]))->getProfile(false); //retrieve profile data of the view user
  extract($viewProfile->getData()); //use key names as variable names
  $profileData = ["picture" => $profilePictureURL, "wallpaper" => $wallpaper, "firstname" => $firstname, "lastname" => $lastname, "city" => $city, "country" => $country, "gender" => $gender, "age" => $age, "dob" => is_null($dob) ? null : (new DateTime("@$dob"))->format("m-d-Y"), "job" => $job, "company" => $company, "major" => $major, "school" => $school, "about" => $about, "interests" => $interests, "quote" => $quote, "email" => $email, "website" => $website, "socialmedia" => $socialmedia];
  $viewLoader->load("profile_view.html")->bind($profileData)->render(); //load profile view
  
} ELSEIF ( isset($_REQUEST["editProfile"]) ) {  //edit own profile
  
  $profile = $userObj->getProfile(false);
  extract($profile->getData()); //use key names as variable names
  $profileData = ["picture" => $profilePictureURL, "wallpaper" => $wallpaper, "firstname" => $firstname, "lastname" => $lastname, "city" => $city, "country" => $country, "gender" => $gender, "age" => $age, "dob" => is_null($dob) ? null : (new DateTime("@$dob"))->format("m-d-Y"), "job" => $job, "company" => $company, "major" => $major, "school" => $school, "about" => $about, "interests" => $interests, "quote" => $quote, "email" => $email, "website" => $website, "socialmedia" => $socialmedia];
  
  $viewLoader->load("profile_edit.html")->bind($profileData)->render(); //load data into profile edit view
  $viewLoader->load("error_toast.html")->render(); //toast for errors
  
} ELSE { //no request, self viewing own profile

  //UI consists of different views, collecting required data and binding them to each view 1 by 1
  
  //extract current user's data
  $profile = $userObj->getProfile(false);
  extract($profile->getData()); //use key names as variable names

  $postManager = new PostManager($user);

  //opening view
  $viewLoader->load("profile_start.html")->render();

  //profile snapshot view
  $summaryData = ["wallpaper" => $wallpaper, "profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "summary" => strlen($about) > 100 ? substr( $about, 0, strpos(wordwrap($about, 100), "\n") ) . '...' : $about, "posts-stat" => $postManager->getNumberOfPosts(), "photos-stat" => $postManager->getNumberOfPostedImages(), "comments-stat" => 12, "likes-stat" => ($postManager->getNumberOfLikes() - $postManager->getNumberOfDislikes()), "profile-link" => $_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"] . '&editProfile=true'];
  
  $viewLoader->load("profile_summary.html")->bind($summaryData)->render();

  //photos summary view
  $photosData = ["photos" => $postManager->getPostedImages(9)];
  
  $viewLoader->load("profile_photos.html")->bind($photosData)->render();

  //friends snapshot view
  $friends = $userObj->getFriends(9); //get 9 friends of this user
  $friend_names = []; //array to store names of friends
  $friend_photos = []; //array to store photos of friends

  foreach ($friends as $friend) { //loop through friends to fill the arrays
    $friend_data = $friend->getProfile(true)->getData();
    array_push($friend_names, $friend_data["firstname"] . ' ' . $friend_data["lastname"]); 
    array_push($friend_photos, $friend_data["profilePictureURL"]); 
  }
  
  $friendsData = [ "friends" => $friend_names, "photos" => $friend_photos ];
  
  $viewLoader->load("profile_friends.html")->bind($friendsData)->render();

  $viewLoader->load("profile_sidemenu_end.html")->render();

  //new post form view
  $formData = ["profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname];
  $viewLoader->load("profile_post_form.html")->bind($formData)->render();

  //old posts view
  $viewLoader->load("profile_posts_start.html")->render();

  $page = isset($_REQUEST["pagination"]) ? $_REQUEST["pagination"] : 1; //paginated number assigned if requested else starting with 1
  $posts = $postManager->getPage($page);
  
  foreach ($posts as $post) {

    extract($post); //get data about this post
    $date = (new DateTime("@$timestamp"))->format("M d, Y"); //format timestamp to date
    $configs = is_null($images) ? null : $postManager::getImageCssClasses($images);

    $postData = ["id" => $id, "profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "date" => $date, "options" => ["<i class='bi bi-pencil'></i> Edit post", "<i class='bi bi-trash'></i> Delete post"], "text" => $text, "images" => $images, "configs"=> $configs, "likes-stat" => $likes, "dislikes-stat" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked];
    $viewLoader->load("profile_post.html")->bind($postData)->render();
  
  }
  
  $viewLoader->load("profile_posts_end.html")->render();

  //pagination view
  $pagination = $postManager->paginate($page);
  $viewLoader->load("profile_posts_pagination.html")->bind(["pages" => $pagination, "activePage" => $page])->render();
  

  //closing view
  $viewLoader->load("profile_end.html")->render();
  
  //toast for errors
  $viewLoader->load("error_toast.html")->render(); 

}


?>

<!--accompanying JS-->
<script src="js/profile_post.js"></script>