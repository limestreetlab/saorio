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

    } else { //no content, so reference to an old post

      $postData = $this->mysql->request(MySQL::readTextPostQuery, [":id" => $id]);

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
  text post can be contained in a non-text post such as an image post containing a text post to hold text content
  hence, creating a non-text post can result in creating two kinds of posts (that non-text post itself and a text post)
  given current model where all types of posts get recorded in one table and contents in different tables, the shared timestamp-based id for the common table can overlap
  when an overlapping id occurs, one of the two posts should adjust its id; text-post is the one to adjust here
  text-post adjusts its id by changing the last digit, incrementing by 1 for 0-8 or to 1 for 9
  note that because only text-post will adjust, the non-text post must be recorded first so when ids overlap, text-post will experience error anad just
  id only adjusts once, if it still fails, an exception will be thrown
  */
  public function post(): bool {

    if (strlen($this->content) > self::MAX_LENGTH) { //check if length below max
      array_push($this->errorCodes, 5);
      return false;
    } 

    if (!empty($this->content)) { //do nothing if empty content

      try { //create a record in the common post table and then one in the text post record, using existing id

        $this->mysql->beginTransaction();

        $this->mysql->request(MySQL::createTextPostQuery, [":id" => $this->id, ":user" => $this->user]); //id can potentially clash 
        $this->mysql->request(MySQL::createTextPostContentQuery, [":post_id" => $this->id, ":content" => $this->content]);

        return $this->mysql->commit();

      } catch (PDOException $ex) {

        if ($ex->errorInfo[0] == 23000 && $ex->errorInfo[1] == 1062) { //primary key duplicate exception

          try { //re-try using a new id

            $this->id = substr($this->id, 0, strlen($this->id) - 1) . ((string)(substr($this->id, -1) + 1))[0]; //increment by 1; when last digit is 9, take 1st digit (=1) instead of using 10
            $this->mysql->rollBack(); //roll back so to start from scratch
            
            $this->mysql->beginTransaction(); //start again

            $this->mysql->request(MySQL::createTextPostQuery, [":id" => $this->id, ":user" => $this->user]); 
            $this->mysql->request(MySQL::createTextPostContentQuery, [":post_id" => $this->id, ":content" => $this->content]);

            return $this->mysql->commit(); 

          } catch (PDOException $ex) { //failed again for whatever reason, giving up

            $this->mysql->rollBack(); //roll back 
            array_push($this->errorCodes, $ex->getMessage()); //log system error
            return false;

          }

        } else { //non-primary-key error

          $this->mysql->rollBack(); //roll back 
          array_push($this->errorCodes, $ex->getMessage()); //log system error
          return false;

        }

      } //end catch 

    }//end if

  }//end function

  /*
  @Override
  */
  public function update(string $newContent = null): self {

    $newContent = self::cleanString($newContent);

    if (strlen($newContent) > self::MAX_LENGTH) { //check if length below max
      array_push($this->errorCodes, 5);
      throw new Exception("entered texts exceed max length of " . self::MAX_LENGTH);
    }

    if (!empty($newContent)) { //do nothing if empty content

      try {

        $this->mysql->request(MySQL::updateTextPostQuery, [":content" => $newContent, ":post_id" => $this->id]); //update db
        $this->content = $newContent; //update object

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

      $this->mysql->request( MySQL::deletePostQuery, [":id" => $this->id] );
      unset($this->id); //cannot unset the object itself, merely unset its key instance handle variable

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      throw $ex;
    
    }

  }

  /*
  @Override
  @return string the content of this text post
  */
  public function getContent(): string {

    if ( !isset($this->content) ) {

      try {
        
        return $this->mysql->request( MySQL::readTextPostQuery, [":id" => $this->id] )[0]["post"];
      
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