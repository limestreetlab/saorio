
$(".friendRequestConfirmationBtn").click( function() {

  //retrieve data from the friend-confirm button and assign to js
  var myself = String($(this).data('user'));
  var myFirstname = String($(this).data('user-firstname'));
  var requestFrom = String($(this).data('request-from'));
  var requestFromFirstname = $(this).data('request-from-firstname');
  var requestFromLastname = $(this).data('request-from-lastname');
  
  //customize friend request contents
  var titleText = "Friend request from " + requestFromFirstname + " " + requestFromLastname;
  $("#friendRequestConfirmationModalTitle").text(titleText);
  var bodyText = "Hello " + myFirstname + ", \n I would like to add you as a friend." ; //in the future can allow a customized msg when adding a friend
  $("#friendRequestConfirmationModalBody").text(bodyText);

  //reject button click handler, first unbind old ones and then bind this one, else there will be multiple handlers bound to the same element
  $("#friendRequestConfirmationModalRejectBtn").off("click"); //unregister attached click handlers, if any
  
  $("#friendRequestConfirmationModalRejectBtn").on("click", function() { //register a handler for this member

    var dataSend = {user2: myself, user1: requestFrom, status: "rejected"}; //data to send
    $.post("ajax/members_ajax.php", dataSend, updateRelationshipStatus(requestFrom, 4) );

  }); //close reject button click handler

  //accept button click handler, first unbind old ones and then bind this one, else there will be multiple handlers bound to the same element
  $("#friendRequestConfirmationModalAcceptBtn").off("click"); //unregister attached click handlers, if any
  
  $("#friendRequestConfirmationModalAcceptBtn").on("click", function() { //register a handler for this member

    var dataSend = {user2: myself, user1: requestFrom, status: "confirmed"}; //data to send
    $.post("ajax/members_ajax.php", dataSend, updateRelationshipStatus(requestFrom, 1) );

  }); //close accept button click handler
  
});//close outer click handler


//callback function to update the relationship button on members.php
function updateRelationshipStatus(mUser, relationshipCode) {
  //same codes as ones used in members.php
  var rejected = "<button type='button' class='mt-3 btn btn-primary btn-sm' disabled>Rejected</button>";
  var confirmed = "<button type='button' class='mt-3 btn btn-outline-primary btn-sm' disabled>You're Friends</button>";
  //the relationship button is between <div id="relationshipWith$mUser"></div>
  var relationshipWith = "#relationshipWith" + mUser; //the ID selector of this relationship
  
  switch (relationshipCode) {
    case 1: 
      $(relationshipWith).html(confirmed);
      break;
    case 4:
      $(relationshipWith).html(rejected);
      break;
    default:
      console.log("Error: relationshipCode param passed must be either 1 or 4, for confirmed or rejected.");
  }
  
}
