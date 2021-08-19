//script to handle multiple messaging functions from attaching Send to send button and enter key to loading conversations for each person and updating chats
$(document).ready(function() {
 
  initialClick(); //click to load one of the conversations

}); //close document ready

/*
functon to simulate a click on the chatter list to load one of the chatter's conversations
it either clicks on the user at the top of the list or a particular user when the backend (messages.php) specifies one through a data-* inside the form
*/
function initialClick() {

  let firstPerson = $(".conversationRow").first().data("user"); //the data-user value of the first .conversationRow element
  let specificPerson = $("#conversationList").data("user-highlight"); //the person to highlight specified by backend, if any

  if (specificPerson) { //someone is specified
    userHighlight = specificPerson; 
  } else { 
    userHighlight = firstPerson; 
  }

  let conversationRowToHighlight = ".conversationRow[data-user='" + userHighlight + "']"; //make the query selector string for the element having data-user=firstPerson
  $(conversationRowToHighlight).trigger("click"); //triggering a click event on that target element

} //close function

/*
onclick event, used to load conversations with the clicked user onto chatPanel 
*/
$(".conversationRow").click( function(){
  
  $("#chatPanel").empty(); //empty out the chat panel first

  let chatter = $(this).data("user"); //the clickable element should embed a data-element (data-user) containing the username of the chat is with
  $("#conversationDisplay").data("user", chatter); //add a data-user=person tag to #conversationDisplay
  let dataSend = {chatRetrieve: true, chatWith: chatter}; //the data to send over to php using ajax
  
  $.post("ajax/messages_ajax.php", dataSend, 
  //start callback
  function(dataReceive) { 
      
      //variable declarations
      let myself = dataReceive.user; //the $user in php
      let sender; //username of a msg sender
      let message; //the msg content
      let timeElapsed; //msg since 
      let chatBubble; //the msg display UI
      
      //for each message exchanged between me and the other person (who)
      $.each(dataReceive.conversation, function() {
        
        timeElapsed = this.timeElapsed; 
        sender = this.sender; 
        message = this.message;
        chatBubble = makeChatBubble(myself, sender, message, timeElapsed);

        $("#chatPanel").append(chatBubble); //add the chat to the chat panel for display

      }); //close $.each
      
    } //close callback 
    , "json"); //close $.post 
  
  updateChat(); //after a conversation is opened, update it automatically
}); //close onclick

/*
function to repeatedly check for new chat messages and display if any
*/
function updateChat() {
  
  let chatter = $("#conversationDisplay").data("user");//by design, the active conversation has its chatter's username embedded in a data-user attribute
  let dataSend = {chatUpdate: true, chatWith: chatter};
  
  $.post("ajax/messages_ajax.php", dataSend,  
    //start callback
    function(dataReceive) { 
      
      //will receive json of [hasNewChats: bool, user: str, and conversation: arr]
      if (dataReceive.hasNewChats) {
        
        let myself = dataReceive.user; //the $user in the session
        //for each message exchanged between me and the other person (who)
        $.each(dataReceive.conversation, function() {
        
          let timeElapsed = this.timeElapsed; 
          let sender = this.sender; 
          let message = this.message;
          let chatBubble = makeChatBubble(myself, sender, message, timeElapsed);

          $("#chatPanel").append(chatBubble); //add the chat to the chat panel for display
        }); //close $.each
      } //if close 
    } //close callback
    
  , "json"); //close request

  setTimeout(updateChat, 4000); //call self at a timeout
  
} //close function

/*
helper function to create chat bubbles
*/
function makeChatBubble(myself, sender, message, timeElapsed) {

  let startOrEnd; //string of either 'start' or 'end', for bootstrap's justify-content-start/justify-content-end, text-start/text-end
  let chatBubble;
  
  if (myself == sender) {
    startOrEnd = "end"; 
  } else {
    startOrEnd = "start";
  }

  chatBubble = "<div class='row card-text justify-content-" + startOrEnd + "'><div class='col-6 text-" + startOrEnd + "'>" +
                message + "<div class='small text-muted'>" + timeElapsed + "</div></div></div>";
  
  return chatBubble;

}

/*
click listender for sending chat messages
*/
$( "#chatMessageBtn" ).click( function(clickEvent) {
  clickEvent.preventDefault();
  
  let msg = $("#chatMessage").val().toString(); //raw user message
  let recipient = $("#conversationDisplay").data("user"); //retrieve the data-user tag value from #conversationDisplay inserted when .conversationRow is clicked
  let dataSend = {sendMessage: true, message: msg, recipient: recipient};
  
  if (msg.trim() != "") { //if the message is not empty, post it tp php script
    $.post( "ajax/messages_ajax.php", dataSend, function(result) {
            if (!result.success) {
              alert("Error occurred and message couldn't be sent"); 
            } else {       
              $("#chatMessage").val(""); 
              $("#chatMessage").focus();
            }
          }, "json");
  }

}); //close onclick

/*
link the enter key press to send button click
*/
$( "#chatMessage" ).keypress( function(keyEvent) {
  let keypressed = keyEvent.keyCode ? keyEvent.keyCode : keyEvent.which;
  
  if (keypressed == "13") { //if enter key is pressed
    $("#chatMessageBtn").click(); //click the send button
  }

});

