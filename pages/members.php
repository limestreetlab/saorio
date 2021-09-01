<?php

  if (!$isLoggedIn) {
    header( "Location: " .  REL_SITE_ROOT);
    exit();
  }
  
  //data retrieval
  $members = queryDB($getAllUsersQuery); //get the entire set of usernames from database 
  $numberOfMembers = count($members); 

  $viewLoader->load("members_list_start.phtml")->bind(["appName" => $appName, "numberOfUsers" => $numberOfMembers])->render(); //page open html
  
  $userObj = new User($user);
  
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
    //profile of this user
    $profileObj = $memberObj->getProfile(true); //get the BasicProfile of this user
    $profileData = $profileObj->getData(); //get instance variables
    $hisFirstname = $profileData["firstname"];
    $hisLastname = $profileData["lastname"];
    $hisPicture = $profileData["profilePictureURL"]; //rel path to picture
    
    //apply this member's data in a view
    $data = ["hisPicture" => $hisPicture, "hisFullname" => $hisFirstname . ' ' . $hisLastname, "hisUsername" => $hisUsername, "relationship" => $relationship];
    $viewLoader->load("members_card.phtml")->bind($data)->render(); //include each member's card view
    
  } //end for-loop

  $viewLoader->load("members_list_end.phtml")->render(); //page close html
  $viewLoader->load("friend_request_confirmation_modal.phtml")->render(); //modal 


?>

<!--page js-->
<script src="js/members.js"></script>