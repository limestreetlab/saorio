<?php


class Login {

    private $user;
    private $password; //raw password entered
    private $passwordHash; //hashed password in database
    private $error; //1 for invalid username, 2 for invalid password
    protected $mysql; //object for mysql database access

    /*
    constructor
    @param $user username
    @param $password password 
    */
    public function __construct(string $user, string $password) {

        $this->user = filter_var(trim($user), FILTER_SANITIZE_STRING);
        $this->password = trim($password);
        $this->mysql = MySQL::getInstance();

    }

    /*
    function to verify entered password against database
    */
    public function verifyPassword(): self {

        $this->passwordHash = $this->mysql->request(MySQL::readPasswordQuery, [":user" => $this->user])[0][0]; //retrieve hashed password from database for this username
        
        if ( password_verify( $this->password, $this->passwordHash ) ) { //compare entered pass with hashed pass 
            
            return $this;

        } else {

            $this->error = 2;
            throw new Exception("invalid password.");

        }

    }

    /*
    function to check if entered username exists in db
    */
    public function checkUsername(): self {

        $usernameExists = $this->mysql->request(MySQL::readMembersTableQuery, [":user" => $this->user]);

        if (!$usernameExists) {
            $this->error = 1;
            throw new Exception("username does not exist.");
        }

        return $this;

    }

    /*
    error variable getter
    */
    public function getError(): int {

        return $this->error;

    }
    

}


?>