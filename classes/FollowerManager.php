<?php

/*
interface in an Observer pattern, typically known as the Subject. 
A user has followers (observers) and whenever a new post is made, his followers are notified.
*/

interface FollowerManager {
  
  /*
  function to add a follower to the Subject's list
  */
  public function addFollower(string $follower): void;

  /*
  function to remove a follower from the Subject's list
  */
  public function removeFollower(string $follower): void;

  /*
  notify all followers about a new item by calling their common methods
  */
  public function notifyFollowers(string $postId): void;

}

?>
