<?php

/*
abstract class encapsulating a user's posts
*/

abstract class Post {

  protected $id; //an id to identify each post
  protected $user; //user of the post
  protected $timestamp; //unix epoch timestamp of the post
  protected $content; //contents of the post, varies depending on concrete classes
  protected $comments = []; //others' comments of the post
  protected $likedBy = []; //users who reacted positively to the post
  protected $dislikedBy = []; //users who reacted negatively to the post
  protected $likes; //number of positive reactions
  protected $dislikes; //number of negative reactions
  protected $errorCodes = []; //array to append errors to. -1 for system errors, 1 for unfound object id, 2 for content above defined maximum size, 3 for content file format issue, 4 for post files over max number
  protected $mysql; 

  /*
  constructor, used to create a new object or reference a created object
  some id is used to gain handle to a created object, that can be database id, filename, etc
  for new creation, content is provided; for old reference, content is null
  @param the content of the post
  @param the id referencing an existing post
  */
  public function __construct($content = null, $id = null) {

    if (is_null($content) && is_null($id)) {
      throw new Exception("parameters cannot all be null.");
    }

    $this->mysql = MySQL::getInstance(); //database accessor instance

    if ( isset($content) ) { //content provided, so a new post

      $this->user = $_SESSION["user"]; //set to session user
      $this->id = $this->user . time(); //concatenate username and obj creation time as id
      $this->likes = 0;
      $this->dislikes = 0;
    
    } else { //no content, so reference to old post

      $this->id = $id;
      $this->content = $this->getContent();
      $this->comments = $this->mysql->request($this->mysql->readPostCommentsQuery, [":post_id" => $id]);
      $this->likedBy = $this->mysql->request($this->mysql->readPostLikedByQuery, [":post_id" => $id], true);
      $this->dislikedBy = $this->mysql->request($this->mysql->readPostDislikedByQuery, [":post_id" => $id], true);;
      $this->likes = count($this->likedBy);
      $this->dislikes = count($this->dislikedBy);

    }
    
  }

  /*
  function to complete and submit a new post
  */
  abstract public function post();

  /*
  function to remove an existing post
  */
  abstract public function delete(): void;

  /*
  function to modify an existing post
  */
  abstract public function update();

  /*
  function to retrieve content of an existing post
  */
  abstract public function getContent();

  /*
  initialize a liking of a post
  @param username, user doing the liking of this post
  */
  public function like(string $username): self {
    //proceed only if user has not already liked and it is not own post
    if ( !$this->haveAlreadyLiked($username) && $this->user != $username ) {

      try {

        $this->mysql->request($this->mysql->createPostLikeQuery, [":post_id" => "$this->id", ":user" => $username]); //add the like to db
        array_push($this->likedBy, $username); //add username to array
        $this->likes++;

      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        throw $ex;

      }

    }

    return $this;

  }
  
  /*
  initialzie disliking of a post
  @param username, user doing the disliking of this post
  */
  public function dislike(string $username): self {
    //proceed only if user has not already disliked and it is not own post
    if ( !$this->haveAlreadyDisliked($username) && $this->user != $username ) {
      
      try {

        $this->mysql->request($this->mysql->createPostDislikeQuery, [":post_id" => "$this->id", ":user" => $username]); //add the dislike to db
        array_push($this->dislikedBy, $username); //add username to array
        $this->dislikes++;

      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        throw $ex;

      }

    }

    return $this;

  }

  /*
  undo an expressed like
  @param username, user doing the unliking of the post
  */
  public function unlike(string $username): self {

    if ( $this->haveAlreadyLiked($username) ) {

      try {

        $this->mysql->request($this->mysql->deletePostLikeQuery, [":post_id" => "$this->id", ":user" => $username]); //remove the like from db
        unset($this->likedBy[array_search($username, $this->likedBy)]); //remove username from array
        $this->likes--;

      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        throw $ex;

      }

    }

    return $this;

  }

  /*
  undo an expressed dislike
  @param username, user doing the undisliking of this post
  */
  public function undislike(string $username): self {

    if ( $this->haveAlreadyDisliked($username) ) {

      try {

        $this->mysql->request($this->mysql->deletePostDislikeQuery, [":post_id" => "$this->id", ":user" => $username]); //remove the dislike from db
        unset($this->dislikedBy[array_search($username, $this->dislikedBy)]); //remove username from array
        $this->dislikes--;

      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        throw $ex;

      }

    }

    return $this;

  }

  /*
  flagging if an user has already reacted to the post positively
  @param username, the user of whom has already liked the post
  */
  public function haveAlreadyLiked(string $username): bool {

    return in_array($username, $this->likedBy);

  }

  /*
  flagging if an user has already reacted to the post negatively
  @param username, the user of whom has already disliked the post
  */
  public function haveAlreadyDisliked(string $username): bool {

    return in_array($username, $this->dislikedBy);

  }

  /*
  getter of this post data
  */
  public function getData(): array {

    return ["id" => $this->id, "user" => $this->user, "timestamp" => $this->timestamp, "content" => $this->content, "comments" => $this->comments, "likes" => $this->likes, "dislikes" => $this->dislikes];

  }

  /*
  function to add a comment to a post by another user
  @param username, the username of the user making a comment
  @param comment, the comment to be added
  */
  public function addComment(string $username, string $comment): self {

    $comment = self::cleanString($comment);

    try {

      $params = [":post_id" => "$this->id", ":user" => $username, ":comment" => $comment];
      $this->mysql->request($this->mysql->createPostCommentQuery, $params);

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      throw $ex;

    }
    
    return $this;

  }

  /*
  function to edit an added comment to a post by another user
  @param comment_id, the id in the comments table of which to update
  @param newComment, the new comments to update to
  */
  public function editComment(int $comment_id, string $newComment): self {

    $newComment = self::cleanString($newComment);

    try {

      $params = [":comment_id" => $comment_id, ":comment" => $newComment];
      $this->mysql->request($this->mysql->updatePostCommentQuery, $params);

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      throw $ex;

    }

    return $this;

  }

  /*
  function to delete an added comment to a post by another user
  @param comment_id, the id in the comments table of which to remove
  */
  public function deleteComment(int $comment_id): self {

    try {

      $this->mysql->request($this->mysql->deletePostCommentQuery, [":comment_id" => $comment_id]);

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      throw $ex;

    }

    return $this;

  }
  
  /*
  errorCodes getter
  */
  public function getErrors(): array {

    return array_unique($this->errorCodes);

  }

  /*
  class utility to clean string inputs, by trimming, sanitizing, and replacing double whitespaces
  @param pre-clean string
  @return post-clean string
  */
  protected static function cleanString(string $input): string {

    $input = preg_replace('/\s\s+/', ' ',$input); //replace double whitespaces to single
    $input = filter_var(trim($input), FILTER_SANITIZE_STRING); //trim, sanitize
    return $input;

  }








} //end class

?>

