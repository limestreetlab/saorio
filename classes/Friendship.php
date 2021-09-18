<?php

/*
class to encapsulate friendship between two users
friends are non-directional
some actions are directional, such as friend requests, A adds B, A removes B
for directional data/actions, $thisuser and $thatuser are important
*/
class Friendship {

    //variables declaration
    protected $thisuser; //username of the current user 
    protected $thatuser; //username of the other user
    protected $status; //defined relationship code, 0:stranger, 1:friend, 2:request sent (to thatuser), 3:request received (from thatuser)
    protected $isFollowing; //given friends, flag if thisuser is following thatuser
    protected $notesAbout; //given friends, notes thisuser has written about thatuser
    protected $mysql; //object for mysql database access

    /*
    constructor
    @param $thisuser, $thatuser, usernames of two users defined in a relationship
    */
    public function __construct(string $thisuser, string $thatuser) {

        $this->thisuser = $thisuser;
        $this->thatuser = $thatuser;
        $this->mysql = MySQL::getInstance();
        
        $data = $this->mysql->request($this->mysql->readFriendsDataQuery, [":user1" => $this->thisuser, ":user2" => $this->thatuser])[0];
        $this->isFollowing = $data["following"];
        $this->notesAbout = $data["notes"];

        if ( !$this->mysql->request($this->mysql->readMembersTableQuery, [":user" => $this->thisuser]) || !$this->mysql->request($this->mysql->readMembersTableQuery, [":user" => $this->thatuser]) ) {
            throw new Exception("Nonexistent username provided.");
        }
        
    }

    /*
    Directional
    a user (current user, thisuser) to add another user (the other user, thatuser) as a friend. 
    */
    public function add(): bool {

        try {

            if ( $this->getFriendship() != 0 ) {
                throw new Exception("Can only add a stranger.");
            }

            //database call
            $params = [":requestSender" => "$this->thisuser", ":requestRecipient" => "$this->thatuser"];
            $this->mysql->request($this->mysql->createFriendRequestQuery, $params);

            //send a message to the other user
            $contents = "Hello, I would like to add you as a friend and sent you a request. ";
            $friendRequestMessage = new Message($this->thisuser, $this->thatuser, time(), $contents);
            $friendRequestMessage->send();

            $this->status = 2;
            return true;

        } catch (Exception $ex) {
            return false;
        }        

    }

    /*
    Directional
    a user (current user, thisuser) removing an existing friend (the other user, thatuser) as a friend
    */
    public function remove(): bool {

        try {

            if ( $this->getFriendship() == 0 ) {
                throw new Exception("Cannot unfriend a stranger.");
            }

            //database call
            $params = [":a" => "$this->thisuser", ":b" => "$this->thatuser"];    
            $this->mysql->request($this->mysql->deleteFriendshipQuery, $params);

            $this->mysql->request($this->mysql->deleteFriendsDataQuery, $params);

            $this->status = 0;
            return true;

        } catch (Exception $ex) {
            return false;
        }
    }

    /*
    Directional
    for a user (current user) who has received a request from another user (the other user), accept the request and become friends
    */
    public function confirmRequest(): bool {

        try {

            if ( $this->getFriendship() != 3 ) {
                throw new Exception("Can only respond to a friend request received.");
            }

            //database call
            $params = [":requestSender" => "$this->thatuser", ":requestRecipient" => "$this->thisuser"];
            $this->mysql->request($this->mysql->updateFriendRequestQuery, $params);

            $this->mysql->request($this->mysql->createFriendsDataQuery, [":user1" => $this->thisuser, ":user2" => $this->thatuser]);
            $this->mysql->request($this->mysql->createFriendsDataQuery, [":user1" => $this->thatuser, ":user2" => $this->thisuser]);

            $this->status = 1;
            return true;

        } catch (Exception $ex) {
            return false;
        }

    }

    /*
    Directional
    for a user (current user) who has received a request from another user (the other user), reject the request and remain strangers
    */
    public function rejectRequest(): bool {

        try {

            if ( $this->getFriendship() != 3 ) {
                throw new Exception("Can only respond to a friend request received.");
            }

            $params = [":requestSender" => "$this->thatuser", ":requestRecipient" => "$this->thisuser"];
            $this->mysql->request($this->mysql->deleteFriendRequestQuery, $params);

            $this->status = 0;
            return true;

        } catch (Exception $ex) {
            return false;
        }

    }

    /*
    Directional
    for a user (current user, thisuser) to add notes about his friend (thatuser)
    */
    public function addNotes(string $notes): bool {

        try {

            if ($this->getFriendship() != 1) {
                throw new Exception("Can only add notes to an existing friend.");
            }

            $params = [":user1" => "$this->thisuser", ":user2" => "$this->thatuser", ":notes" => $notes];
            $this->mysql->request($this->mysql->updateFriendNotesQuery, $params);

            $this->notesAbout = $notes;

            return true;

        } catch (Exception $ex) {
            return false;
        }

    }

    /*
    Directional
    Getter for notes by thisuser about thatuser
    */
    public function getNotes(): ?string {

        return $this->notesAbout;

    }

    /*
    Directional
    toggle the following status
    */
    public function toggleFollowing(): bool {

        try {

            if ($this->getFriendship() != 1) {
                throw new Exception("Can only follow an existing friend.");
            }

            $params = [":user1" => "$this->thisuser", ":user2" => "$this->thatuser"];
            $this->mysql->request($this->mysql->updateFollowingQuery, $params);

            $this->isFollowing = $this->isFollowing ? 0 : 1;

            return true;

        } catch (Exception $ex) {
            return false;
        }

    }

    /*
    Directional
    getter for isFollowing, flag if thisuser is following that user
    */
    public function getIsFollowing(): bool {

        return $this->isFollowing;

    }
   

    /*
    Directional
    get existing relationship between current user and the other user
    @return defined relationship code, 0 for stranger, 1 for existing friend, 2 for friend request sent, 3 for friend request received
    */
    public function getFriendship(): int {

        //check if two users are in the friends table at all
        $anyResults = $this->mysql->request("SELECT * FROM friends WHERE user1 = '$this->thisuser' AND user2 = '$this->thatuser' UNION SELECT * FROM friends WHERE user1 = '$this->thatuser' AND user2 = '$this->thisuser'");
        
        if (!$anyResults) {

            return 0; //no relationship

        } elseif ($this->mysql->request($this->mysql->readFriendshipQuery, [":a" => "$this->thisuser", ":b" => "$this->thatuser"])) { //existing friends

            return 1;

        } elseif ( $this->mysql->request("SELECT * FROM friends WHERE user1 = '$this->thatuser' AND user2 = '$this->thisuser' AND status = 2") ) { //request received
        
            return 3;

        } else { //some friend request pending

            return 2; //request sent

        }
        
    }

    /*
    getter of timestamp for the relationship
    @return unix timestamp
    */
    public function getTimestamp(): int {

        if ($this->getFriendship() == 0) {
            
            throw new Exception("Cannot get friendship timestamp for two strangers.");
            
        }

        return intval( $this->mysql->request($this->mysql->readFriendshipQuery, [":a" => "$this->thisuser", ":b" => "$this->thatuser"])[0]["timestamp"] );

    }



    
}//close class

?>