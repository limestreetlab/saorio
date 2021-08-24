<?php

require_once INCLUDE_DIR . "queries.php";
require_once CLASS_DIR . "Message.php";


class Friendship {

    protected $user1; 
    protected $user2;
    protected $status;
    protected $isFollowing;

    public function __construct($user1, $user2) {

        $this->user1 = $user1;
        $this->user2 = $user2;
    }

    public function add(): bool {

        try {

            global $addAFriendQuery;
            $params = [":a" => $this->user1, ":b" => $this->user2];
            queryDB($addAFriendQuery, $params);

            $contents = "Hi '$this->user2', I would like to add you as a friend and sent you a request. ";
            $friendRequestMessage = new Message($this->user1, $this->user2, time(), $contents);
            $friendRequestMessage->send();

            $this->status = "requested";
            return true;

        } catch (Exception $ex) {
            return false;
        }        

    }

    public function remove(): bool {

        try {
            global $removeAFriendQuery;
            $params = [":a" => $this->user1, ":b" => $this->user2];    
            queryDB($removeAFriendQuery, $params);
            $this->status = null;
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function confirmRequest(): bool {

        try {
            global $confirmFriendRequestQuery;
            $params = [":requestSender" => $this->user2, ":requestRecipient" => $this->user1];
            queryDB($confirmFriendRequestQuery, $params);
            $this->status = "confirmed";
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function rejectRequest(): bool {

        try {
            global $rejectFriendRequestQuery;
            $params = [":requestSender" => $this->user2, ":requestRecipient" => $this->user1];
            queryDB($rejectFriendRequestQuery, $params);
            $this->status = "rejected";
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }


    /*
    public function follow(): bool {


        $this->isFollowing = true;
        return true;
    }

    public function unfollow(): bool {


        $this->isFollowing = false;
        return true;
    }
    */

    //function to get existing friendship, if any, between two users
    //@return defined relationship code, int 0 for stranger, 1 for existing friend, 2 for friend request sent, 3 for friend request received, 4 for friend request rejected
    public function getFriendship(): int {

        global $checkIfFriendsQuery;
        //check if user1 and user2 are in the friends table at all
        $hasAnyRelationship = queryDB("SELECT * FROM friends WHERE user1 = '$this->user1' AND user2 = '$this->user2' UNION SELECT * FROM friends WHERE user1 = '$this->user2' AND user2 = '$this->user1'");
        
        if (!$hasAnyRelationship) {

            return 0; //no relationship

        } elseif (queryDB($checkIfFriendsQuery, [":a" => "$this->user1", ":b" => "$this->user2"])) { //existing friends

            return 1;

        } elseif ( queryDB("SELECT * FROM friends WHERE user1 = '$this->user2' AND user2 = '$this->user1' AND status = 'rejected'") ) { //I have rejected his request
        
            return 4;

        } else { //some friend request pending

            if( queryDB("SELECT * FROM friends WHERE user1 = '$this->user2' AND user2 = '$this->user1' AND status = 'requested'") ) { //I received the request
                return 3; 
            } else {
                return 2; //either I sent the request and it's rejected by him or pending response
            }
        }
        
    }



    
}//close class

?>