<?php

  if (!$isLoggedIn) {
    header( "Location: " .  REL_SITE_ROOT);
    exit();
  }

  require_once INCLUDE_DIR . "queries.php";
  

  //php and html block to retrieve and display all members
  $members = queryDB($getAllMembersQuery); //get all members into an array of users, defined to return user, firstname, lastname, profilePictureURL
  $numberOfMembers = count($members);

  //the members display template
  echo "<main class='container'>";
  echo "<br><div class='h4 text-primary'>Saorio's $numberOfMembers Strong Members</div><br>";
  echo "<section class='row row-cols-4'>"; //row-cols-* assigns number of items per row

  //loop block to display each member profile
  foreach ($members as $member) { 
    
    //retrieve info about this member
    $userObj = new User($member["user"]); //user obj
    $mFirstname = ucfirst(strtolower($members[$m]["firstname"])); //firstname
    $mLastname = ucfirst(strtolower($members[$m]["lastname"])); //lastname
    $profilePicture = getPhotoPath($members[$m]["profilePictureURL"]); //rel path for img
    
    require INCLUDE_DIR . "templates.php"; //require not once as used in a loop, after setting each user's variables, include some friendship buttons that embed such data
   
    if ($mUser == $user) {
      continue; //skip this iteration if the user himself
    }    

    switch ($relationship) {
      case 0: 
        $mRelationship = $stranger;
        break;
      case 1:
        $mRelationship = $alreadyFriend;
        break;
      case 2:
        $mRelationship = $requesting;
        break;
      case 3: 
        $mRelationship = $requested;
        break;
      case 4: 
        $mRelationship = $rejected;
        break;
      default:
        $mRelationship = null;
    }
    
    echo "<div class='col'><div class='card h-100'>"; //this member's profile starts
    echo "<img src='$profilePicture' class='card-img-top img-fluid img-thumbnail'>"; //the profile image
    echo "<div class='card-body text-center'><h5 class='card-title'>$mFirstname $mLastname</h5>"; //profile body
    echo "<div id='relationshipWith$mUser'> $mRelationship </div>";
    echo "</div></div></div>"; //profile ends
  
  } //end for loop

  echo "</section></main>"; //close the container and row tags


?>

<!-- Friend Request Received Confirmation Modal -->
<div class="modal fade" id="friendRequestConfirmationModal" >
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="friendRequestConfirmationModalTitle"></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="friendRequestConfirmationModalBody">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary mx-2" id="friendRequestConfirmationModalRejectBtn" data-bs-dismiss="modal">Reject</button>
        <button type="button" class="btn btn-primary mx-2" id="friendRequestConfirmationModalAcceptBtn" data-bs-dismiss="modal">Accept</button>
      </div>
    </div>
  </div>
</div>
<!--modal end-->

<!--required js-->
<script src="js/members.js"></script>