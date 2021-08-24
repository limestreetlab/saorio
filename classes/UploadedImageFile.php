<?php
//class for uploading photos

require_once INCLUDE_DIR . "queries.php";

class UploadedImageFile {

  protected static $maxUploadSize = "1500000";
  protected static $imageMIME = ["image/jpeg", "image/png", "image/gif", "image/svg+xml"];
  protected $messages = [];
  protected $mime;
  protected $fileExtension;
  protected $fileSize;
  protected $tempFilePath;
  protected $permFilePath;
  protected $filename;

  //constructor
  public function __construct($uploadedFile) {
    
    $this->mime = $uploadedFile["type"];
    $this->fileExtension = strtolower(pathinfo($uploadedFile["name"], PATHINFO_EXTENSION));
    $this->fileSize = $uploadedFile["size"];
    $this->tempFilePath = $uploadedFile["tmp_name"];
    $this->filename = $this->user . "-profile" . "." . $this->fileExtension;
    $this->permFilePath = UPLOAD_DIR . $this->filename;

  }

  //main function to perform file checking, moving from temp to permanent destination (full abs path with file name and ext), and DB persistence 
  public function upload(string $destination): bool {

    $isChecked = $this->check();

    if ($isChecked) {

      $isMoved = $this->move();

      if ($isMoved) {

        $isPersisted = $this->persist();

        if ($isPersisted) {

          $msg = "<div class='alert alert-success'>Your profile picture is successfully updated.</div>";
          array_push($this->messages, $msg);
          return true;

        }

      }
    
    }

  }

  protected function check(): bool {

    $isSizeChecked = $this->fileSize <= self::$maxUploadSize ? true : false;
    if (!$isSizeChecked) {
      $msg = "<div class='alert alert-warning'>The uploaded file exceeds " . self::$maxUploadSize/1000000 . " MB.</div>";
      array_push($this->messages, $msg);
    }

    $isTypeChecked = in_array($this->mime, self::$imageMIME) ? true : false;
    if (!$isTypeChecked) {
      $msg = "<div class='alert alert-warning'>The uploaded file isn't an allowed format.</div>";
      array_push($this->messages, $msg);
    }

    if ($isSizeChecked && $isTypeChecked) {
      return true;
    } else {
      return false;
    }

  }

  protected function move(): bool {

    $success = move_uploaded_file($this->tempFilePath, $this->permFilePath);

    if (!$success) {
      $msg = "<div class='alert alert-warning'>Profile picture upload failed due to our internal file system error. Sorry for the inconvenience.</div>";
      array_push($this->messages, $msg);
    }

    return $success;

  }

  protected function persist(): bool {

    $params = [":url" => $this->permFilePath, ":mime" => $this->mime, ":user" => $this->user];
    global $pictureUpdateQuery;

    try {
      queryDB($pictureUpdateQuery, $params);
      $success = true;
    } catch (Exception $ex) {
      $success = false;
    }

    if (!$success) {
      $msg = "<div class='alert alert-warning'>Profile picture upload failed due to our database system error. Sorry for the inconvenience.</div>";
      array_push($this->messages, $msg);
    }

    return $success;

  }

  public function getMessages(): array {

    return $this->messages;

  }


}




?>