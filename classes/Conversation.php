<?php 

class Conversation {

  private $user; //this user
  private $chatWith; //that user
  private $messages = []; //array of messages in the conversation
  private $numberOfMessages; //conversation length
  private $mysql; //object for mysql database access

  /*
  constructor for a Conversation between two users
  */
  public function __construct(string $user, string $chatWith) {

    $this->mysql = MySQL::getInstance();
    $this->user = $user;
    $this->chatWith = $chatWith;
    $this->messages = $this->retrieveMessages();
    $this->numberOfMessages = count( $this->messages );

  }

  /*
  getter for messages
  @return array of Message objects
  */
  public function getMessages(): array {

    return $this->messages; 

  }

 
  /*helper function to retrieve messages in a conversation
  @return array of Message objects
  */
  private function retrieveMessages(): array {

    $out = [];
    $params = [":chatWith" => "$this->chatWith", ":me" => "$this->user"];
    $resultset = $this->mysql->request($this->mysql->readConversationWithQuery, $params); //get the conversation from database

    foreach ($resultset as $row) {

      $messageObj = new Message($row["sender"], $row["recipient"], $row["timestamp"], $row["message"]);
      array_push($out, $messageObj); 

    }

    return $out;

  }

  /*function to retrieve messages since a specified time
  @param $since epoch timestamp representing since when messages to retrieve
  @return array of Message objects
  */
  public function getMessagesSince(int $since): array {

    $out = [];
    $params = [":chatWith" => $this->chatWith, ":me" => $this->user, ":since" => $since];
    $resultset = $this->mysql->request($this->mysql->readConversationWithSinceQuery, $params); //get message data since last timestamp 

    if ($resultset) { //if there are messages since the timestamp

      foreach ($resultset as $row) {

        $messageObj = new Message($row["sender"], $row["recipient"], $row["timestamp"], $row["message"]);
        array_push($out, $messageObj);

      }
      
    }
    
    return $out;  

  }

  /*
  get the most recent Message in the Conversation
  @return Message
  */
  public function getNewestMessage(): Message {

    $messages = $this->messages; //copy this Conversation's Messages to a local var
    usort($messages, array($this, "messageComparator")); //sort to asceding order
    return array_pop($messages); //get the last element

  }

  /*
  get the oldest Message in the Conversation
  @return Message
  */
  public function getOldestMessage(): Message {

    $messages = $this->messages;
    usort($messages, array($this, "messageComparator")); //sort to asscending order
    return array_shift($messages); //get the first element

  }

  /*
  getter for number of messages
  @return number of messages in this Conversation
  */
  public function getNumberOfMessages(): int {

    return $this->numberOfMessages;

  }

  /*
  Comparator function for sorting Message objs based on timestamp
  more recent message (larger timestamp) is defined to be larger
  */
  public function messageComparator(Message $a, Message $b) {

    $timeDiff = intval($a->timestamp) - intval($b->timestamp);
    
    if ($timeDiff == 0) {
      return 0;
    } elseif ($timeDiff > 0) { //when first Msg is larger than second
      return 1;
    } else {  //when second Msg is larger than first
      return -1;
    }

  }
 


} //end of class


?>