<?php


class Login {

    private $user;
    private $password; //raw password entered
    private $passwordHash; //hashed password in database
    private $messages = [];
    protected $mysql; //object for mysql database access

    //constructor
    public function __construct(string $user, string $password) {

        $this->user = filter_var(trim($user), FILTER_SANITIZE_STRING);
        $this->password = trim($password);
        $this->mysql = MySQL::getInstance();

    }

    //main function to verify entered credentials against database
    public function verify(): bool {

        $isVerified = false;

        if ( $this->checkIfUserExists() ) {

            $this->passwordHash = $this->mysql->request($this->mysql->readPasswordQuery, [":user" => $this->user])[0]["password"]; //retrieve hashed password from database for this username
            $isVerified = password_verify( $this->password, $this->passwordHash ); //compare entered pass with hashed pass 
            
            if (!$isVerified) {
                $msg = "<div class='alert alert-warning'>The password does not match.</div>";
                array_push($this->messages, $msg);
            } else {
                $msg = "<div class='alert alert-success'>You're now logged in. You will soon be redirected. </div>";
                array_push($this->messages, $msg);
            }
        }

        return $isVerified;

    }

    //helper function to check if entered username exists in db
    private function checkIfUserExists(): bool {

        $dataForThisUser = $this->mysql->request($this->mysql->readMembersTableQuery, [":user" => $this->user]);
        
        if ( $dataForThisUser ) {

            $usernameExists = true;

        } else {

            $usernameExists = false;
            $msg = "<div class='alert alert-warning'>The username entered does not exist.</div>";
            array_push($this->messages, $msg);

        }

        return $usernameExists;
    }

    //messages getter
    public function getMessages() {
        return $this->messages;
    }


}


?>