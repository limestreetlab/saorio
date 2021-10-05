<?php

class PostOfText extends Post {

  //new variables
  const MAX_LENGTH = 500; //max length of a text post string

  /*
  constructor, used to create a new object or reference a created object
  Post id is used to identify an existing post
  for new creation, content is provided; for old reference, content is null
  @param content, the content of the post
  @param id, the post id to identify a specific post
  */
  public function __construct(string $content = null, string $id = null) {

    parent::__construct($content, $id);

    if ( isset($content) ) { //content provided, so a new post creation

      $this->content = self::cleanString($content);
      $this->id = $this->user . time(); //concatenate username and obj creation time as id

      if (strlen($this->content) > self::MAX_LENGTH) { //check if length below max
        array_push($this->errorCodes, 2);
        throw new Exception("entered texts exceed max length of " . self::MAX_LENGTH);
      }

    } else { //no content, so reference to an old post

      $postData = $this->mysql->request($this->mysql->readTextPostQuery, [":id" => $id]);

      if (!$postData) {
        array_push($this->errorCodes, 1);
        throw new Exception("the provided id " . $this->id . "cannot be found.");
      }

      $this->user = $postData[0]["user"];
      $this->timestamp = $postData[0]["timestamp"];

    }
    
  }

  /*
  @Override
  */
  public function post(): bool {

    if (!empty($this->content)) { //do nothing if empty content

      try {

        $this->mysql->request($this->mysql->createTextPostQuery, [":id" => $this->id, ":user" => $this->user]);
        $this->mysql->request($this->mysql->createTextPostContentQuery, [":post_id" => $this->id, ":content" => $this->content]);
        return true;

      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        return false;

      }

    }

  }

  /*
  @Override
  */
  public function update(string $newContent = null): self {

    $newContent = self::cleanString($newContent);

    if (strlen($newContent) > self::MAX_LENGTH) { //check if length below max
      array_push($this->errorCodes, 2);
      throw new Exception("entered texts exceed max length.");
    }

    if (!empty($newContent)) { //do nothing if empty content

      try {

        $this->mysql->request($this->mysql->updateTextPostQuery, [":content" => $newContent, ":post_id" => $this->id]);
        $this->content = $newContent;

      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        throw $ex;
      
      }

    }

    return $this;

  }

  /*
  @Override
  */
  public function delete(): void {

    try {

      $this->mysql->request( $this->mysql->deletePostQuery, [":id" => $this->id] );
      unset($this->id); //cannot unset the object itself, merely unset its key instance handle variable

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      throw $ex;
    
    }

  }

  /*
  @Override
  */
  public function getContent(): string {

    if ( !isset($this->content) ) {

      try {
        
        return $this->mysql->request( $this->mysql->readTextPostQuery, [":id" => $this->id] )[0]["post"];
      
      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        throw $ex;

      }
    
    } else {

      return $this->content;

    }

  }




}

?>