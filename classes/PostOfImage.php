<?php

class PostOfImage extends Post {

  //new variables
  const MAX_IMAGES = 10; //max number of photos per post
  const MAX_DESCRIPTION_LENGTH = 200;//max text length for description
  protected $numberOfImages; //number of images contained in this post
  protected $content = []; //overriding instance variable to be an array (of arrays)

  /*
  constructor, used to create a new object or reference a created object
  Post id is used to identify an existing object
  for new creation, content is provided; for old reference, content is null
  each image post can contain multiple images of which each can contain a text description
  so content is an array, each element is an array [file, description]  
  @param content, an array of arrays, [[file1, description1], [file2, description2], ...]
  @param id, post id
  */
  public function __construct(array $content = null, string $id = null) {
    
    parent::__construct($content, $id);

    if ( isset($content) ) { //content provided, so a new post creation

      //check the input content format
      if (!is_array($content)) { //check content is an array

        array_push($this->errorCodes, -1);
        throw new Exception("code bugs: input content should be an array.");

      } elseif ( count($content) != count(array_filter($content, function($el){return count($el)==2;})) ) { //check all elements are 2-element arrays

        array_push($this->errorCodes, -1);
        throw new Exception("code bugs: input content's elements should be 2-element arrays.");

      }

      $this->numberOfImages = count($content);
      
      if ($this->numberOfImages > self::MAX_IMAGES) {
        array_push($this->errorCodes, 4);
        throw new Exception("Each post can contain up to " . self::MAX_IMAGES . "files but " . $this->numberOfImages . " provided");
      } 

      $this->id = $this->user . time(); //concatenate username and obj creation time as post id
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

      //create this post in db
      $this->mysql->request($this->mysql->createImagePostQuery, [":id" => $this->id, ":user" => $this->user]);
      
      $success = []; //to account for upload successes
      //create an entry for each image in db, each references the post
      foreach ($this->content as $el) {

        $imageDescription = $el[1];
        $imageFileObj = $el[0];
        $id = $imageFileObj->getId();
        $this->mysql->request($this->mysql->createImagePostContentQuery, [":id" => $id, ":post_id" => $this->id, ":description" => $imageDescription]);

        if ( !$imageFileObj->upload() ) {

          $this->mysql->request($this->mysql->deleteImagePostQuery, [":id" => $id]); //remove the database record just create for this image
          array_push($success, false);

          switch($imageFileObj->getErrors()[0]) {
            //mapping file class error codes to post class error codes
            case 1:
              array_push($this->errorCodes, 2);
              break;
            case 2:
            case 3:
              array_push($this->errorCodes, 3);
              break;

          }

        }

      }

      return !in_array(false, $success);

    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      throw $ex;

    }

  }

  /*
  @Override
  factory method to update parts of an existing post
  it can update image descriptions, delete images, add images
  @param switch, int to switch among update operations, 1 for description, 2 for img delete, 3 for img add
  @param data, array containing data to accompany the update, [[img index, new description],...] for 1, [img index, ...] for 2, [[file, description],...] for 3 
  @return success, true if all elements succeed, false if not all succeed
  */
  public function update(int $switch = 0, array $data = null): bool {

    $success = [];

    switch ($switch) {

      case 1:
        foreach ($data as $el) {
          array_push($success, $this->updateImageDescription($el[0], $el[1]) );
        }
        break;

      case 2:
        foreach ($data as $el) {
          array_push($success, $this->removeImage($el) );
        }
        break;

      case 3:
        foreach ($data as $el) {
          array_push($success, $this->addImage($el[0], $el[1]) );
        }
        break;

      default:
        throw new Exception("unknown function parameter provided.");

    }

    return !in_array(false, $success);

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
      return false;

    } elseif ($this->numberOfImages > self::MAX_IMAGES) { 

      array_push($this->errorCodes, 4);
      return false;

    } else {      

      array_push($this->content, [$imageFileObj, $description]); //add to object
      $this->numberOfImages += 1;

    }

    //add to database
    try {
      
      $this->mysql->request($this->mysql->createImagePostContentQuery, [":id" => $image_id_available, ":post_id" => $this->id, ":description" => $description]);
      
    } catch (Exception $ex) {

      array_push($this->errorCodes, -1);
      return false;

    }

    if ( $imageFileObj->upload() ) { //file check and upload succeeded

      return true;

    } else { //unload failed

      $this->mysql->request($this->mysql->deleteImagePostQuery, [":id" => $image_id_available]); //remove the record just created for this image
      switch($imageFileObj->getErrors()[0]) { //mapping file class error codes to post class error codes
        
        case 1:
          array_push($this->errorCodes, 2);
          break;
        case 2:
        case 3:
          array_push($this->errorCodes, 3);
          break;

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

      $this->mysql->request( $this->mysql->deletePostQuery, [":id" => $this->id] );
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