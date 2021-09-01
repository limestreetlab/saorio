
//frontend function to respond to a friend request
$(".friendRequestConfirmationBtn").click( function() {

  //retrieve data embedded in the friend-confirm button and assign to js
  var requestFrom = String($(this).data('request-from'));
  var requestFromFullname = $(this).data('request-from-fullname');
  
  //create a friend message for the modal
  var titleText = "Friend request from " + requestFromFullname;
  $("#friendRequestConfirmationModalTitle").text(titleText);
  var bodyText = "Hello, \n" + "I would like to add you as a friend." ; //in the future can allow a customized msg when adding a friend
  $("#friendRequestConfirmationModalBody").text(bodyText);

  //because the same modal is used for requests from all members, its handlers must first unregister old ones and re-register using current data, else old handlers would be triggered as well
  $("#friendRequestConfirmationModalRejectBtn").off("click"); //unregister attached reject btn click handlers, if any
  $("#friendRequestConfirmationModalAcceptBtn").off("click"); //unregister attached accept btn click handlers, if any

  //register handlers for the two buttons (accept, reject), data to send to server are the requester's username and accept/reject action (>0 for accept, <0 for reject)
  $("#friendRequestConfirmationModalRejectBtn").on("click", function() { //register reject btn handler

    var dataSend = {requestFrom: requestFrom, action: -1}; //<0 value for reject
    $.post("ajax/members_ajax.php", dataSend, updateRelationshipBtn(requestFrom, 0) );

  }); //close reject button handler

  
  $("#friendRequestConfirmationModalAcceptBtn").on("click", function() { //register accept btn handler

    var dataSend = {requestFrom: requestFrom, action: 1}; //>0 for accept
    $.post("ajax/members_ajax.php", dataSend, updateRelationshipBtn(requestFrom, 1) );

  }); //close accept button handler
  
});//close outer click handler


//callback function to update the relationship button on members.php
function updateRelationshipBtn(hisUsername, relationshipCode) {
  //same codes as ones used inside members.php
  var stranger = "<button type='button' class='mt-3 btn btn-primary btn-sm'>Add Friend</button>"; 
  var friend = "<button type='button' class='mt-3 btn btn-outline-primary btn-sm' disabled>Your Friend</button>";
  //the relationship button is between <div id='relationshipWith{{hisUsername}}'></div>
  var relationshipWith = "#relationshipWith" + hisUsername; //the ID selector of this relationship
  
  switch (relationshipCode) {
    case 1: 
      $(relationshipWith).html(friend);
      break;
    case 0:
      $(relationshipWith).html(stranger);
      break;
    default:
      console.log("Error: relationshipCode param passed must be either 0 or 1, for stranger or friend.");
  }
  
}
