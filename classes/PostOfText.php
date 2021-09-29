<?php

class PostOfText extends Post {

  /*
  constructor
  */
  public function __construct(string $content = null, mixed $id = null) {

    parent::__construct($content, $id);

    if ( isset($content) ) { //content provided, so a new post

      $this->content = self::cleanString($content);      

    } else { //no content, so reference to an old post

      $this->user = $this->mysql->request($this->mysql->readTextPostQuery, [":id" => $id])[0]["user"];
      $this->timestamp = $this->mysql->request($this->mysql->readTextPostQuery, [":id" => $id])[0]["timestamp"];

    }
    
  }

  /*
  @Override
  */
  public function post(): bool {

    try {

      $this->id = $this->user . time(); //concatenate username and current time
      $this->mysql->request($this->mysql->createTextPostQuery, [":id" => $this->id, ":user" => $this->user]);
      $this->mysql->request($this->mysql->createTextPostContentQuery, [":post_id" => $this->id, ":content" => $this->content]);
      return true;

    } catch (Exception $ex) {

      return false;

    }

  }

  /*
  @Override
  */
  public function update($newContent): bool {

    try {

      $this->mysql->request($this->mysql->updateTextPostQuery, [":content" => $newContent, ":post_id" => $this->id]);
      $this->content = $newContent;
      return true;

    } catch (Exception $ex) {

      return false;
    
    }

  }

  /*
  @Override
  */
  public function delete(): bool {

    try {

      $this->mysql->request( $this->mysql->deletePostQuery, [":id" => $this->id] );
      unset($this->id); //cannot unset the object itself, merely unset its key instance handle variable
      return true;

    } catch (Exception $ex) {

      return false;
    
    }

  }

  /*
  @Override
  */
  public function getContent(): string {

    return $this->mysql->request( $this->mysql->readTextPostQuery, [":id" => $this->id] )[0]["post"];

  }




}

?>