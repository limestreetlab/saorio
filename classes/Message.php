<?php 

class Message {

  public $sender;
  public $recipient;
  public $timestamp;
  public $timeElapsed;
  public $message;
  protected $mysql; //object for mysql database access

  public function __construct(string $sender, string $recipient, int $timestamp, string $message) {

    $this->sender = $sender;
    $this->recipient = $recipient;
    $this->timestamp = $timestamp;
    $this->timeElapsed = self::getDateTimeElapsed( intval($this->timestamp) );
    $this->message = $message;
    $this->mysql = MySQL::getinstance();

  }

  /*
  send a Message obj to database
  @return success
  */
  public function send(): bool {

    $this->message = filter_var(trim($this->message), FILTER_SANITIZE_STRING); //trim, sanitize
    $params = [":time" => $this->timestamp, ":from" => $this->sender, ":to" => $this->recipient, ":message" => $this->message];

    try {
      $this->mysql->request($this->mysql->createMessageQuery, $params);
      $success = true;
    } catch (Exception $ex) {
      $success = false;
      throw $ex;
    }

    return $success;
  
  }

  /*
  get all instance varibles of a Message obj
  @return associative array ["sender", "recipient", "message", "timeElapsed", "timestamp"]
  */
  public function read(): array {
    return ["sender" => $this->sender, "recipient" => $this->recipient, "message" => $this->message, "timeElapsed" => $this->timeElapsed, "timestamp" => $this->timestamp];
  }


  /*
  Class function to return the largest datetime unit (year, week, day, miniute, second) has passed for a Unix timestamp
  For example, if for an input epoch, 2 years 3 months 8 days have passed, it returns 2 years; if 1 day 3 hours, it returns 1 day.
  @param $int epoch timestamp in seconds
  @return a string containing the largest datetime unit elapsed
  */
  protected static function getDateTimeElapsed(int $epoch): string {

    $dtNow = new DateTime(); //DateTime obj for now
    $dtThen = new DateTime("@$epoch"); //DateTime obj for input epoch
    $diff = (array)($dtNow->diff($dtThen)); //calculate difference and cast into array
    //get the datetime units, note there is no week unit
    $year = $diff["y"];
    $month = $diff["m"];
    $day = $diff["d"];
    $hour = $diff["h"];
    $minute = $diff["i"];
    $second = $diff["s"];

    //if if-else block to waterfall through to the largest non-empty unit
    if (!empty($year)) {
      $value = $year;
      $unit = "year";
    } elseif (!empty($month)) {
      $value = $month;
      $unit = "month";
    } elseif (!empty($day)) {
      if ( (int)($day/7) > 0 ) {
        $value = (int)($day/7);
        $unit = "week";
      } else {
        $value = $day;
        $unit = "day";
      }
    } elseif (!empty($hour)) {
      $value = $hour;
      $unit = "hour";
    } elseif (!empty($minute)) {
      $value = $minute;
      $unit = "minute";
    } else {
      $value = $second;
      $unit = "second";
    }

    if ($unit == "second" && $value < 60) { //in case less than 60 seconds
      $output = "just now"; //call the output just now
    } else { 
      $unit =  $value > 1 ? $unit . "s" : $unit; //add a s (plural) if datetime unit above 1
      $output = "$value $unit" . " ago"; 
    }
    
    return ($output); 

  }



} //end class

?>