<?php

  if (!$isLoggedIn) {
    header( "Location: " .  SITE_ROOT_URL);
    exit();
  }
  
  //data retrieval
  $memberManager = new MemberManager();
  $members = $memberManager->getMembers();
  $numberOfMembers = $memberManager->getNumberOfMembers(); 

  $viewLoader->load("members_list_start.html")->bind(["appName" => $appName, "numberOfUsers" => $numberOfMembers])->render(); //page open html
    
  //loop block for each user
  foreach ($members as $member) { 

    //getting data of this member
    $profileData = $member->getProfile(true)->getData();
    $hisUsername = $profileData["user"];
    $hisFullname = $profileData["firstname"] . ' ' . $profileData["lastname"];
    $hisPicture = $profileData["profilePictureURL"];
    $relationship = $userObj->getRelationshipWith($hisUsername); //get this member's relationship code with me
  
    //apply the data in view
    $viewData = compact("hisUsername", "hisPicture", "hisFullname", "relationship");
    $viewLoader->load("members_card.html")->bind($viewData)->render(); //include each member's card view
    
  } //end for-loop

  $viewLoader->load("members_list_end.html")->render(); //page close html
  $viewLoader->load("friend_request_confirmation_modal.html")->render(); //modal 
  $viewLoader->load("error_toast.html")->render(); //toast for errors

?>

<!--page js-->
<script src="js/members.js"></script>