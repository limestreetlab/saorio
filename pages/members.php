<?php

  if (!$isLoggedIn) {
    header( "Location: " .  REL_SITE_ROOT);
    exit();
  }
  
  //data retrieval
  $mysql = MySQL::getInstance(); //object for mysql database access
  $members = $mysql->request($mysql->readAllUsersQuery); //get the entire set of usernames from database 
  $numberOfMembers = count($members); 

  $viewLoader->load("members_list_start.html")->bind(["appName" => $appName, "numberOfUsers" => $numberOfMembers])->render(); //page open html
    
  //loop block for each user
  foreach ($members as $member) { 
    
    //skip the current user himself
    if ( $user == $member["user"] ) { 
      continue; 
    }

    //variables assignment
    $hisUsername = $member["user"];
    $memberObj = new User($hisUsername); //instantiate a User obj for this member
    $relationship = $userObj->getRelationshipWith($hisUsername); //get this member's relationship code with me
    
    //profile data of this user
    $profileData = $memberObj->getProfile(true)->getData(); 
    
    //apply this member's data in a view
    $viewData = ["hisPicture" => $profileData["profilePictureURL"], "hisFullname" => $profileData["firstname"] . ' ' . $profileData["lastname"], "hisUsername" => $hisUsername, "relationship" => $relationship];
    
    $viewLoader->load("members_card.html")->bind($viewData)->render(); //include each member's card view
    
  } //end for-loop

  $viewLoader->load("members_list_end.html")->render(); //page close html
  $viewLoader->load("friend_request_confirmation_modal.html")->render(); //modal 
  $viewLoader->load("error_toast.html")->render(); //toast for errors

?>

<!--page js-->
<script src="js/members.js"></script>