<?php 

require_once INCLUDE_DIR . "queries.php";
require_once CLASS_DIR . "Friendship.php";

class User {
 
  //variables declaration
  public $user; //username
  protected $profile; //profile object
  protected $friends = []; //array of other user objects
  protected $numberOfFriends; //length of the friends array
  
  //constructor
  public function __construct($user) {
    $this->user = $user;    
  }

  //function to get this user's friends
  //@return array of User objects
  public function getFriends(): array {
    
    $friendsData = $this->queryFriends(); //getting friends data from database

    foreach ($friendsData as $f) {
      $friend = new User($f["user"]); //instantiate a User obj for each friend
      array_push($this->friends, $friend); //append to array of friend objs
    }

    return $this->friends;

  }
  
  //getter for this user's friends number
  public function getNumberOfFriends(): int {

    $this->numberOfFriends = count( $this->queryFriends() );
    return $this->numberOfFriends;

  }

  //helper method to query database for this user's friends data
  protected function queryFriends(): array {

    global $getAllFriendsQuery;

    $friendsData = queryDB($getAllFriendsQuery, [":user" => $this->user]); //retrieve friends' usernames from db
    return $friendsData;
  }

  //function to get the existing defined relationship between this user and another user
  //@param the other user's username
  //@return defined relationship code, int 0 for stranger, 1 for existing friend, 2 for friend request sent, 3 for friend request received, 4 for friend request rejected
  public function getRelationshipWith (string $thatUser): int {

    $friendship = new Friendship($this->user, $thatUser);
    $relationshipCode = $friendship->getFriendship;
    return $relationshipCode;

  }

  //function to retrieve this user's profile object
  //@param $fullProfile = true or false to indicate whether a Profile obj or a BasicProfile obj to retrieve
  //@return either a Profile or BasicProfile obj
  public function getProfile(bool $fullProfile = true) {

  }

  





















}

?>