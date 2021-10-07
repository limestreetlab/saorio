
<?php
//done for now, add profile-edit JS and AJAX later

if (!$isLoggedIn) {
  header( "Location: " .  REL_SITE_ROOT);
  exit();
}

//route script to load based on request string (viewing one's homepage, viewing one's profile, viewing own homepage, viewing own editable profile)
if ( isset($_REQUEST["viewUser"]) ) { //viewing another user's home page (summary, photos, posts, with link to his profile)

  
} elseif ( isset($_REQUEST["viewProfile"]) ) { //viewing another user's profile 

  $viewProfile = (new User($_REQUEST["viewProfile"]))->getProfile(false); //retrieve profile data of the view user
  extract($viewProfile->getData()); //use key names as variable names
  $profileData = ["picture" => $profilePictureURL, "wallpaper" => $wallpaper, "firstname" => $firstname, "lastname" => $lastname, "city" => $city, "country" => $country, "gender" => $gender, "age" => $age, "dob" => is_null($dob) ? null : (new DateTime("@$dob"))->format("m-d-Y"), "job" => $job, "company" => $company, "major" => $major, "school" => $school, "about" => $about, "interests" => $interests, "quote" => $quote, "email" => $email, "website" => $website, "socialmedia" => $socialmedia];
  $viewLoader->load("profile_view.html")->bind($profileData)->render(); //load profile view
  
} elseif ( isset($_REQUEST["editProfile"]) ) {  //edit own profile
  
  $profile = $userObj->getProfile(false);
  extract($profile->getData()); //use key names as variable names
  $profileData = ["picture" => $profilePictureURL, "wallpaper" => $wallpaper, "firstname" => $firstname, "lastname" => $lastname, "city" => $city, "country" => $country, "gender" => $gender, "age" => $age, "dob" => is_null($dob) ? null : (new DateTime("@$dob"))->format("m-d-Y"), "job" => $job, "company" => $company, "major" => $major, "school" => $school, "about" => $about, "interests" => $interests, "quote" => $quote, "email" => $email, "website" => $website, "socialmedia" => $socialmedia];
  
  $viewLoader->load("error_toast.html")->render(); //toast for errors
  $viewLoader->load("profile_edit.html")->bind($profileData)->render(); //load data into profile edit view
  
} else { //no request, self viewing own profile

  //collecting data and loading them into views, 1 by 1

  //extract current user's data
  $profile = $userObj->getProfile(false);
  extract($profile->getData()); //use key names as variable names

  //opening view
  $viewLoader->load("profile_start.html")->render();

  //profile snapshot 
  $summaryData = ["wallpaper" => $wallpaper, "profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname, "summary" => strlen($about) > 100 ? substr( $about, 0, strpos(wordwrap($about, 100), "\n") ) . '...' : $about, "posts-stat" => 51, "photos-stat" => 11, "comments-stat" => 12, "likes-stat" => 188, "profile-link" => $_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"] . '&editProfile=true'];
  
  $viewLoader->load("profile_summary.html")->bind($summaryData)->render();

  //photos summary 
  $photosData = [ "photos" => ["https://mdbcdn.b-cdn.net/img/new/standard/city/041.jpg", "https://mdbcdn.b-cdn.net/img/new/standard/city/042.jpg", "https://mdbcdn.b-cdn.net/img/new/standard/city/043.jpg", "https://mdbcdn.b-cdn.net/img/new/standard/city/044.jpg"] ];
  
  $viewLoader->load("profile_photos.html")->bind($photosData)->render();

  //friends snapshot 
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

  //new post form 
  $formData = ["profile-picture" => $profilePictureURL, "firstname" => $firstname, "lastname" => $lastname];
  $viewLoader->load("profile_post_form.html")->bind($formData)->render();

  //old posts
  
  $posts = ["profile-picture" => "https://m.media-amazon.com/images/I/41vPqZrsW2L._AC_.jpg", "firstname" => "Johny", "lastname" => "Depp", "date" => "October 1, 2021", "text" => "Aloha, I&#39;m good. Maloha!", "images" => null, "likes-stat" => 38, "dislikes-stat" => 12];
  $viewLoader->load("profile_post.html")->bind($posts)->render();
  
  //pagination
  $pages = ["pages" => [1,2,3,4]];
  $viewLoader->load("profile_posts_pagination.html")->bind($pages)->render();

  //closing view
  $viewLoader->load("profile_end.html")->render();

}


?>

<!--accompanying JS-->
<script src="js/profile_post.js"></script>