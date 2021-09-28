<?php

/*
abstract class encapsulating a user's posts
*/

abstract class Post {

  protected $id; //an id to identify each post
  protected $user; //user of the post
  protected $timestamp; //unix epoch timestamp of the post
  protected $content; //contents of the post
  protected $comments = []; //others' comments of the post
  protected $likedBy = []; //users who reacted positively to the post
  protected $dislikedBy = []; //users who reacted negatively to the post
  protected $likes; //number of positive reactions
  protected $dislikes; //number of negative reactions

  /*
  constructor
  */
  public function __construct(mixed $content, int $id = null) {

    $this->mysql = MySQL::getInstance(); //database accessor instance

    if ( isset($id) ) { //id provided, so referencing an existing post

      $this->id = $id;
      $this->user = $_SESSION["user"]; //set to session user
      //$this->timestamp = ;
      $this->content = $this->getContent($this->id);
      //$this->comments = ;
      //$this->likedBy = ;
      //$this->dislikedBy = ;
      $this->likes = count($this->likedBy);
      $this->dislikes = count($this->dislikedBy);
      
    
    } else { //no id, so a new post

      $this->id = null;
      $this->user = $_SESSION["user"]; //set to session user
      $this->content = $content;
      $this->likes = 0;
      $this->dislikes = 0;

    }
  }

  /*
  function to complete and submit a post
  */
  abstract public function post(): bool;

  /*
  function to remove a post
  */
  abstract public function delete(): bool;

  /*
  function to modify a posted post
  */
  abstract public function edit(): bool;

  /*
  getter of a post's contents
  */
  abstract public function getContent(int $id): string;

  /*
  liking a post
  */
  public function like(string $username): void {

    if ( !$this->haveAlreadyLiked($username) ) {

      array_push($this->likedBy, $username);

    }

  }
  
  /*
  disliking a post
  */
  public function dislike(string $username): void {

    if ( !$this->haveAlreadyDisliked($username) ) {
      
      array_push($this->dislikedBy, $username);
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



  }

  /*
  function to edit an added comment to a post by another user
  */
  public function editComment(int $id, string $comment): void {

    $comment = self::cleanString($comment);

  }

  /*
  function to delete an added comment to a post by another user
  */
  public function deleteComment(int $id): void {

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
























}

?>

