/*
front-end script to handle all conversation loading using ajax.
there are two sections on page: 1. list of conversations on left, 2. display of a selected conversation on right
script contains mutiple functions from attaching Send function to send button and enter-key to loading conversations and updating real-time chats
*/

//file-wide variables
var loadMoreBtn = "<button type='button' class='btn btn-outline-secondary rounded-pill mb-5 border-0' id='loadMoreBtn' data-last-message-id=''><i class='bi bi-arrow-up-square'></i> Previous messages</button>";


$("document").ready(function() {
 
  initialClick(); //click to load one of the conversations

}); 

/*
functon to simulate a click on the chat list to load one of the conversations
it either clicks on the user at top of list or a specified user if backend (messages.php) specifies one through a data-*
*/
function initialClick() {

  let firstPerson = $(".conversationRow").first().data("chat-with"); //the data-chat-with value of first .conversationRow element
  let specificPerson = $("#conversations").data("highlight"); //the person to highlight specified by backend, if any
  let highlight = firstPerson; //default value
  
  if (specificPerson) { //someone is specified
    highlight = specificPerson; 
  } 

  let conversationRowToHighlight = ".conversationRow[data-chat-with='" + highlight + "']"; //make the query selector string
  
  $(conversationRowToHighlight).trigger("click"); //triggering a click event on that target element

} //close function

/*
onclick event, used to load conversations with the clicked user onto chatPanel on the right
*/
$(".conversationRow").click( function(){
  
  $("#chatPanel").empty(); //empty out the chat panel (display area) first

  let picture = $(this).find(".chatWithPicture").attr("src");
  let name = $(this).find(".name").text();
  $("#chatWithPicture").attr("src", picture);
  $("#chatWithName").text(name);

  let chatWith = $(this).data("chat-with"); //the clickable element should embed a data-element containing the username of whom the chat is with
  $("#conversationDisplay").data("user", chatWith); //add a data-* to #conversationDisplay
  let dataSend = {chatRetrieve: true, chatWith: chatWith}; //the data to send over to php using ajax
  
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

      $("#chatPanel").prepend(loadMoreBtn);
      
    } //close callback 
    , "json"); //close $.post 
  
  updateChat(); //after a conversation is opened, update it automatically

}); //close onclick

/*
function to repeatedly check for new chat messages and display if any
*/
function updateChat() {
  
  let chatWith = $("#conversationDisplay").data("user");//by design, the active conversation has its chatter's username embedded in a data-user attribute
  let dataSend = {chatUpdate: true, chatWith: chatWith};
  
  $.post("ajax/messages_ajax.php", dataSend,  
    //start callback
    function(dataReceive) { 
      //will receive json of [user, [newMessages]]
      if (dataReceive.newMessages.length > 0) { //there are new messages
        
        let myself = dataReceive.user; //the $user in the session
        //for each message exchanged between me and the other person (who)
        $.each(dataReceive.newMessages, function() {
        
          let timeElapsed = this.timeElapsed; 
          let sender = this.sender; 
          let message = this.message;

          let chatBubble = makeChatBubble(myself, sender, message, timeElapsed);

          $("#chatPanel").append(chatBubble); //add the chat to the chat panel for display
        }); //close $.each
      } //close if 
    } //close callback
    
  , "json"); //close request

  setTimeout(updateChat, 3000); //call self at timeout
  
} //close function

/*
helper function to create chat bubbles
*/
function makeChatBubble(myself, sender, message, timeElapsed) {

  let startOrEnd; //string of either 'start' or 'end', for bootstrap's justify-content-start/justify-content-end
  let chatBubble;
  
  if (myself == sender) {
    startOrEnd = "end"; 
  } else {
    startOrEnd = "start";
  }

  //unfortunately, Bootstrap style is mixed here
  chatBubble = "<div class='row ms-1 me-1 mb-2 card-text justify-content-" + startOrEnd + "'><div class='col-5'><div class='p-3 text-start'>" +
                message + "</div><div class='message-time mt-0 me-3 text-muted w-100 text-end'>" + timeElapsed + "</div></div></div>";
  
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
  
  if (msg.trim() != "") { //if the message is not empty, post it to php script
    $.post( "ajax/messages_ajax.php", dataSend, function(result) {
            if (!result.success) {
              alert("Error occurred in our system. Sorry for the inconvenience."); 
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

