<?php
/*
create MySQL database connection and single access-point to database
singleton class
*/

require_once $_SERVER["DOCUMENT_ROOT"] . "/Saorio/includes/credentials.php"; //load database credentials from config file

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
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
  @param $fetchByColumn, a flag indicating to fetch the first column (index 0) of the resultset into an array
  @return array resultset if one exists or null if there is no resultset (not a Select query)
  */
  public function request(string $query, array $params = null, bool $fetchByColumn = false): ?array {

    try {

      if (isset($params)) { //query params provided, so a prepared statement
        
        $stmt = ($this->dbh)->prepare($query); //set up the prepared statement

        $isAssocArray = count(array_filter(array_keys($params), "is_string")) == 0 ? false : true; //boolean flag for associative array (dict, with keys) versus sequential array (list, without keys)  
        
        if ($isAssocArray) { //the prepared statement uses named parameters (:name1, :name2, ...)
          
          foreach ($params as $key => &$value) {  //bind the parameters 1-by-1
            
            if (substr($key, 0, 1) != ":") { //if the provided parameter isn't prefixed with ':' which is required in bindParam()
              $key = ":".$key; //prefix it with ':'
            }

            is_integer($value) ? $stmt->bindParam($key, $value, PDO::PARAM_INT) : $stmt->bindParam($key, $value, PDO::PARAM_STR) ;
          
          }

        } else { //the prepared statement uses unnamed parameters (?, ?, ...) 
          
          for($i = 1; $i <= count($params); $i++) { //bind the parameters 1-by-1
            
            is_integer($params[$i-1]) ? $stmt->bindParam($i, $params[$i-1], PDO::PARAM_INT) : $stmt->bindParam($i, $params[$i-1], PDO::PARAM_STR) ;
          
          }

        } //the prepared statement has its values bound and ready for execution

        $stmt->execute();

      } else { //not a prepared statement, a straight query

        $stmt = ($this->dbh)->query($query);   

      }

      //using fetch() or fetchAll() when there is no resultset will result in an exception
      //before fetching, use columnCount() to check if a resultset exists, it returns 0 if no resultset
      if ($fetchByColumn) {

        $resultset = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

      } else {
        
        $resultset = $stmt->columnCount() > 0 ? $stmt->fetchAll() : null;
      
      }

      return $resultset;

    } catch (PDOException $ex) {

      throw $ex;

    }

  }//end function

  /*
  initiate a transaction, to be 
  @return boolean success
  */
  public function beginTransaction(): bool {

    return ($this->dbh)->beginTransaction();

  }

  /*
  commit a transaction and return to auto-commit mode
  @return boolean success
  */
  public function commit(): bool {

    return ($this->dbh)->commit();

  }

  /*
  roll back a transaction that is initiated by beginTransaction() and return to auto-commit mode
  @return boolean success
  */
  public function rollBack(): bool {

    return ($this->dbh)->rollBack();

  }

  /*
  turn off foreign key checks for a transaction.
  */
  public function deferForeignKeyChecks(): void {

    $this->dbh->query("SET FOREIGN_KEY_CHECKS=0");

  }

  /*
  turn on foreign key checks for a transaction.
  */
  public function restoreForeignKeyChecks(): void {

    $this->dbh->query("SET FOREIGN_KEY_CHECKS=1");

  }

  /*
  Collection of all SQL query strings used throughout the app
  */

  //for members
  public const readAllUsersQuery = "SELECT user FROM members ORDER BY timestamp DESC";
  //select * number of latest users
  public const readNewUsersQuery = "SELECT user FROM members ORDER BY timestamp DESC LIMIT :number";

  //for account logging
  public const createMemberQuery = "INSERT INTO members (user, password, email) VALUES (:user, :password, :email)"; //create a new record of members
  public const createBasicProfileQuery = "INSERT INTO profiles (user, firstname, lastname) VALUES (:user, :firstname, :lastname)"; //create a default profile
  public const readPasswordQuery = "SELECT password FROM members WHERE user = :user";
  public const readMembersTableQuery = "SELECT * FROM members WHERE user = :user";
  public const readEmailQuery = "SELECT email FROM members where user = :user";

  //for profile
  public const readProfileQuery = "SELECT * FROM profiles WHERE user = :user";
  public const readBasicProfileQuery = "SELECT firstname, lastname, profilePictureURL FROM profiles WHERE user = :user";
  public const updateProfileQuery = "UPDATE profiles SET about = :about, gender = :gender, ageGroup = :ageGroup, location = :location, job = :job, company = :company, major = :major, school = :school, interests = :interests, quote = :quote WHERE user = :user"; 
  
  //for profile edits (atomic: one statement for each updatable field, inefficient and redundant but simple)
  public const updateProfilePictureQuery = "UPDATE profiles SET profilePictureURL = :url, profilePictureMIME = :mime WHERE user = :user"; 
  public const updateProfilePictureToDefaultQuery = "UPDATE profiles SET profilePictureURL = DEFAULT, profilePictureMIME = DEFAULT WHERE user = :user";
  public const updateProfileWallpaperQuery = "UPDATE profiles SET wallpaper = :wallpaper WHERE user = :user";
  public const updateProfileWallpaperToNullQuery = "UPDATE profiles SET wallpaper = NULL WHERE user = :user";
  public const updateProfileAboutQuery = "UPDATE profiles SET about = :about WHERE user = :user";
  public const updateProfileGenderQuery = "UPDATE profiles SET gender = :gender WHERE user = :user";
  public const updateProfileDobQuery = "UPDATE profiles SET dob = :dob WHERE user = :user";
  public const updateProfileInterestsQuery = "UPDATE profiles SET interests = :interests WHERE user = :user";
  public const updateProfileQuoteQuery = "UPDATE profiles SET quote = :quote WHERE user = :user";
  public const updateProfileCityQuery = "UPDATE profiles SET city = :city WHERE user = :user";
  public const updateProfileCountryQuery = "UPDATE profiles SET country = :country WHERE user = :user";
  public const updateProfileJobQuery = "UPDATE profiles SET job = :job WHERE user = :user";
  public const updateProfileCompanyQuery = "UPDATE profiles SET company = :company WHERE user = :user";
  public const updateProfileMajorQuery = "UPDATE profiles SET major = :major WHERE user = :user";
  public const updateProfileSchoolQuery = "UPDATE profiles SET school = :school WHERE user = :user";
  public const updateProfileWebsiteQuery = "UPDATE profiles SET website = :website WHERE user = :user";
  public const updateProfileSocialMediaQuery = "UPDATE profiles SET socialmedia = :socialmedia WHERE user = :user";
  public const updateMembersEmailQuery = "UPDATE members SET email = :email WHERE user = :user";


  //for friends
  public const createFriendsDataQuery = "INSERT INTO friends_data (user1, user2) VALUES (:user1, :user2)";
  public const createFriendRequestQuery = "INSERT INTO friends (user1, user2, status) VALUES (:requestSender, :requestRecipient, 2)";
  //get all users who are friends of a certain user
  public const readAllFriendsQuery = "SELECT friend.user FROM (SELECT user2 AS user FROM friends WHERE user1 = :user AND status = 1 UNION SELECT user1 AS user FROM friends WHERE user2 = :user AND status = 1) AS friend";
  public const readFriendshipQuery = "SELECT user1, user2, unix_timestamp(timestamp) AS timestamp FROM friends WHERE user1 = :a AND user2 = :b AND status = 1 UNION SELECT user1, user2, unix_timestamp(timestamp) AS timestamp FROM friends WHERE user1 = :b AND user2 = :a AND status = 1"; //null if a and b are not confirmed friends
  public const readFriendsDataQuery = "SELECT * from friends_data WHERE user1 = :user1 AND user2 = :user2";
  //get all users who are not friends of a certain user
  public const readAllNotFriendsQuery = "SELECT members.user FROM 
                                      members 
                                      LEFT JOIN 
                                      (SELECT user2 AS user FROM friends WHERE user1 = :user AND status = 1 UNION SELECT user1 AS user FROM friends WHERE user2 = :user AND status = 1) AS friends 
                                      ON members.user = friends.user WHERE friends.user IS NULL AND members.user != :user";
  //get a certain number of users who are not friends of a certain user, chosen at random
  public const readSomeNotFriendsQuery = "SELECT members.user FROM 
                                      members 
                                      LEFT JOIN 
                                      (SELECT user2 AS user FROM friends WHERE user1 = :user AND status = 1 UNION SELECT user1 AS user FROM friends WHERE user2 = :user AND status = 1) AS friends 
                                      ON members.user = friends.user WHERE friends.user IS NULL AND members.user != :user ORDER BY RAND() LIMIT :number";
  public const updateFriendNotesQuery = "UPDATE friends_data SET notes = :notes WHERE user1 = :user1 AND user2 = :user2";
  public const updateFollowingQuery = "UPDATE friends_data SET following = IF(following = 1, 0, 1) WHERE user1 = :user1 AND user2 = :user2"; //toggle following, if initially 0 update to 1, if initially 1 update to 0
  public const updateFriendRequestQuery = "UPDATE friends SET status = 1 WHERE user1 = :requestSender AND user2 = :requestRecipient";
  public const deleteFriendRequestQuery = "DELETE FROM friends WHERE user1 = :requestSender AND user2 = :requestRecipient";
  public const deleteFriendshipQuery = "DELETE FROM friends WHERE (user1 = :a AND user2 = :b) OR (user1 = :b AND user2 = :a)";
  public const deleteFriendsDataQuery = "DELETE FROM friends_data WHERE (user1 = :a AND user2 = :b) OR (user1 = :b AND user2 = :a)";
  
  //for messages
  public const createMessageQuery = "INSERT INTO messages VALUES (NULL, :time, :from, :to, :message)";
  public const readConversationWithQuery = "SELECT * FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :chatWith UNION SELECT * FROM messages WHERE sender = :chatWith AND recipient = :me) AS conversation ORDER BY timestamp ASC";
  public const readConversationWithSinceQuery = "SELECT * FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :chatWith AND timestamp >= :since UNION SELECT * FROM messages WHERE sender = :chatWith AND recipient = :me AND timestamp >= :since) AS conversation ORDER BY timestamp ASC";
  public const readChattedWithQuery = "SELECT MAX(timestamp) AS lastTime, chatWith FROM ( SELECT sender AS chatWith, timestamp FROM messages WHERE recipient = :me UNION SELECT recipient AS chatWith, timestamp FROM messages WHERE sender = :me) AS m GROUP BY chatWith ORDER BY lastTime DESC";

  
  /*
  for posts statistics
  */
  public const readPostNumberQuery = "SELECT COUNT(posts.id) AS number FROM posts LEFT JOIN text_posts ON posts.id = text_posts.post_id WHERE text_posts.text_for IS NULL AND posts.user = :user";
  public const readImagePostNumberQuery = "SELECT COUNT(*) FROM posts WHERE post_type = 2 AND user = :user";  
  public const readImagesNumber = "SELECT COUNT(*) FROM posts INNER JOIN image_posts ON posts.id = image_posts.post_id WHERE posts.user = :user";
  public const readTextPostNumberQuery = "SELECT COUNT(*) FROM posts INNER JOIN text_posts ON posts.id = text_posts.post_id WHERE text_posts.text_for IS NULL AND posts.user = :user";
  public const readNumberOfLikesQuery = "SELECT COUNT(*) AS likes FROM (SELECT id FROM posts WHERE user = :user) AS posts INNER JOIN post_reactions ON posts.id = post_reactions.post_id WHERE post_reactions.reaction > 0";
  public const readNumberOfDislikesQuery = "SELECT COUNT(*) AS dislikes FROM (SELECT id FROM posts WHERE user = :user) AS posts INNER JOIN post_reactions ON posts.id = post_reactions.post_id WHERE post_reactions.reaction < 0";

  //for post likes
  public const createPostLikeQuery = "INSERT INTO post_reactions VALUES (:post_id, :user, 1)";
  public const createPostDislikeQuery = "INSERT INTO post_reactions VALUES (:post_id, :user, -1)";
  public const readPostLikedByQuery = "SELECT user FROM post_reactions WHERE post_id = :post_id AND reaction > 0";
  public const readPostDislikedByQuery = "SELECT user FROM post_reactions WHERE post_id = :post_id AND reaction < 0";
  public const deletePostLikeQuery = "DELETE FROM post_reactions WHERE post_id = :post_id AND user = :user";
  public const deletePostDislikeQuery = "DELETE FROM post_reactions WHERE post_id = :post_id AND user = :user";
 

  /*
  for post comments
  */
  public const createPostCommentQuery = "INSERT INTO post_comments (post_id, user, comment) VALUES (:post_id, :user, :comment)";
  public const readPostCommentsQuery = "SELECT * FROM post_comments WHERE post_id = :post_id";
  public const updatePostCommentQuery = "UPDATE post_comments SET comment = :comment WHERE comment_id = :comment_id";
  public const deletePostCommentQuery = "DELETE FROM post_comments WHERE comment_id = :comment_id";

  /*
  for post contents
  */
  //for all posts
  public const readPostQuery = "SELECT user, UNIX_TIMESTAMP(timestamp) AS timestamp, post_type AS type FROM posts WHERE id = :id";
  //retrieve [id timestamp, type] for every post created by user
  public const readPostsQuery = "SELECT id, UNIX_TIMESTAMP(timestamp) AS timestamp, post_type AS type FROM posts WHERE post_type = 2 AND user = :user /*the image posts*/
                            UNION
                            SELECT posts.id, UNIX_TIMESTAMP(posts.timestamp) AS timestamp, posts.post_type AS type FROM posts INNER JOIN text_posts ON posts.id = text_posts.post_id WHERE user = :user AND text_for IS NULL /*the standalone text posts*/
                            ORDER BY timestamp DESC LIMIT :offset, :count"; 

  //retrieve [id, timestamp, text, image, description] of every posted content (thus multiple image rows from the same post) submitted by user
  public const readPostContentsQuery = "SELECT contents.*, UNIX_TIMESTAMP(posts.timestamp) AS timestamp FROM

                              (SELECT * FROM posts WHERE user = :user) AS posts

                              INNER JOIN 

                              (SELECT * FROM 

                                (SELECT image_posts.post_id AS id, imageURL AS image, description, content AS text FROM 
                                image_posts LEFT JOIN text_posts 
                                ON image_posts.post_id = text_posts.text_for) AS image_posts /*every image posted with accompanying text*/

                                UNION

                                (SELECT text_posts.post_id AS id, imageURL AS image, description, content AS text FROM 
                                text_posts LEFT JOIN image_posts ON text_posts.text_for != image_posts.post_id WHERE text_for IS NULL)

                              ) AS contents  /*every single content, text and image*/

                              ON posts.id = contents.id
                              ORDER BY timestamp DESC LIMIT :offset, :count";
  public const deletePostQuery = "DELETE FROM posts WHERE id = :id";

  //for text posts
  public const createTextPostQuery = "INSERT INTO posts (id, user, post_type) VALUES (:id, :user, 1)";
  public const createTextPostContentQuery = "INSERT INTO text_posts (post_id, content) VALUES (:post_id, :content)";
  //read text posts by user
  public const readTextPostsQuery = "SELECT posts.id, UNIX_TIMESTAMP(posts.timestamp) AS timestamp, text_posts.content AS post FROM posts INNER JOIN text_posts ON posts.id = text_posts.post_id WHERE posts.user = :user ORDER BY posts.timestamp DESC LIMIT :offset, :count";
  //read one text post by id
  public const readTextPostQuery = "SELECT posts.user, UNIX_TIMESTAMP(posts.timestamp) AS timestamp, text_posts.content AS post FROM posts INNER JOIN text_posts ON posts.id = text_posts.post_id WHERE posts.id = :id";
  public const updateTextPostForQuery = "UPDATE text_posts SET text_for = :for WHERE post_id = :post_id"; //specify for which non-text post this text post is associated with
  public const updateTextPostQuery = "UPDATE text_posts SET content = :content WHERE post_id = :post_id";

  //for image posts
  public const createImagePostQuery = "INSERT INTO posts (id, user, post_type) VALUES (:id, :user, 2)";
  public const createImagePostContentQuery = "INSERT INTO image_posts (id, post_id, description) VALUES (:id, :post_id, :description)";
  //read image contents by user
  public const readImagePostsQuery = "SELECT images.id, images.timestamp, texts.text, images.imageURL AS image, images.description FROM 
                                (SELECT posts.id, UNIX_TIMESTAMP(posts.timestamp) AS timestamp, image_posts.imageURL, image_posts.description FROM posts INNER JOIN image_posts ON posts.id = image_posts.post_id WHERE posts.user = :user ORDER BY posts.timestamp DESC LIMIT :offset, :count) AS images 
                                LEFT JOIN 
                                (SELECT text_posts.content AS text, text_posts.text_for FROM posts INNER JOIN text_posts ON posts.id = text_posts.post_id WHERE posts.user = :user AND text_posts.text_for IS NOT NULL) AS texts 
                                ON images.id = texts.text_for";  
  //read one image post by id
  public const readImagePostQuery = "SELECT images.*, text_posts.content AS text FROM 
                                (SELECT posts.user, posts.id, UNIX_TIMESTAMP(posts.timestamp) AS timestamp, image_posts.id AS image_id ,image_posts.imageURL AS image, image_posts.imageMIME AS mime, image_posts.description FROM posts INNER JOIN image_posts ON posts.id = image_posts.post_id WHERE posts.id = :id) AS images 
                                LEFT JOIN text_posts ON images.id = text_posts.text_for";
  //read the ids of a certain number of images
  public const readImagePostIdQuery = "SELECT DISTINCT * FROM (SELECT posts.id FROM posts INNER JOIN image_posts ON posts.id = image_posts.post_id WHERE posts.user = :user ORDER BY posts.timestamp DESC LIMIT :count) AS image_ids";
  //read the one single specific image inside image posts table using the table primary key
  public const readImagePostImageQuery = "SELECT imageURL AS image from image_posts WHERE id = :id";
  //read possibly multiple images belonging to an image post using the image post id
  public const readImagePostImagesQuery = "SELECT imageURL AS image from image_posts WHERE post_id = :id";
  public const readImagePostMaximumIdQuery = "SELECT MAX(id) AS max_id from image_posts"; //retrieve the max id used, used for knowing the next id to use in codes
  public const updateImagePostImageQuery = "UPDATE image_posts SET imageURL = :imageURL, imageMIME = :imageMIME WHERE id = :id"; //recall image belongs to a post, so a post must exist before an image can be persisted
  public const updateImagePostDescriptionQuery = "UPDATE image_posts SET description = :description WHERE id = :id";
  public const deleteImagePostQuery = "DELETE FROM image_posts WHERE id = :id";
  
  
  
  

} //end class

?>

