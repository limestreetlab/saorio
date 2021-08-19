<?php

  if (!$isLoggedIn) {
    header( "Location: " . SITE_ROOT ) ;
    exit();
  }

  require_once INCLUDE_DIR . "queries.php";
  

  //php code block for adding a friend
  if (isset($_REQUEST["addFriend"])) {
    
    //the user to send request to
    $addFriend = filter_var(trim($_REQUEST["addFriend"]), FILTER_SANITIZE_STRING);
    
    //to change relationship in friends table
    $param = [":a" => $user, ":b" => $addFriend];  
    queryDB($addAFriendQuery, $param); //reflect the friend request status to database
    
    //a message is sent to the user to add to let him know of a friend request
    $addFriendFirstname = queryDB($getNameAndPictureQuery, [":user" => $addFriend])[0]["firstname"];
    $friendRequestMessage = "Hi $addFriendFirstname, 
                             I would like to add you as a friend and sent you a request. ";
    sendMessage($user, $addFriend, $friendRequestMessage); //send the friend request message
    
  }

  //php and html block to retrieve and display all members
  $members = queryDB($getAllMembersQuery); //get all members into an array of users, defined to return user, firstname, lastname, profilePictureURL
  $numberOfMembers = count($members);

  //the profile display frame
  echo "<main class='container'>";
  echo "<br><div class='h4 text-primary'>Saorio's $numberOfMembers Strong Members</div><br>";
  echo "<section class='row row-cols-4'>"; //row-cols-* assigns number of items per row

  //loop block to display each member profile
  for ($m = 0; $m < $numberOfMembers; $m++) { 
    
    //retrieve info about this member
    $mUser = $members[$m]["user"]; //user
    $mFirstname = ucfirst(strtolower($members[$m]["firstname"])); //firstname
    $mLastname = ucfirst(strtolower($members[$m]["lastname"])); //lastname
    $profilePicture = getPhotoPath($members[$m]["profilePictureURL"]); //rel path for img
    
    require INCLUDE_DIR . "templates.php"; //require not once as used in a loop, after setting each user's variables, include some friendship buttons that embed such data
   
    if ($mUser == $user) {
      continue; //skip this iteration if the user himself
    }    

    $relationship = getRelationship($user, $mUser);
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

  //helper function to check the relationship between two users
  //@param string user a for this user, string user b for another user
  //@return int 0 for stranger, 1 for existing friend, 2 for friend request sent, 3 for friend request received, 4 for friend request rejected
  function getRelationship(string $a, string $b) {
    global $checkIfFriendsQuery;
    //check if a and b are in the friends table at all
    $hasAnyRelationship = queryDB("SELECT * FROM friends WHERE user1 = '$a' AND user2 = '$b' UNION SELECT * FROM friends WHERE user1 = '$b' AND user2 = '$a'");
    if (!$hasAnyRelationship) {

      return 0; //no relationship

    } elseif (queryDB($checkIfFriendsQuery, [":a" => $a, ":b" => $b])) { //existing friends

      return 1;

    } elseif ( queryDB("SELECT * FROM friends WHERE user1 = '$b' AND user2 = '$a' AND status = 'rejected'") ) { //I have rejected his request
      
      return 4;

    } else { //some friend request pending

      if( queryDB("SELECT * FROM friends WHERE user1 = '$b' AND user2 = '$a' AND status = 'requested'") ) { //I received the request
        return 3; 
      } else {
        return 2; //either I sent the request and it's rejected by him or pending response
      }
      
    }

  }

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