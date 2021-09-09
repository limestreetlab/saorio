<?php


class Login {

    private $user;
    private $password; //raw password entered
    private $passwordHash; //hashed password in database
    protected $mysql; //object for mysql database access

    /*
    constructor
    @param $user username
    @param $password password 
    */
    public function __construct(string $user, string $password) {

        $this->user = filter_var(trim($user), FILTER_SANITIZE_STRING);
        $this->password = trim($password);
        $this->mysql = MySQL::getinstance();

    }

    /*
    function to verify entered password against database
    */
    public function verifyPassword(): bool {

        $this->passwordHash = $this->mysql->request($this->mysql->readPasswordQuery, [":user" => $this->user])[0][0]; //retrieve hashed password from database for this username
        return password_verify( $this->password, $this->passwordHash ); //compare entered pass with hashed pass 

    }

    /*
    function to check if entered username exists in db
    */
    public function checkUsername(): bool {

        $usernameExists = $this->mysql->request($this->mysql->readMembersTableQuery, [":user" => $this->user]);

        return $usernameExists ? true : false;

    }

    

}


?>