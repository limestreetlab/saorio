<?php
/*
create MySQL database connection and single access-point to database
singleton class
*/

require_once $_SERVER["DOCUMENT_ROOT"] . "/Saorio/includes/config.php"; //load database credentials from config file

final class MySQL {

  private $dsn; //data source name
  private $dbh; //database handle 
  private static $mysql = null; //single instance
  
  /*
  private constructor, establish a handle to mysql database
  */
  private function __construct() {

    try {

      $this->dsn = "mysql:host=". DB_HOST .";dbname=". DB_NAME .";port=". DB_PORT; 
      $this->dbh = new PDO($this->dsn, DB_USER, DB_PASSWORD);

    } catch (PDOException $ex) {

      exit("Database connection failed: " . $ex->getMessage());

    }

  }

  /*
  method to access the single instance
  */
  public static function getInstance() {

    if (self::$mysql == null) {
      self::$mysql = new MySQL();
    }

    return self::$mysql;

  }

  /*
  key function to access database, for all CRUD operations
  @param $query: SQL query string, can be either a straight query (without any inputs) or a prepared statement using either named parameters (:param) or positional params (?)
  @param $params: values, in array, to bind to a prepared statement, [value1, value2, ...] or ["name1" => value1, "name2" => value2, ...] for positional or named params
  @return full resultset
  */
  public function request(string $query, array $params=null): array {
    
    if (isset($params)) { //query params provided, so a prepared statement
      
      $stmt = ($this->dbh)->prepare($query); //set up the prepared statement

      $isAssocArray = count(array_filter(array_keys($params), "is_string")) == 0 ? false : true; //boolean flag for associative array (dict, with keys) versus sequential array (list, without keys)  
      
      if ($isAssocArray) { //the prepared statement uses named parameters (:name1, :name2, ...)
        
        foreach ($params as $key => &$value) {  //bind the parameters 1-by-1
          if (substr($key, 0, 1) != ":") { //if the provided parameter isn't prefixed with ':' which is required in bindParam()
            $name = ":".$key; //prefix it with ':'
          }

          $stmt->bindParam($key, $value);
        }

      } else { //the prepared statement uses unnamed parameters (?, ?, ...) 
        
        for($i = 1; $i <= count($params); $i++) { //bind the parameters 1-by-1
          $stmt->bindParam($i, $params[$i-1]); 
        }

      } //the prepared statement has its values bound and ready for execution

      $stmt->execute();

    } else { //not a prepared statement, a straight query

      $stmt = ($this->dbh)->query($query);   

    }

    $resultset = $stmt->fetchAll(); //grab the entire resultset
    return $resultset;

  }//end function

  /*
  Collection of all SQL query strings used throughout the app
  */

  //for members
  public $readAllUsersQuery = "SELECT user FROM members";


  //for account logging
  public $readPasswordQuery = "SELECT password FROM members WHERE user = :user";
  public $readMembersTableQuery = "SELECT * FROM members WHERE user = :user";
  public $createMemberQuery = "INSERT INTO members (user, password, email) VALUES (:user, :password, :email)"; //create a new record of members
  public $createBasicProfileQuery = "INSERT INTO profiles (user, firstname, lastname) VALUES (:user, :firstname, :lastname)"; //create a default profile
  public $readEmailQuery = "SELECT email FROM members where user = :user";

  //for profile
  public $updateProfileQuery = "UPDATE profiles SET about = :about, gender = :gender, ageGroup = :ageGroup, location = :location, job = :job, company = :company, major = :major, school = :school, interests = :interests, quote = :quote WHERE user = :user"; 

  public $readProfileQuery = "SELECT * FROM profiles WHERE user = :user";
  public $readBasicProfileQuery = "SELECT firstname, lastname, profilePictureURL FROM profiles WHERE user = :user";

  //for profile edits (atomic: one statement for each updatable field, inefficient and redundant but simple)
  public $updateProfilePictureQuery = "UPDATE profiles SET profilePictureURL = :url, profilePictureMIME = :mime WHERE user = :user"; 
  public $updateProfileAboutQuery = "UPDATE profiles SET about = :about WHERE user = :user";
  public $updateProfileGenderQuery = "UPDATE profiles SET gender = :gender WHERE user = :user";
  public $updateProfileDobQuery = "UPDATE profiles SET dob = :dob WHERE user = :user";
  public $updateProfileInterestsQuery = "UPDATE profiles SET interests = :interests WHERE user = :user";
  public $updateProfileQuoteQuery = "UPDATE profiles SET quote = :quote WHERE user = :user";
  public $updateProfileCityQuery = "UPDATE profiles SET city = :city WHERE user = :user";
  public $updateProfileCountryQuery = "UPDATE profiles SET country = :country WHERE user = :user";
  public $updateProfileJobQuery = "UPDATE profiles SET job = :job WHERE user = :user";
  public $updateProfileCompanyQuery = "UPDATE profiles SET company = :company WHERE user = :user";
  public $updateProfileMajorQuery = "UPDATE profiles SET major = :major WHERE user = :user";
  public $updateProfileSchoolQuery = "UPDATE profiles SET school = :school WHERE user = :user";
  public $updateProfileWebsiteQuery = "UPDATE profiles SET website = :website WHERE user = :user";
  public $updateProfileSocialMediaQuery = "UPDATE profiles SET socialmedia = :socialmedia WHERE user = :user";
  public $updateMembersEmailQuery = "UPDATE members SET email = :email WHERE user = :user";


  //for friends
  public $readAllFriendsQuery = "SELECT f.user FROM (SELECT user2 AS user FROM friends WHERE user1 = :user AND status = 1 UNION SELECT user1 AS user FROM friends WHERE user2 = :user AND status = 1) AS f";
  public $readFriendshipQuery = "SELECT user1, user2, unix_timestamp(timestamp) AS timestamp FROM friends WHERE user1 = :a AND user2 = :b AND status = 1 UNION select * FROM friends WHERE user1 = :b AND user2 = :a AND status = 1"; //null if a and b are not confirmed friends
  public $updateFriendRequestQuery = "UPDATE friends SET status = 1 WHERE user1 = :requestSender AND user2 = :requestRecipient";
  public $deleteFriendRequestQuery = "DELETE FROM friends WHERE user1 = :requestSender AND user2 = :requestRecipient";
  public $createFriendRequestQuery = "INSERT INTO friends (user1, user2, status) VALUES (:requestSender, :requestRecipient, 2)";
  public $deleteFriendshipQuery = "DELETE FROM friends WHERE (user1 = :a AND user2 = :b) OR (user1 = :b AND user2 = :a)";

  //for messages
  public $readConversationWithQuery = "SELECT timestamp, sender, recipient, message FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :chatWith UNION SELECT * FROM messages WHERE sender = :chatWith AND recipient = :me) AS conversation ORDER BY timestamp ASC";
  public $readConversationWithSinceQuery = "SELECT timestamp, sender, recipient, message FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :chatWith AND timestamp > :since UNION SELECT * FROM messages WHERE sender = :chatWith AND recipient = :me AND timestamp > :since) AS conversation ORDER BY timestamp ASC";
  public $readChattedWithQuery = "SELECT MAX(timestamp) AS lastTime, chatWith FROM ( SELECT sender AS chatWith, timestamp FROM messages WHERE recipient = :me UNION SELECT recipient AS chatWith, timestamp FROM messages WHERE sender = :me) AS m GROUP BY chatWith ORDER BY lastTime DESC";
  public $createMessageQuery = "INSERT INTO messages VALUES (NULL, :time, :from, :to, :message)";



  


} //end class

?>