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
  @param $id, the message id from which to start retrieving messages 
  @param $length, the number of messages to retrieve
  @param $forwardRetrieval, when a retrival id is provided, it indicates whether messages should be retrieved forward (newer than) or backward (older than) from that message
  @param $inclusive, when a retrieval id is provided, it indicates whether the IDed message should be included or excluded
  @return array of Message objects or null if id isn't found
  */
  public function getMessages(?int $length = null, ?int $id = null, bool $forwardRetrieval = true, bool $inclusive = true): ?array {

    //if no id and length provided, return all messages in the conversation
    if (is_null($length) && is_null($id)) {

      return $this->messages;

    }

    if (is_null($id)) { //id not provided, only length is

      return $length < $this->numberOfMessages ? array_slice($this->messages, $this->numberOfMessages - $length, $length) : $this->messages;      

    } else { //id provided

      $index = $this->getMessageIndex($id);

      //if id not found in messages
      if (is_null($index)) {
        return null;
      }

      if ($forwardRetrieval) { //messages should be retrieved forward

        if ($length) { //forward with length

          return $inclusive ? array_slice($this->messages, $index, $length) : array_slice($this->messages, $index + 1, $length) ; 

        } else { //forward without length

          return $inclusive ? array_slice($this->messages, $index) : array_slice($this->messages, $index + 1); 

        }

      } else { //messages should be retrieved backward

        if ($length) { //backward with length

          return $inclusive ? array_slice( $this->messages, MAX($index - $length + 1, 0), MIN($length, $index + 1 ) ) : array_slice( $this->messages, MAX($index - $length, 0), MIN($length, $index ) ); 

        } else { //backward without length

          return $inclusive ? array_slice($this->messages, 0, $index + 1) : array_slice($this->messages, 0, $index); 

        }

      }

    }

  }

 
  /*helper function to retrieve messages in a conversation
  @return array of Message objects
  */
  private function retrieveMessages(): array {

    $messages = [];
    $params = [":chatWith" => "$this->chatWith", ":me" => "$this->user"];
    $resultset = $this->mysql->request($this->mysql->readConversationWithQuery, $params); //get the conversation from database

    foreach ($resultset as $row) {

      $messageObj = new Message($row["sender"], $row["recipient"], $row["timestamp"], $row["message"], $row["id"]);
      array_push($messages, $messageObj); 

    }

    return $messages;

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

        $messageObj = new Message($row["sender"], $row["recipient"], $row["timestamp"], $row["message"], $row["id"]);
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
  get the index in $messages array for a message id
  in other words, given a message id, it returns index to retrieve that message from $messages array
  @param $id the message id
  @return index number in the $messages array or null if id is not found
  */
  protected function getMessageIndex(int $id): ?int {

    //filter the message array using id equality
    $filteredArray = array_filter($this->messages, function($msg) use($id) {
      return $msg->id == $id;
    });

    //if id is found, return the array index of that element
    return count($filteredArray) > 0 ? array_keys($filteredArray)[0] : null;

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