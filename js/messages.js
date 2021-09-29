/*
front-end script to handle all conversation loading using ajax.
there are two sections on page: 1. list of conversations on left, 2. display of a selected conversation on right
script contains mutiple functions from attaching Send function to send button and enter-key to loading conversations and updating real-time chats
*/

$("document").ready(function() {
  
  //register event handlers
  $(".conversationRow").on("click", loadInitialConversation); //onclick event, used to load conversations with the clicked user onto chatPanel on the right
  $("#chatPanel").on("click", "#loadMoreBtn", loadMoreMessages); //onclick event for button to load additional messages, delegated as button dynamically created
  $("#chatMessageBtn").on("click", sendMessage); //onclick event for button to send a message
  $("#chatMessage").on("keypress", enterToSendMessage); //enterkey pressed inside message form equivalent to a send btn click
 
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
handler to load a conversation initially when one is selected
*/
function loadInitialConversation() {

  $("#chatPanel").empty(); //empty out the chat panel (display area) first

  //setting up the chat panel header with picture and name
  let picture = $(this).find(".chatWithPicture").attr("src");
  let name = $(this).find(".name").text();
  
  $("#chatWithPicture").attr("src", picture);
  $("#chatWithName").text(name);

  //get data for ajax call
  let chatWith = $(this).data("chat-with"); //the clickable element should embed a data-element containing the username of whom the chat is with
  $("#conversationDisplay").data("user", chatWith); //add a data-* to #conversationDisplay
  let dataSend = {chatRetrieve: true, chatWith: chatWith}; //the data to send over to php using ajax
  
  $.get("ajax/messages_ajax.php", dataSend, 

    async function(data) {

      let totalMessages = await makeChatBubbles(data);
      addLoadMessageButton(totalMessages);

    }  

    , "json");

  updateChat();
  
}

/*
handler to request further messages based on message id and load them into view when an id-embedded button is clicked
*/
function loadMoreMessages() {

  //read data from views
  let chatWith = $("#conversationDisplay").data("user");
  let id = $("#loadMoreBtn").data("last-message-id");
  
  //remove the clicked button
  $(this).remove();

  let dataSend = {chatRetrieve: true, chatWith: chatWith, id: id};

  //ajax post call
  $.get("ajax/messages_ajax.php", dataSend, 

    async function(data) {

      let totalMessages = await makeChatBubbles(data);
      addLoadMessageButton(totalMessages);

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
asynchronous function to create chat bubbles and load them into view by prepending
suitable only for loading initial and previous messages, but not new messages after first load
output is a promise object, because caller may want to wait for code completion before proceeding
@param message data
@return Promise
*/
function makeChatBubbles(messageData) {

  let promise = new Promise(function(resolve, reject) {
    //async call to load templated view elements 
    $.get("templates/messages_frontend_views.html", function(viewData) {
      
      //view elements
      let hisChatBubble = $(viewData).find("#hisChatBubble").prop("outerHTML");
      let myChatBubble = $(viewData).find("#myChatBubble").prop("outerHTML");
      let thisChatBubble = ''; //var for each one chat bubble
      let chatBubbles = ''; //var for multiple chat bubbles in a conversation

      //current user's username and message data
      let myself = messageData.user;
      let sender;
      let message;
      let timeElapsed;
      let id;

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

      $("#chatPanel").prepend(chatBubbles); //display all the chat bubbles created for the received messages

      resolve(messageData.total); //return and embed total number of messages for the conversation

    }); //end get async call

  }); //end Promise

  return promise;

} //end function

/*
asynchronous function to create a chat bubble and return it as Promise to caller without loading it into view
@param message data
@return Promise containing a chat bubble string
*/
function getChatBubble(self, sender, message, timeElapsed, id) {

  let promise = new Promise(function(resolve, reject) {

    $.get("templates/messages_frontend_views.html", function(viewData) {

      if (sender == self) {

        var html = $(viewData).find("#myChatBubble").prop("outerHTML");
        
      } else {

        var html = $(viewData).find("#hisChatBubble").prop("outerHTML");

      }

      let output = html.replace("{{message}}", message).replace("{{time}}", timeElapsed).replace("{{id}}", id);
      
      resolve(output);

    }); //end ajax get
  
  }); //end Promise

  return promise;

}

/*
function to repeatedly check for new chat messages and add them to display
*/
function updateChat() {
  
  let chatWith = $("#conversationDisplay").data("user");//by design, the active conversation has its chatter's username embedded in a data-user attribute
  let dataSend = {chatUpdate: true, chatWith: chatWith};
  
  //request new chat messages
  $.get("ajax/messages_ajax.php", dataSend,  
    
    function(data) { 
      
      //will receive json of [user, [newMessages]]
      if (data.newMessages.length > 0) { //there are new messages
        
        let myself = data.user; //current user in the session
        //for each message exchanged
        $.each(data.newMessages, async function() {
        
          let timeElapsed = this.timeElapsed; 
          let sender = this.sender; 
          let message = this.message;
          let id = this.id;
          
          let chatBubble = await getChatBubble(myself, sender, message, timeElapsed, id); //get a bubble string
          
          $("#chatPanel").append(chatBubble); //append this bubble to display
          document.querySelector("[data-db-id='" + id + "']").scrollIntoView(true); //get the added chat element and scroll it into view
          
        }); //close $.each
      } //close if 
    } //close callback
    
  , "json"); //close request

  setTimeout(updateChat, 1000); //repeatedly calling self at set interval
  
} //close function

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

/*
function to check if a keypress inside message box is enterkey, if then do a button click
*/
function enterToSendMessage(keyEvent) {

  //get the key pressed
  let keypressed = keyEvent.keyCode ? keyEvent.keyCode : keyEvent.which;
  
  if (keypressed == "13") { //if enter key is pressed

    $("#chatMessageBtn").click(); //generate a click on the send button

  }

}

