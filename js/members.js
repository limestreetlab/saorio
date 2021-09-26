$("document").ready(function() {
  //bind to the container of all elements to delegate event handling rather than directly onto the elements themselves, as they might be dynamically loaded and not exist at render time
  $(".relationshipBtn").on("click", ".friendRequestBtn", sendFriendRequest); 
  $(".relationshipBtn").on("mouseenter", ".friendRequestSentBtn", showRequestCancelBtn); 
  $(".relationshipBtn").on("mouseleave", ".friendRequestCancelBtn", reshowRequestSentBtn); 
  $(".relationshipBtn").on("click", ".friendRequestCancelBtn", cancelFriendRequest); 
  $(".relationshipBtn").on("click", ".friendRequestConfirmationBtn", confirmFriendRequest); 

});

/*
function to send a friend request from the current user to a specific user
*/
function sendFriendRequest() {
  
  let sendRequestTo = $(this).data("send-request-to");
  let dataSend = {sendRequestTo: sendRequestTo};
  $.post("ajax/members_ajax.php", dataSend, function(result) {
    result.success ? updateRelationshipBtn(sendRequestTo, 2) : $("#toast-failure").toast('show');
  }, "json");

}

/*
function to display the default hidden cancel request btn
*/
function showRequestCancelBtn() {

  $(this).addClass("d-none");
  $(this).siblings(".friendRequestCancelBtn").removeClass("d-none");

}

/*
function to undo whatever done to display the hidden cancel request btn
*/
function reshowRequestSentBtn() {

  $(this).addClass("d-none");
  $(this).siblings(".friendRequestSentBtn").removeClass("d-none");

}

/*
function to cancel a previously sent friend request to a specific user
*/
function cancelFriendRequest() {

  let cancelRequestTo = $(this).data('cancel-request-to');
  let dataSend = {cancelRequestTo: cancelRequestTo};
  $.post("ajax/members_ajax.php", dataSend, function(result) {
    result.success ? updateRelationshipBtn(cancelRequestTo, 0) : $("#toast-failure").toast('show');
  }, "json");

}

/*
function to respond to a received friend request from a specific user
it displays a BS modal containing a request message and an accept and a reject buttons
on accept btn click, friendship status will be updated
on reject btn click, friendship record will be deleted
*/
function confirmFriendRequest() {

  //retrieve data embedded in the friend-confirm button and assign to js
  let requestFrom = $(this).data('request-from');
  let requestFromFullname = $(this).data('request-from-fullname');
  
  //create a friend message for the modal
  let titleText = "Friend request from " + requestFromFullname;
  $("#friendRequestConfirmationModalTitle").text(titleText);
  let bodyText = "Hello, <br> I would like to add you as a friend. Please respond." ; //in the future can allow a customized msg when adding a friend
  $("#friendRequestConfirmationModalBody").html(bodyText);

  //register handlers for the two buttons (accept, reject), data to send to server are the requester's username and accept/reject action (>0 for accept, <0 for reject)
  $("#friendRequestConfirmationModalRejectBtn").off("click").on("click", function() { //register reject btn handler

    let dataSend = {requestFrom: requestFrom, action: "reject"}; 
    $.post("ajax/members_ajax.php", dataSend, function(result) {
      result.success ? updateRelationshipBtn(requestFrom, 0) : $("#toast-failure").toast('show'); 
    }, "json");

  }); //close reject button handler

  
  $("#friendRequestConfirmationModalAcceptBtn").off("click").on("click", function() { //register accept btn handler

    let dataSend = {requestFrom: requestFrom, action: "accept"}; 
    $.post("ajax/members_ajax.php", dataSend, function(result) {
      result.success ? updateRelationshipBtn(requestFrom, 1) : $("#toast-failure").toast('show');
    }, "json");

  }); //close accept button handler

} //end function

/*
callback to update the relationship button in frontend
@param hisUsername, the username of the other user of this relationship
@param relationshipCode, the defined code to update the relationship to
*/
function updateRelationshipBtn(hisUsername, relationshipCode) {

  fetch("templates/members_card.html")
  .then(view => view.text())
  .then(function(view) {
    
    let buttonName;
    switch(relationshipCode) {
      case 0:
        buttonName = "friendRequestBtn"; //class name of the button to send a friend request
        break;
      case 1:
        buttonName = "friendAlreadyBtn"; //class name of the button to indicate existing friend
        break;
      case 2:
        buttonName = "friendRequestSentAndCancelBtnSet"; //class name of the buttons to indicate request sent and cancel a request
        break;
    }
    
    let relationshipBtn = "#relationshipWith" + hisUsername; //the ID selector of this relationship
    let button = $(view).find("." + buttonName).prop("outerHTML").replace('{{hisUsername}}', hisUsername);
    $(relationshipBtn).html(button);

  });
  
}
