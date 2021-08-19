<head>
  <style>
    .conversationRow:hover {
      background-color: #f8f9fa;
      cursor: pointer;
    }
  </style>
</head>

<?php

if (!$isLoggedIn) {
  header( "Location: " . SITE_ROOT ) ;
  exit();
}

require_once INCLUDE_DIR . "queries.php";

$userHighlight = null;
if ( isset($_REQUEST["viewChat"]) ) {
  $userHighlight = $_REQUEST["viewChat"];
}

//display chatters list and conversations using two columns inside a row
echo "<main class='container'><main class='row'>"; //containing class

//first the chatters list which lists all persons I have had a conversation with in chronological order
echo "<section class='col-4' id='conversationList' data-user-highlight='$userHighlight'>"; //chatters list, it contains a data-point, 'data-user-highlight', to inform js if a chatter's conversation should be highlight (=null or his username)

$people = queryDB($getPeopleIHaveConversationsWithQuery, [":me" => $user]); //arrays of chatter's username and our latest chat timestamp
$numberOfConversations = count($people); //number of people I have chatted with

for ($i = 0; $i < $numberOfConversations; $i++) { //for each person that I have had conversation with

  //get variables for each conversation person
  $person = $people[$i]["who"]; //username of the person I chatted with
  $time = $people[$i]["lastTime"]; //last message timestamp
  $personInfo = queryDB($getNameAndPictureQuery, [":user" => $person])[0]; //his name and picture data
  $firstname = ucfirst(strtolower($personInfo["firstname"])); //his firstname
  $lastname = ucfirst(strtolower($personInfo["lastname"])); //first lastname
  $profilePicture = getPhotoPath($personInfo["profilePictureURL"]); //path to his profile image
  $timeElapsed = getDateTimeElapsed($time); //string showing how much time has passed since last message

  //construct a row for each person and populate with his/her profile img, name, and last msg time
  echo "<div class='row my-2 py-2 conversationRow' data-user='$person'>"; //outer container, importantly also embeds a data element for the username of who the chat is with
    echo "<div class='d-flex flex-row justify-content-center'>"; //a flex container, inner container
    echo "<img src='$profilePicture' class='img-fluid rounded-circle me-2' width='100' height='100'>"; //item 1, img
    echo "<div class='d-flex flex-column justify-content-center align-items-start'>"; //item 2, flex-col container
    echo "<div class='h5'>$firstname $lastname</div>"; //item 2-1, name nside flex-col container
    echo "<div>$timeElapsed</div>"; //item 2-2, last msg time inside flex-col container
    echo "</div>"; //close flex-col container
    echo "</div>"; //close flex-row container
  echo "</div>"; //close row

}
echo "</section>"; //end of loop, close the chatters list

//start of conversation display
echo "<section class='col-8 h-100' id='conversationDisplay'><div class='card h-100'>"; //the conversation display area
echo "<div class='card-header'>Chats <i class='bi bi-chat-dots float-end'></i></div>";
echo "<div class='card-body' id='chatPanel'></div>";
echo "<div class='card-footer'><div class='input-group'><input type='text' class='form-control' id='chatMessage'><button class='btn btn-primary' id='chatMessageBtn' type='button'>Send</button></div></div>";
echo "</div></section>"; //close conversation display area

echo "</main></main>"; //close containing class

?>
<!--required js-->
<script src="js/messages.js"></script>

