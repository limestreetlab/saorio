<?php
//class to aggregate member statistics for generalized site-wide use

class MemberManager {

    //variables declaration
    protected $members;
    protected $numberOfMembers;
    protected $mysql;

    public function __construct() {
        
        $this->mysql = MySQL::getInstance();
        $this->members = $this->mysql->request(MySQL::readAllUsersQuery, null, true);
        $this->numberOfMembers = count($this->members);

    }

    /*
    function to get a list of members picked at random
    @param $number, number of members to get
    @return array of User objects representing the members
    */
    public function getMembers(int $number = null): array {

        $number = isset($number) ? $number : $this->numberOfMembers; 
        $clone = $this->members; //get a copy
        shuffle($clone); //randomnize the entire member list
        $members = array_slice($clone, 0, $number); //a slice of shuffled list of number-length
        
        $list = [];
        foreach ($members as $member) {

            $memberObj = new User($member);
            array_push($list, $memberObj);

        }

        return $list;

    }

    /*
    function to get a certain number of most recently joined members
    @param number of members to get
    @return array of User objects representing the members in most recent order
    */
    public function getNewMembers(int $number = 1): array {

        $newMembers = $this->mysql->request(MySQL::readNewUsersQuery, [":number" => $number], true);
        $list = [];
        foreach ($newMembers as $newMember) {

            $memberObj = new User($newMember);
            array_push($list, $memberObj);

        }

        return $list;

    }

    /*
    getter of number of members
    @return number of memebrs
    */
    public function getNumberOfMembers(): int {

        return $this->numberOfMembers;

    }


}


?>