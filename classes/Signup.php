<?php
/*
class to encapsulate data of and perform related operations for a user signup 
*/
class Signup {

    //variables declaration
    private $user; //username
    private $password; //password
    private $passwordRepeat; //repeatedly typed password
    private $email; //email address
    private $firstname; //firstname
    private $lastname; //lastname 
    private $errorCodes = []; //array to append issues during data validation
    protected $mysql; //object for mysql database access

    /*
    constructor
    */
    public function __construct(string $user = null, string $password = null, string $passwordRepeat = null, string $email = null, string $firstname = null, string $lastname = null) {

        $this->user = $user;
        $this->password = $password;
        $this->passwordRepeat = $passwordRepeat;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->mysql = MySQL::getinstance();

    }

    /*
    main register function to sign up users, performs validations and database entry
    @return 2-element array [success, [error codes]] where an error code is given when some validation error occurs or null otherwise
    error codes are defined as -1 for internal failure, 0 for duplicated username, 1 for whitespaces in username, 2 for special characters in username, 3 for username starting with a digit, 4 for username beyond min/max length, 
    5 for whitespaces in password, 6 for password containing disallowed special characters, 7 for password beyond min/max length, 8 for repeated password not matching,
    9 for invalid email address, 10 for email address already used, 11 for email beyond min/max length, 12 for name beyong min/max length, 13 for name having non-English alphabets, 14 for having null fields
    */
    public function register(): array {

        $success = false;

        if (isset($this->user, $this->password, $this->passwordRepeat, $this->email, $this->firstname, $this->lastname)) {

            if ( $this->validate() ) {

                try {

                    $this->persist();
                    $this->initializeProfile();
                    $success = true; 
                
                } catch (Exception $ex) {

                    array_push($this->errorCodes, -1);
                    error_log("Database error occurred during user signup. Exception message: " . $ex->getMessage());
                    
                }
            }
        
        } else {

            array_push($this->errorCodes, 14);

        }

        return [$success, $this->errorCodes];

    }

    /*
    helper function to factory-validate user inputs
    */
    private function validate(): bool {

        return ( $this->checkUsername() & $this->checkPassword() & $this->checkEmail() & $this->checkName() );

    }

    /*
    helper function to check if username is acceptable
    */
    public function checkUsername(): bool {
        
        $success = true;
        $this->user = strtolower(filter_var(trim($this->user), FILTER_SANITIZE_STRING)); //sanitize, trim, lowercase

        //check for username duplicate
        $usernameExists = $this->mysql->request($this->mysql->readMembersTableQuery, [":user" => "$this->user"]);
        if ($usernameExists) { 
            $success = false;
            array_push($this->errorCodes, 0);
        }

        //check for whitespace
        if ( strpos( $this->user, " ") ) { 
            $success = false;
            array_push($this->errorCodes, 1);
        }

        //check for special characters
        $specialCharacterPattern = "/[^a-zA-Z0-9]/"; //regex for not alphabets or numbers
        if ( preg_match($specialCharacterPattern, $this->user) ) { 
            $success = false;
            array_push($this->errorCodes, 2);
        }

        //check if starting with a digit
        $beginningNumberPattern = "/^[0-9]/"; //regex for digit as first entry
        if ( preg_match($beginningNumberPattern, $this->user) ) { 
            $success = false;
            array_push($this->errorCodes, 3);
        }

        //check for length
        $minLength = 5;
        $maxLength = 20;
        $length = strlen($this->user);
        if ( $length > $maxLength || $length < $minLength ) {
            $success = false;
            array_push($this->errorCodes, 4);
        }

        return $success;

    } //end check user

    /*
    helper function to check password validity
    */
    public function checkPassword(): bool {

        $success = true;
        $this->password = trim($this->password); //trim

        //check for whitespace
        if ( strpos( $this->password, " ") ) { 
            $success = false;
            array_push($this->errorCodes, 5);
        }

        //check for unallowed characters
        $passwordCharacterPattern = "/[^-+?.!_$%&a-zA-Z0-9\s]/"; //allows some special characters, but not slashes and brackets
        if ( preg_match($passwordCharacterPattern, $this->password) ) { 
            $success = false;
            array_push($this->errorCodes, 6);
        }

        //check for length
        $minLength = 5;
        $maxLength = 50;
        $length = strlen($this->password);
        if ( $length > $maxLength || $length < $minLength ) {
            $success = false;
            array_push($this->errorCodes, 7);
        }

        //check for repeated password match
        if ( $this->password != $this->passwordRepeat ) {
            $success = false;
            array_push($this->errorCodes, 8);
        }

        return $success;

    } //end check password

    /*
    helper function to check email address validity
    */
    public function checkEmail(): bool {

        $success = true;
        $this->email = strtolower(filter_var(trim($this->email), FILTER_SANITIZE_STRING)); //sanitize, trim, lowercase

        //check for address validity
        if ( !filter_var($this->email, FILTER_VALIDATE_EMAIL) ) {
            $success = false;
            array_push($this->errorCodes, 9);
        }

        //check for duplicate email in database
        $emailExists = $this->mysql->request("SELECT * from members WHERE email = '$this->email'");
        if ($emailExists) {
            $success = false;
            array_push($this->errorCodes, 10);
        }

        //check for length
        $minLength = 5;
        $maxLength = 50;
        $length = strlen($this->email);
        if ( $length > $maxLength || $length < $minLength ) {
            $success = false;
            array_push($this->errorCodes, 11);
        }

        return $success;

    }

    /*
    helper function to validate first and last names
    */
    public function checkName(): bool {

        $success = true;
        $this->firstname = ucwords(filter_var(trim($this->firstname), FILTER_SANITIZE_STRING)); //sanitize, trim, capitalize
        $this->lastname = ucwords(filter_var(trim($this->lastname), FILTER_SANITIZE_STRING)); //sanitize, trim, capitalize

        //check for length
        $minLength = 2;
        $maxLength = 30;
        $length1 = strlen($this->firstname);
        $length2 = strlen($this->lastname);
        if ( $length1 > $maxLength || $length1 < $minLength || $length2 > $maxLength || $length2 < $minLength ) {
            $success = false;
            array_push($this->errorCodes, 12);
        }

        //check for non-characters
        $nonCharacterPattern = "/[^a-zA-Z]/"; //regex for non-alphabets
        if ( preg_match($nonCharacterPattern, $this->firstname) || preg_match($nonCharacterPattern, $this->lastname) ) { 
            $success = false;
            array_push($this->errorCodes, 13);
        }

        return $success;

    }

    /*
    errorCodes getter
    */
    public function getErrorCodes(): array {

        return $this->errorCodes;

    }

    /*
    function to persist validated data to database
    */
    private function persist(): bool {

        try {
            $passwordHash = password_hash($this->password, PASSWORD_DEFAULT); //hashed password
            $params = [":user" => "$this->user", ":password" => $passwordHash, ":email" => "$this->email"];
            $this->mysql->request($this->mysql->createMemberQuery, $params);
            $success = true;
        } catch (Exception $ex) {
            $success = false;
            throw $ex;
        }

        return $success;

    }

    /*
    optional function to initialize basic profile data
    */
    private function initializeProfile(): bool {

        try {
            $params = [":user" => "$this->user", ":firstname" => "$this->firstname", ":lastname" => "$this->lastname"];
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