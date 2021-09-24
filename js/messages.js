/*
front-end script to handle all conversation loading using ajax.
there are two sections on page: 1. list of conversations on left, 2. display of a selected conversation on right
script contains mutiple functions from attaching Send function to send button and enter-key to loading conversations and updating real-time chats
*/

$("document").ready(function() {
  
  //register event handlers
  $(".conversationRow").click(loadInitialConversation); //onclick event, used to load conversations with the clicked user onto chatPanel on the right
  $("#chatPanel").on("click", "#loadMoreBtn", loadMoreMessages); //onclick event for button to load additional messages, delegated as button dynamically created
  $("#chatMessageBtn").click(sendMessage); //onclick event for button to send a message
 
  emulateFirstClick(); //click to initialize one of the conversations in view

}); 

/*
functon to simulate a click on the chat list to load one of the conversations
it either clicks on the user at top of list or a specified user if backend (messages.php) specifies one through a data-*
*/
function emulateFirstClick() {
  
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
function to load a conversation initially
*/
function loadInitialConversation() {

  $("#chatPanel").empty(); //empty out the chat panel (display area) first

  //set up the chatHeader
  let picture = $(this).find(".chatWithPicture").attr("src");
  let name = $(this).find(".name").text();
  
  $("#chatWithPicture").attr("src", picture);
  $("#chatWithName").text(name);

  let chatWith = $(this).data("chat-with"); //the clickable element should embed a data-element containing the username of whom the chat is with
  $("#conversationDisplay").data("user", chatWith); //add a data-* to #conversationDisplay
  let dataSend = {chatRetrieve: true, chatWith: chatWith}; //the data to send over to php using ajax
  
  $.post("ajax/messages_ajax.php", dataSend, 

    function(data) {

      let makingChatBubbles = makeChatBubbles(data);
      makingChatBubbles.then(addLoadMessageButton);

    }  

    , "json");
  
}

/*
callback function to add a button for loading additional messages if any
*/
function addLoadMessageButton(totalNumberOfMessagesAvailable) {
  
  let numberOfMessagesLoaded = $(".chat-bubble").length; //count the number of messages that have been loaded so far
  
  if (totalNumberOfMessagesAvailable > numberOfMessagesLoaded) {

    $.get("templates/messages_frontend_views.html", function(viewData){

      let id = $(".chat-bubble").first().data("db-id"); //id data for the last loaded message
      loadMoreMessageBtn = $(viewData).find("#loadMoreBtn").prop("outerHTML"); //get the button view
      
      $("#chatPanel").prepend(loadMoreMessageBtn); //add the button
      $("#loadMoreBtn").data("last-message-id", id); //add data to the button

    }); //end get async call

  } //end if

}

/*
function to request further messages based on message id and load them into view
*/
function loadMoreMessages() {

  //read data from views
  let chatWith = $("#conversationDisplay").data("user");
  let id = $("#loadMoreBtn").data("last-message-id");
  
  //remove this clicked button
  $(this).remove();

  let dataSend = {chatRetrieve: true, chatWith: chatWith, id: id};

  //ajax post call
  $.post("ajax/messages_ajax.php", dataSend, 

    function(data) {

      let makingChatBubbles = makeChatBubbles(data);
      makingChatBubbles.then(addLoadMessageButton);

    }  

    , "json");

}

/*
function to create chat bubbles and load them into view
@param message data
@return Promise
*/
function makeChatBubbles(messageData) {

  return new Promise(function(resolve, reject) {

    $.get("templates/messages_frontend_views.html", function(viewData) {
      
      //view elements
      hisChatBubble = $(viewData).find("#hisChatBubble").prop("outerHTML");
      myChatBubble = $(viewData).find("#myChatBubble").prop("outerHTML");
      thisChatBubble = ''; //var for each one chat bubble
      chatBubbles = ''; //var for multiple chat bubbles in a conversation

      //current user's username
      myself = messageData.user;

      //loop through array for each message object
      $.each(messageData.conversation, function() {
          
        timeElapsed = this.timeElapsed; 
        sender = this.sender; 
        message = this.message;
        id = this.id;

        if (sender == myself) {

          thisChatBubble = myChatBubble.replace("{{message}}", message).replace("{{time}}", timeElapsed).replace("{{id}}", id);

        } else {

          thisChatBubble = hisChatBubble.replace("{{message}}", message).replace("{{time}}", timeElapsed).replace("{{id}}", id);

        }

        chatBubbles += thisChatBubble; //concatenate current bubble

      }); //end of each

      $("#chatPanel").prepend(chatBubbles);

      resolve(messageData.total);

    }); //end get async call

  }); //end Promise

} //end function

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
link the enter key press to send button click
*/
$("#chatMessage").keypress( function(keyEvent) {

  let keypressed = keyEvent.keyCode ? keyEvent.keyCode : keyEvent.which;
  
  if (keypressed == "13") { //if enter key is pressed

    $("#chatMessageBtn").click(); //click the send button

  }

});

/*
function for sending a message
*/
function sendMessage() {
    
  let msg = $("#chatMessage").val().toString(); //raw user message
  let recipient = $("#conversationDisplay").data("user"); //retrieve the data-user tag value from #conversationDisplay inserted when .conversationRow is clicked
  let dataSend = {sendMessage: true, message: msg, recipient: recipient};
  
  if (msg.trim() != "") { //if the message is not empty, post it to php script

    $.post( "ajax/messages_ajax.php", dataSend, function(result) {

      if (!result.success) {
        alert("Oops! Something's wrong on our side."); 
      } else {       
        $("#chatMessage").val(""); 
        $("#chatMessage").focus();
      }

    }, "json");
          
  }

}

