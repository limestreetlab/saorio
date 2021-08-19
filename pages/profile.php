<main class="container">

<?php

if (!$isLoggedIn) {
  header( "Location: " . SITE_ROOT ) ;
  exit();
}

require_once INCLUDE_DIR . "queries.php";


//variables declarations
$updateStatus = "";
$aboutMinWords = 20;
$imageMIME = ["image/jpeg", "image/png", "image/gif", "image/svg+xml"];
$profilePictureMaxSize = "1500000"; //in bytes, so 1.5mb

$updateProfile = intval($_REQUEST["updateProfile"]); //retrieve profileUpdate value, 0 or 1, indicating if cancel or save profile clicked
if ($updateProfile) { //user clicks save profile button
  
  //retrive all profile variables, set to null if empty fields
  $about = !empty($_REQUEST["about"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["about"])), FILTER_SANITIZE_STRING) : null; //if not empty, sanitize and replace whitespaces
  $gender = isset($_REQUEST["gender"]) ? filter_var(trim($_REQUEST["gender"]), FILTER_SANITIZE_STRING) : null;
  $ageGroup = isset($_REQUEST["age"]) ? filter_var(trim($_REQUEST["age"]), FILTER_SANITIZE_STRING) : null;
  $location = !empty($_REQUEST["location"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["location"])), FILTER_SANITIZE_STRING) : null;
  $job = !empty($_REQUEST["job"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["job"])), FILTER_SANITIZE_STRING) : null;
  $company = !empty($_REQUEST["company"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["company"])), FILTER_SANITIZE_STRING) : null;
  $school = !empty($_REQUEST["school"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["school"])), FILTER_SANITIZE_STRING) : null;
  $major = !empty($_REQUEST["major"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["major"])), FILTER_SANITIZE_STRING) : null;
  $interests = !empty($_REQUEST["interests"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["interests"])), FILTER_SANITIZE_STRING) : null;
  $quote = !empty($_REQUEST["quote"]) ? preg_replace('/\s\s+/', ' ', filter_var(trim($_REQUEST["quote"])), FILTER_SANITIZE_STRING) : null;

  //check if all entered information pass validations
  if (isset($about) && strlen($about) < $aboutMinWords) {
    $updateStatus = $updateStatus."<div class='alert alert-warning'>About should have 20 words minimum.</div>";
  } else {
    $params = [":user" => $user, ":about" => $about, ":gender" => $gender, ":ageGroup" => $ageGroup, ":location" => $location, ":job" => $job, ":company" => $company, ":major" => $major, ":school" => $school, ":interests" => $interests, ":quote" => $quote];
    queryDB($profileUpdateQuery, $params);
    $updateStatus = $updateStatus."<div class='alert alert-success'>Your profile is successfully updated.</div> ";
  }
}


//update the profile picture
if ( isset($_FILES["profilePicture"]) && $_FILES["profilePicture"]["error"] == 0) { //if photo is uploaded without error
  
  //getting info of the uploaded file
  $type = $_FILES["profilePicture"]["type"]; //browser provided mime, can be cheated
  $size = $_FILES["profilePicture"]["size"]; //size in bytes
  $extension = strtolower(pathinfo($_FILES["profilePicture"]["name"], PATHINFO_EXTENSION)); //file extension of the uploaded file
  $filename = "$user-profile.$extension"; //create a filename to be used as username-profile.ext
  $tempFilePath = $_FILES["profilePicture"]["tmp_name"]; //the temporary absolute path

  //checking for upload clearance
  if ($size > $profilePictureMaxSize) { //max size exceeded

    $updateStatus = $updateStatus . "<div class='alert alert-warning'>The uploaded file exceeds " . $profilePictureMaxSize/1000000 . " MB.</div>";
  
  } elseif (!in_array($type, $imageMIME) ) { //not an allowed mime
    
    $updateStatus = $updateStatus . "<div class='alert alert-warning'>The uploaded file isn't an allowed format.</div>";
  
  } else { //cleared for upload

    $permFilePath = UPLOAD_DIR . "$filename"; //absolute path to save the uploaded file
    if(move_uploaded_file($tempFilePath, $permFilePath)) {

      $param = [":url" => $permFilePath, ":mime" => $type, ":user" => $user];
      queryDB($pictureUpdateQuery, $param);
      $updateStatus = $updateStatus."<div class='alert alert-success'>Your profile picture is updated.</div>";
    
    } else {
      
      $updateStatus = $updateStatus."<div class='alert alert-warning'>Profile picture could not be uploaded.</div>";
    
    }
  }
} 

//profile display block
echo "<div class='row mb-3'><div class='col-12'><h3>$firstname's Profile</h3></div></div>"; //heading
showProfile($user); //diplaying the user profile info here
echo "<section class='row my-5 justify-content-center'><form class='col-4' method='post' action='index.php?reqPage=profile'><button type='submit' class='btn btn-primary col-12' name='editProfile'>Edit Profile</button></form></section>"; //edit profile button
echo $updateStatus; //place to display profile update message

//if edit profile button is clicked, get vars from DB for use in form and display edit form
if (isset($_REQUEST["editProfile"])) {
  
  //retrieve user's existing profile items from DB and set to variables to be displayed in edit form
  $profile = queryDB($getProfileQuery, [":user" => $user])[0]; //get the profile row from DB
  extract($profile); //extract the variables from profile, as DB column names

  //set boolean for radio and check boxes in form

  //the profile edit form
  $editProfileForm = "
    <form method='post' action='index.php?reqPage=profile' enctype='multipart/form-data'>

      <section class='row mb-3'>
        <label class='form-label'>Profile picture</label>
        <input class='form-control' type='file' name='profilePicture' id='profilePicture' accept='image/jpg, image/png, image/gif, image/jpeg'>
      </section>

      <section class='row mb-3'>
        <label class='col-12 col-form-label'>About</label>
        <div class='col-12'>
          <textarea class='form-control' id='about' name='about' maxlength='1000' rows='4' placeholder='I am a yellow labrador retriever!'>$about</textarea>
        </div>
      </section>

      <section class='row mb-3'>
        <div class='col-6'>
          <label class='col-form-label'>Gender</label><br>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='gender' value='m'>
            <label class='form-check-label'>
              Male
            </label>
          </div>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='gender' value='f'>
            <label class='form-check-label'>
              Female
            </label>
          </div>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='gender' value='o'>
            <label class='form-check-label'>
              Intersex
            </label>
          </div>
        </div>

        <div class='col-6'>
          <label class='col-form-label'>Age</label><br>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='age' value='1'>
            <label class='form-check-label'>
              10-19
            </label>
          </div>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='age' value='2'>
            <label class='form-check-label'>
              20-29
            </label>
          </div>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='age' value='3'>
            <label class='form-check-label'>
              30-39
            </label>
          </div>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='age' value='4'>
            <label class='form-check-label'>
              40-49
            </label>
          </div>
          <div class='form-check-inline'>
            <input class='form-check-input' type='radio' name='age' value='5'>
            <label class='form-check-label'>
              50+
            </label>
          </div>
        </div>
      </section>

      <section class='row mb-3'>
        <div class='col-6'>
          <div class='col-12'>
          <label class='col-form-label'>Location</label>
          <input type='text' class='form-control' name='location' placeholder='Japan' value='$location'>
          </div>

          <div class='col-12'>
          <label class='col-form-label'>Work</label>
          <div class='input-group'>
            <input type='text' class='form-control' name='job' placeholder='Accountant' value='$job'>
            <span class='input-group-text'>at</span>
            <input type='text' class='form-control' name='company' placeholder='Walmart' value='$company'>
          </div>
          </div>

          <div class='col-12'>
            <label class='col-form-label'>School</label>
            <div class='input-group'>
            <input type='text' class='form-control' name='major' placeholder='Engineering' value='$major'>
            <span class='input-group-text'>at</span>
            <input type='text' class='form-control' name='school' placeholder='University of Michigan' value='$school'>
            </div>
          </div>
        </div>

        <div class='col-6'>
          <div class='col-12'>
            <label class='col-form-label'>Interests & Hobbies</label>
            <input type='text' class='form-control' name='interests' placeholder='Tennis, karaoke' value='$interests'>
          </div>
          <label class='col-form-label'>Favorite quote</label>
          <textarea class='form-control' name='quote' rows='4' placeholder='The most beautiful experience we can have is the mysterious. -Albert Einstein'>$quote</textarea>
        </div>
      </section>
      
      <!--save or cancel buttons, sends either 1 or 0 in profileUpdate, used to signal update or not-->
      <section class='row justify-content-center mb-3'>
          <button type='submit' class='btn btn-primary col-2 mx-2' name='updateProfile' value='0'>Cancel</button>
          <button type='submit' class='btn btn-primary col-2 mx-2' name='updateProfile' value='1'>Save</button>
      </section>

      <section class='row justify-content-center mb-3'>
        <div class='col-12'><?php echo '$updateStatus' ?></div>
      </section>

    </form>
    ";
  
  //find and replace some radio button items to reflect existing data
  $editProfileForm = str_replace("name='gender' value='$gender'", "name='gender' value='$gender' checked", $editProfileForm); //add checked to gender radio group
  $editProfileForm = str_replace("name='age' value='$ageGroup'", "name='age' value='$ageGroup' checked", $editProfileForm); //add checked to ageGroup radio group
  
  //display the edit form
  echo $editProfileForm;

}


?>

</main> <!--closing container-->