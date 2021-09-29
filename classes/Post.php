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
  protected $mysql; 

  /*
  constructor
  */
  public function __construct(mixed $content = null, mixed $id = null) {

    if (is_null($content) && is_null($id)) {
      throw new Exception("parameters cannot all be null.");
    }

    $this->mysql = MySQL::getInstance(); //database accessor instance

    if ( isset($content) ) { //content provided, so a new post

      $this->id = null;
      $this->user = $_SESSION["user"]; //set to session user
      $this->likes = 0;
      $this->dislikes = 0;
    
    } else { //no content, so reference to old post

      $this->id = $id;
      $this->content = $this->getContent();
      $this->comments = $this->mysql->request($this->mysql->readPostCommentsQuery, [":post_id" => $id]);
      $this->likedBy = $this->mysql->request($this->mysql->readPostLikedByQuery, [":post_id" => $id]);
      $this->dislikedBy = $this->mysql->request($this->mysql->readPostDislikedByQuery, [":post_id" => $id]);;
      $this->likes = count($this->likedBy);
      $this->dislikes = count($this->dislikedBy);

    }
  }

  /*
  function to complete and submit a new post
  */
  abstract public function post(): bool;

  /*
  function to remove an existing post
  */
  abstract public function delete(): bool;

  /*
  function to modify an existing post
  */
  abstract public function update($newContent): bool;

  /*
  function to retrieve content of an existing post
  */
  abstract public function getContent(): mixed;

  /*
  liking a post
  */
  public function like(string $username): void {

    if ( !$this->haveAlreadyLiked($username) ) {

      $this->mysql->request($this->mysql->createPostLikeQuery, [":post_id" => "$this->id", ":user" => $username]); //add the like to db
      array_push($this->likedBy, $username); //add to array

    }

  }
  
  /*
  disliking a post
  */
  public function dislike(string $username): void {

    if ( !$this->haveAlreadyDisliked($username) ) {
      
      $this->mysql->request($this->mysql->createPostDislikeQuery, [":post_id" => "$this->id", ":user" => $username]); //add the dislike to db
      array_push($this->dislikedBy, $username); //add to array

    }

  }

  /*
  undo a given like
  */
  public function unlike(string $username): void {

    if ( $this->haveAlreadyLiked($username) ) {

      $this->mysql->request($this->mysql->deletePostLikeQuery, [":post_id" => "$this->id", ":user" => $username]); //remove the like from db
      unset($this->likedBy[array_search($username, $this->likedBy)]); //remove from array

    }

  }

  /*
  undo a given dislike
  */
  public function undislike(string $username): void {

    if ( $this->haveAlreadyDisliked($username) ) {

      $this->mysql->request($this->mysql->deletePostDislikeQuery, [":post_id" => "$this->id", ":user" => $username]); //remove the dislike from db
      unset($this->dislikedBy[array_search($username, $this->dislikedBy)]); //remove from array

    }

  }

  /*
  flagging if an user has already reacted to the post positively
  */
  public function haveAlreadyLiked(string $username): bool {

    return in_array($username, $this->likedBy);

  }

  /*
  flagging if an user has already reacted to the post negatively
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
  */
  public function addComment(string $username, string $comment): void {

    $comment = self::cleanString($comment);

    $params = [":post_id" => "$this->id", ":user" => $username, ":comment" => $comment];
    $this->mysql->request($this->mysql->createPostCommentQuery, $params);

  }

  /*
  function to edit an added comment to a post by another user
  */
  public function editComment(int $id, string $comment): void {

    $comment = self::cleanString($comment);

    $params = [":comment_id" => $id, ":comment" => $comment];
    $this->mysql->request($this->mysql->updatePostCommentQuery, $params);

  }

  /*
  function to delete an added comment to a post by another user
  */
  public function deleteComment(int $id): void {

    $this->mysql->request($this->mysql->deletePostCommentQuery, [":comment_id" => $id]);

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

