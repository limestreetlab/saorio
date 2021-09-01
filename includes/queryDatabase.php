<?php

/*
Collection of all SQL query strings used throughout
*/

//for members
$getAllUsersQuery = "SELECT user FROM members";


//for account logging
$loginPasswordQuery = "SELECT password FROM members WHERE user = :user";
$loginQuery = "SELECT * FROM members WHERE user = :user";
$signupQuery = "INSERT INTO members (user, password, email) VALUES (:user, :password, :email)"; //create a new record of members
$initializeProfileQuery = "INSERT INTO profiles (user, firstname, lastname) VALUES (:user, :firstname, :lastname)"; //create a default profile
$getEmailQuery = "SELECT email FROM members where user = :user";

//for profile
$updateProfileQuery = "UPDATE profiles SET about = :about, gender = :gender, ageGroup = :ageGroup, location = :location, job = :job, company = :company, major = :major, school = :school, interests = :interests, quote = :quote WHERE user = :user"; 

$getProfileQuery = "SELECT * FROM profiles WHERE user = :user";
$getBasicProfileQuery = "SELECT firstname, lastname, profilePictureURL FROM profiles WHERE user = :user";

//for profile edits
$updateProfilePictureQuery = "UPDATE profiles SET profilePictureURL = :url, profilePictureMIME = :mime WHERE user = :user"; 
$updateProfileAboutQuery = "UPDATE profiles SET about = :about WHERE user = :user";
$updateProfileGenderQuery = "UPDATE profiles SET gender = :gender WHERE user = :user";
$updateProfileDobQuery = "UPDATE profiles SET dob = :dob WHERE user = :user";
$updateProfileInterestsQuery = "UPDATE profiles SET interests = :interests WHERE user = :user";
$updateProfileLocationQuery = "UPDATE profiles SET city = :city, country = :country WHERE user = :user";
$updateProfileWorkQuery = "UPDATE profiles SET job = :job, company = :company WHERE user = :user";
$updateProfileStudyQuery = "UPDATE profiles SET school = :school, major = :major WHERE user = :user";
$updateProfileWebsiteQuery = "UPDATE profiles SET website = :website WHERE user = :user";
$updateMembersEmailQuery = "UPDATE members SET email = :email WHERE user = :user";


//for friends
$getAllFriendsQuery = "SELECT f.user FROM (SELECT user2 AS user FROM friends WHERE user1 = :user AND status = 1 UNION SELECT user1 AS user FROM friends WHERE user2 = :user AND status = 1) AS f";
$checkIfFriendsQuery = "SELECT * FROM friends WHERE user1 = :a AND user2 = :b AND status = 1 UNION select * FROM friends WHERE user1 = :b AND user2 = :a AND status = 1";
$confirmFriendRequestQuery = "UPDATE friends SET status = 1 WHERE user1 = :requestSender AND user2 = :requestRecipient";
$rejectFriendRequestQuery = "DELETE FROM friends WHERE user1 = :requestSender AND user2 = :requestRecipient";
$addAFriendQuery = "INSERT INTO friends (user1, user2, status) VALUES (:requestSender, :requestRecipient, 1)";
$removeAFriendQuery = "DELETE FROM friends WHERE (user1 = :a AND user2 = :b) OR (user1 = :b AND user2 = :a)";

//for messages
$getConversationWithQuery = "SELECT timestamp, sender, recipient, message FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :chatWith UNION SELECT * FROM messages WHERE sender = :chatWith AND recipient = :me) AS conversation ORDER BY timestamp ASC";
$getConversationWithSinceQuery = "SELECT timestamp, sender, recipient, message FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :chatWith AND timestamp > :since UNION SELECT * FROM messages WHERE sender = :chatWith AND recipient = :me AND timestamp > :since) AS conversation ORDER BY timestamp ASC";
$getChattedWithQuery = "SELECT MAX(timestamp) AS lastTime, chatWith FROM ( SELECT sender AS chatWith, timestamp FROM messages WHERE recipient = :me UNION SELECT recipient AS chatWith, timestamp FROM messages WHERE sender = :me) AS m GROUP BY chatWith ORDER BY lastTime DESC";
$sendMessageQuery = "INSERT INTO messages VALUES (NULL, :time, :from, :to, :message)";


/*
utility function to query database and return the full resultset.
@param $query: the SQL query string, can be either a straight query (without any external inputs) or a prepared statement using either named parameters (:param) or positional (?)
@param $param: the values, in array, to bind to a prepared statement, [value1, value2, ...] or ["name1" => value1, "name2" => value2, ...] for positional or named params
@return full resultset of the query
*/
function queryDB(string $query, array $param=null): array {
  
  global $dbh; //reference the db handle declared in ini.php 

  if (isset($param)) { //query params provided, so a prepared statement
    
    $stmt = $dbh->prepare($query); //set up the prepared statement

    $isAssocArray = count(array_filter(array_keys($param), "is_string")) == 0 ? false : true; //boolean flag for associative array (dict, with keys) versus sequential array (list, without keys)  
    
    if ($isAssocArray) { //the prepared statement uses named parameters (:name1, :name2, ...)
      
      foreach ($param as $key => &$value) {  //bind the parameters 1-by-1
        if (substr($key, 0, 1) != ":") { //if the provided parameter isn't prefixed with ':' which is required in bindParam()
          $name = ":".$key; //prefix it with ':'
        }

        $stmt->bindParam($key, $value);
      }

    } else { //the prepared statement uses unnamed parameters (?, ?, ...) 
      
      for($i = 1; $i <= count($param); $i++) { //bind the parameters 1-by-1
        $stmt->bindParam($i, $param[$i-1]); 
      }

    } //the prepared statement has its values bound and ready for execution

    $stmt->execute();

  } else { //not a prepared statement, a straight query

    $stmt = $dbh->query($query);   

  }

  $resultset = $stmt->fetchAll(); //grab the entire resultset
  return $resultset;

}//end function



?>