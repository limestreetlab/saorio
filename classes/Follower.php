<?php

/*
interface in an Observer pattern, typically known as the Observer
A user has followers (observers) and whenever a new post is made, his followers are notified.
*/

interface Follower {
  
  /*
  receive a post notice from a following
  @param id of the post
  */
  public function receivePost(string $postId): void;

}