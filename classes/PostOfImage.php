<?php

class PostOfImage extends Post {

  //new variables
  const MAX_IMAGES = 5; //max number of photos per post
  const MAX_DESCRIPTION_LENGTH = 100;//max text length for description
  protected $numberOfImages; //number of images contained in this post
  protected $content = []; //overriding instance variable to be an array (of arrays)
  protected $text; //text post object when a post contains text

  /*
  constructor, used to create a new object or reference a created object
  Post id is used to identify an existing object
  for new creation, content is provided; for old reference, content is null
  each image post can contain multiple images of which each can contain a text description
  so content is an array, each image element being an array having [file, description] 
  now, an image post can have an accompany text (for the entire post, not photo description)
  when a text exists, it must be included as the 1st element (string) of the content array before any image elements (arrays)
  if there is no text for the post, the first content element can be either null or starting image element
  @param content, an array of arrays, [string text or null, [File file1, string description1], [File file2, string description2], ...]
  @param id, post id
  */
  public function __construct(array $content = null, string $id = null) {
    
    parent::__construct($content, $id);

    if ( isset($content) ) { //content provided, so a new post creation

      //check content format is as defined [string or null, [File file1, string description1], [File file2, string description2], ...] or [[File file1, string description1], [File file2, string description2], ...]
      if (!is_array($content)) { //check content is an array

        array_push($this->errorCodes, -1);
        throw new Exception("code bugs: input content should be an array.");

      } elseif ( count(array_slice($content, 1))  != count(array_filter($content, function($el){return count($el)==2;})) ) { //check all elements (except 1st) are 2-element arrays

        array_push($this->errorCodes, -1);
        throw new Exception("code bugs: input content's elements (except first one) must be 2-element arrays.");

      } elseif ( !is_string($content[0]) && !is_null($content[0]) && !(is_array($content[0]) && count($content[0]) == 2) ) { //check 1st element is either string or null (for text) or 2-element img array (no text) 

        array_push($this->errorCodes, -1);
        throw new Exception("code bugs: input content's first element must be either a string, null, or 2-element array.");

      }

      //assign the text to variable and remove it from content array
      if ( is_string($content[0]) || is_null($content[0]) ) { //text provided as string or explicitly null
        
        $text = array_shift($content); //remove the first element from array to variable

      } else { //text is implicitly null

        $text = null; //declare null text, no need to change array which is already image-only

      }
      //create text post object if text isn't null
      $this->text = !is_null($text) ? new PostOfText($text) : null; 

      $this->numberOfImages = count($content); //at this point, array only contains images as 1st element text is popped off
      //check images don't exceed limit
      if ($this->numberOfImages > self::MAX_IMAGES) {
        array_push($this->errorCodes, 4);
        throw new Exception("Each post can contain up to " . self::MAX_IMAGES . "files but " . $this->numberOfImages . " provided");
      } 

      //note that id instance variable refers to the id of posts table, image id refers to id of image posts table 
      $image_id_available = $this->mysql->request($this->mysql->readImagePostMaximumIdQuery)[0]["max_id"] + 1; //next available id to use in the image post table

      //convert image files into Image File objects and clean strings for the content variable
      for($i = 0; $i < $this->numberOfImages; $i++) {

        $imageFileObj = new UploadedPostImageFile($image_id_available + $i, $content[$i][0]);
        $imageDescription = self::cleanString($content[$i][1]);

        if ( strlen($imageDescription) < self::MAX_DESCRIPTION_LENGTH ) {
          
          array_push($this->content, [$imageFileObj, $imageDescription]);

        } else {

          array_push($this->errorCodes, 2);
          throw new Exception("description length exceeds max length of " . self::MAX_DESCRIPTION_LENGTH);

        }

      }

    } else { //content not provided, so referencing an old post

      $postData = $this->mysql->request($this->mysql->readImagePostQuery, [":id" => $id]);

      if (!$postData) {
        array_push($this->errorCodes, 1);
        throw new Exception("the provided id " . $this->id . "cannot be found.");
      }

      $this->user = $postData[0]["user"];
      $this->timestamp = $postData[0]["timestamp"];
      $this->text = !is_null( $postData[0]["text"] ) ? new PostOfText( $postData[0]["text"] ) : null;
      $this->numberOfImages = count($this->content);

    }

  } //end constructor

  /*
  @Override
  function to submit the post object
  @return: true if all post image uploads succeed, false if not all succeed
  */
  public function post(): bool {

    try {

      $this->mysql->beginTransaction();

      $this->mysql->request($this->mysql->createImagePostQuery, [":id" => $this->id, ":user" => $this->user]); //create a post of image type
      
      //create a text post and then link it to the image post
      if (!is_null($this->text)) { //do something if text is not null
        $this->text->post(); //post the text post, which creates a post of text type and a text post
      }
      //a text post needs to be explicity linked to an image post to declare belonging
      $text_post_id = $this->text->getData()["id"]; //retrieve the id of the contained text post
      $this->mysql->request($this->mysql->updateTextPostForQuery, [":for" => $this->id, ":post_id" => $text_post_id]); //declaring the text post belongs to this image post
      //done with text post

      //create an entry for each image in database
      foreach ($this->content as $el) {

        $imageDescription = $el[1]; //the caption of this image
        $imageFileObj = $el[0]; //file object of this image
        $id = $imageFileObj->getId(); //image post this image belongs to
        $this->mysql->request($this->mysql->createImagePostContentQuery, [":id" => $id, ":post_id" => $this->id, ":description" => $imageDescription]); //create a image post

        if ( !$imageFileObj->upload() ) {

          switch($imageFileObj->getErrors()[0]) {
            //mapping file class error codes to post class error codes
            case 1: //file too big
              array_push($this->errorCodes, 2);
              break;
            case 2:
            case 3:
              array_push($this->errorCodes, 3);
              break;

          }
        
          throw new Exception();

        }

      } //image post created, text post (if one) created, images and descrptions added to image post

      $this->mysql->commit();
      return true;

    } catch (Exception $ex) {

      $this->mysql->rollBack();

      if (empty($this->errorCodes)) { //no file upload related error
        array_push($this->errorCodes, -1); //is system error
      }

      return false;

    }

  }

  /*
  @Override
  factory method to update parts of an existing post
  it can update image descriptions, delete images, add images
  @param switch, int to switch among update operations, 1 for description, 2 for img delete, 3 for img add, 4 for updating the associated text
  @param data, array containing data to accompany the update, [[img index, new description],...] for 1, [img index, ...] for 2, [[file, description],...] for 3, ["new text"] for 4
  @return success, true if all elements succeed, false if not all succeed
  */
  public function update(int $switch = 0, array $data = null): bool {

    try {

      switch ($switch) {

        case 1: //update image captions/descriptions

          $this->mysql->beginTransaction();

          foreach ($data as $el) {
            
            if ( !$this->updateImageDescription($el[0], $el[1]) ) {
              throw new Exception("image update failed.");  
            }

          }

          $this->mysql->commit();
          break;

        case 2: //remove images

          $this->mysql->beginTransaction();

          foreach ($data as $el) {

            if ( !$this->removeImage($el) ) {
              throw new Exception("image removal failed.");
            }

          }

          $this->mysql->commit();
          break;

        case 3: //add images

          $this->mysql->beginTransaction();

          foreach ($data as $el) {

            if ( !$this->addImage($el[0], $el[1]) ) {
              throw new Exception("image addition failed.");
            }
            
          }

          $this->mysql->commit();
          break;

        default:
          throw new Exception("unknown function parameter.");

        case 4: //update text
          
          $text = $data[0];
          $this->text->update($text); //update db

      }

    } catch (Exception $ex) {

      $this->mysql->rollBack();
      return false;

    }

  }

  /*
  function to modify existing image descriptions
  @param index, positional index of the image description inside the content array
  @param description, the new description
  */
  protected function updateImageDescription(int $index, string $description): bool {

    $description = self::cleanString($description);
    $imageFileObj = $this->content[$index][0]; //get the image file obj at input index

    if ($description < self::MAX_DESCRIPTION_LENGTH) {
      $this->content[$index][1] = $description; //update object
    } else {
      array_push($this->errorCodes, 2);
      return false;
    }

    $params = [":description" => $description, ":id" => $imageFileObj->getId()];

    try {

      $this->mysql->request($this->mysql->updateImagePostDescriptionQuery, $params); //update database
      return true;

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      return false;

    }
    
  }

  /*
  function to remove posted images from the post
  @param index, positional index of the image to remove
  */
  protected function removeImage(int $index): bool {

    $imageFileObj = $this->content[$index][0];
    unset($this->content[$index]); //update object
    $this->numberOfImages -= 1;
    
    try { //update database
      
      $this->mysql->request($this->mysql->deleteImagePostQuery, [":id" => $imageFileObj->getId()]); 
      return true;

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      return false;

    }

  }

  /*
  function to add an image to an existing post
  @param image, image file to be added
  @param description, description to accompany the image
  */
  protected function addImage($file, string $description): bool {

    $image_id_available = $this->mysql->request($this->mysql->readImagePostMaximumIdQuery)[0]["max_id"] + 1; //next available id to use in the image post table
    $description = self::cleanString($description);
    $imageFileObj = new UploadedPostImageFile($image_id_available, $file);

    if ( strlen($description) > self::MAX_DESCRIPTION_LENGTH ) {

      array_push($this->errorCodes, 2);
      throw new Exception();

    } elseif ($this->numberOfImages >= self::MAX_IMAGES) { 

      array_push($this->errorCodes, 4);
      throw new Exception();

    } else {      

      array_push($this->content, [$imageFileObj, $description]); //add to object
      $this->numberOfImages += 1;

    }

    //add to database
    try {
      
      $this->mysql->beginTransanction();

      $this->mysql->request($this->mysql->createImagePostContentQuery, [":id" => $image_id_available, ":post_id" => $this->id, ":description" => $description]);
      
      if ( !$imageFileObj->upload() ) { //upload failed

        switch($imageFileObj->getErrors()[0]) {
          //mapping file class error codes to post class error codes
          case 1: //file too big
            array_push($this->errorCodes, 2);
            break;
          case 2:
          case 3:
            array_push($this->errorCodes, 3);
            break;  
        }

        throw new Exception();
  
      } else { //upload done

        $this->mysql->commit();
        return true;

      }


    } catch (Exception $ex) {

      $this->mysql->rollBack();

      if (empty($this->errorCodes)) { //no file upload related error
        array_push($this->errorCodes, -1); //is system error
      }

      return false;

    }

  }

  /*
  @Override
  delete the entire post, not individual images
  */
  public function delete(): void {

    try {

      $this->mysql->request( $this->mysql->deletePostQuery, [":id" => $this->id] ); //remove post from database
      unset($this->id); //cannot unset the object itself, merely unset its key instance handle variable

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      throw $ex;
    
    }
    
  }

  /*
  @Override
  retrieve content of this post object
  */
  public function getContent(): array {

    if ( empty($this->content) ) {

      try {

        $postData = $this->mysql->request($this->mysql->readImagePostQuery, [":id" => $this->id]);
        
        foreach ($postData as $row) {
          
          $image_file_id = intval($row["image_id"]);
          $imageDescription = $row["description"]; 
          $imageFileObj = new UploadedPostImageFile($image_file_id, null);

          array_push( $this->content, [$imageFileObj, $imageDescription] );
          
        }        
        
        return $this->content;

      } catch (Exception $ex) {

        array_push($this->errorCodes, -1);
        throw $ex;

      }

    } else {
      
      return $this->content;

    }

  }







} //end close

?>