<?php 

require_once INCLUDE_DIR . "queries.php";
require_once INCLUDE_DIR . "functions.php";
require_once CLASS_DIR . "Message.php";

class Conversation {

 private $user; //this user
 private $chatWith; //that user
 private $messages = []; //array of messages in the conversation
 private $numberOfMessages; //conversation length

 public function __construct($user, $chatWith) {
  $this->user = $user;
  $this->chatWith = $chatWith;
  $this->messages = $this->retrieveMessages();
  $this->numberOfMessages = count( $this->messages );
 }

 public function getMessages(): array {
  return $this->messages; 
 }

 /*function to retrieve messages in a conversation
 @return array of Message objects
 */
 private function retrieveMessages(): array {
  
  global $getMyConversationWithQuery;
  $messages = [];
  $params = [":chatWith" => "$this->chatWith", ":me" => "$this->user"];
  $conversation = queryDB($getMyConversationWithQuery, $params); //get the conversation from database
  $numberOfMessages = count($conversation);

  for ($i = 0; $i < $numberOfMessages; $i++) {
    $messageObj = new Message($conversation[$i]["sender"], $conversation[$i]["recipient"], $conversation[$i]["timestamp"], $conversation[$i]["message"]);
    array_push($messages, $messageObj); 
  }

  return $messages;

 }

 /*function to retrieve messages since a specified time
 @param $since epoch timestamp representing since when messages to retrieve
 @return array of Message objects
 */
 public function getMessagesSince(int $since): array {

  global $getMyConversationWithSinceQuery;
  $messages = [];
  $params = [":chatWith" => $this->chatWith, ":me" => $this->user, ":since" => $since];
  $messagesSince = queryDB($getMyConversationWithSinceQuery, $params); //get the conversation since last timestamp 

  if ($messagesSince) { //if there are messages since the timestamp
    $n = count($messagesSince);

    for ($i = 0; $i < $n; $i++) {
      $messageObj = new Message($messagesSince[$i]["sender"], $messagesSince[$i]["recipient"], $messagesSince[$i]["timestamp"], $messagesSince[$i]["message"]);
      array_push($messages, $messageObj);
    }
    
  }
  
  return $messages;  

 }

 //function to return the most recent message in a conversation
 public function getNewestMessage() {
  $messages = $this->messages;
  usort($messages, array($this, "messageComparator"));
  return array_pop($messages);
 }

 //function to return the oldest message in a conversation
 public function getOldestMessage() {
  $messages = $this->messages;
  usort($messages, array($this, "messageComparator"));
  return array_shift($messages);
 }

 //getter for number of messages
 public function getNumberOfMessages() {
  return $this->numberOfMessages;
 }

 //comparator function for sorting messages, more recent message (higher timestamp) is defined to be larger
 function messageComparator($a, $b) {
  $timeDiff = intval($a->timestamp) - intval($b->timestamp);
  return $timeDiff >= 0 ? 1 : -1;
 }


}


?>