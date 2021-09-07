<?php


class Signup {

    //variables declaration
    private $user;
    private $password;
    private $passwordRepeat;
    private $email;
    private $firstname;
    private $lastname;
    private $messages = [];
    protected $mysql; //object for mysql database access

    //constructor
    public function __construct($user, $password, $passwordRepeat, $email, $firstname, $lastname) {

        $this->user = $user;
        $this->password = $password;
        $this->passwordRepeat = $passwordRepeat;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->mysql = MySQL::getInstance();

    }

    //main register function to sign up users, performs validations and database entry
    public function register(): bool {

        $success = false;

        if ( $this->validate() ) {

            try {
                $this->initializeProfile();
                $this->persist();
                $msg = "<div class='alert alert-success'>Account has been successfully created.</div>";
                array_push($this->messages, $msg);               
                $success = true; 
            
            } catch (Exception $ex) {
                $msg = "<div class='alert alert-warning'>Signup failed due to our database issue. Sorry for the inconvenience.</div>";
                array_push($this->messages, $msg);
                error_log("Database error occurred during user signup. Exception message: $ex");
            }
        }

        return $success;

    }

    //helper function to factory-validate user inputs
    private function validate(): bool {

        if ( $this->checkUsername() && $this->checkPassword() && $this->checkEmail() && $this->checkName() ) { //fields checked
            
            return true;

        } else { //some checks failed

            return false;
            
        }

    }

    //helper function to check username
    private function checkUsername(): bool {
        
        $success = true;
        $this->user = strtolower(filter_var(trim($this->user), FILTER_SANITIZE_STRING)); //sanitize, trim, lowercase

        //check for whitespace
        if ( strpos( $this->user, " ") ) { 
            $success = false;
            $msg = "<div class='alert alert-warning'>Username should not contain any whitespace.</div>";
            array_push($this->messages, $msg);
        }

        //check for special characters
        $specialCharacterPattern = "/[^a-zA-Z0-9]/"; //regex for not alphabets or numbers
        if ( preg_match($specialCharacterPattern, $this->user) ) { 
            $success = false;
            $msg = "<div class='alert alert-warning'>Username should not contain any special characters.</div>";
            array_push($this->messages, $msg);
        }

        //check if starting with a digit
        $beginningNumberPattern = "/^[0-9]/"; //regex for digit as first entry
        if ( preg_match($beginningNumberPattern, $this->user) ) { 
            $success = false;
            $msg = "<div class='alert alert-warning'>Username should not begin with a number.</div>";
            array_push($this->messages, $msg);
        }

        //check for length
        $minLength = 5;
        $maxLength = 20;
        $length = strlen($this->user);
        if ( $length > $maxLength || $length < $minLength ) {
            $success = false;
            $msg = "<div class='alert alert-warning'>Username should be between '$minLength' and '$maxLength' character long.</div>";
            array_push($this->messages, $msg);
        }

        return $success;

    } //end check user

    //helper function to check password
    private function checkPassword(): bool {

        $success = true;
        $this->password = trim($this->password); //trim

        //check for whitespace
        if ( strpos( $this->password, " ") ) { 
            $success = false;
            $msg = "<div class='alert alert-warning'>Password should not contain any whitespace.</div>";
            array_push($this->messages, $msg);
        }

        //check for unallowed characters
        $passwordCharacterPattern = "/[^-+?.!_$%&a-zA-Z0-9]/"; //allows some special characters, but not slashes and brackets
        if ( preg_match($passwordCharacterPattern, $this->password) ) { 
            $success = false;
            $msg = "<div class='alert alert-warning'>Password contains a disallowed special character.</div>";
            array_push($this->messages, $msg);
        }

        //check for length
        $minLength = 5;
        $maxLength = 50;
        $length = strlen($this->password);
        if ( $length > $maxLength || $length < $minLength ) {
            $success = false;
            $msg = "<div class='alert alert-warning'>Password should be between '$minLength' and '$maxLength' character long.</div>";
            array_push($this->messages, $msg);
        }

        //check for repeated password match
        if ( $this->password != $this->passwordRepeat ) {
            $success = false;
            $msg = "<div class='alert alert-warning'>The entered passwords do not match.</div>";
            array_push($this->messages, $msg);
        }

        return $success;

    } //end check password

    //helper function to check email
    private function checkEmail(): bool {

        $success = true;
        $this->email = strtolower(filter_var(trim($this->email), FILTER_SANITIZE_STRING)); //sanitize, trim, lowercase

        //check for address validity
        if ( !filter_var($this->email, FILTER_VALIDATE_EMAIL) ) {
            $success = false;
            $msg = "<div class='alert alert-warning'>The entered email address is invalid.</div>";
            array_push($this->messages, $msg);
        }

        //check for duplicate email in database
        $emailExists = $this->mysql->request("SELECT * from members WHERE email = '$this->email'");
        if ($emailExists) {
            $success = false;
            $msg = "<div class='alert alert-warning'>The email address is already used for an existing account.</div>";
            array_push($this->messages, $msg);
        }

        //check for length
        $minLength = 8;
        $maxLength = 30;
        $length = strlen($this->email);
        if ( $length > $maxLength || $length < $minLength ) {
            $success = false;
            $msg = "<div class='alert alert-warning'>Email should be between '$minLength' and '$maxLength' character long.</div>";
            array_push($this->messages, $msg);
        }

        return $success;

    }

    //helper function to check first and last names
    private function checkName(): bool {

        $success = true;
        $this->firstname = ucwords(filter_var(trim($this->firstname), FILTER_SANITIZE_STRING)); //sanitize, trim, capitalize
        $this->lastname = ucwords(filter_var(trim($this->lastname), FILTER_SANITIZE_STRING)); //sanitize, trim, capitalize

        //check for length
        $minLength = 2;
        $maxLength = 20;
        $length1 = strlen($this->firstname);
        $length2 = strlen($this->lastname);
        if ( $length1 > $maxLength || $length1 < $minLength || $length2 > $maxLength || $length2 < $minLength ) {
            $success = false;
            $msg = "<div class='alert alert-warning'>First name and last name should be between '$minLength' and '$maxLength' character long.</div>";
            array_push($this->messages, $msg);
        }

        //check for non-characters
        $nonCharacterPattern = "/[^a-zA-Z]/"; //regex for non-alphabets
        if ( preg_match($nonCharacterPattern, $this->firstname) || preg_match($nonCharacterPattern, $this->lastname) ) { 
            $success = false;
            $msg = "<div class='alert alert-warning'>Name should only contain English characters.</div>";
            array_push($this->messages, $msg);
        }

        return $success;

    }

    //messages getter
    public function getMessages(): array {
        return $this->messages;
    }

    //function to persist validated data to database
    private function persist(): bool {

        try {
            $passwordHash = password_hash($this->password, PASSWORD_DEFAULT); //hashed password
            $params = [":user" => $this->user, ":password" => $passwordHash, ":email" => $this->email];
            $this->mysql->request($this->mysql->createMemberQuery, $params);
            $success = true;
        } catch (Exception $ex) {
            $success = false;
            throw $ex;
        }

        return $success;

    }

    //optional function to initialize basic profile data
    private function initializeProfile(): bool {

        try {
            $params = [":user" => $this->user, ":firstname" => $this->firstname, ":lastname" => $this->lastname];
            $this->mysql->request($this->mysql->createBasicProfileQuery, $params);
            $success = true;
          } catch (Exception $ex) {
            $success = false;
            throw $ex;
          }
      
          return $success;

    }

}//close class


?>