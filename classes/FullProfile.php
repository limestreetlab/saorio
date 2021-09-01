<?php


//full profile class
class FullProfile extends BasicProfile {

  public $about;
  public $gender;
  public $dob;
  public $age;
  public $location;
  public $job;
  public $company;
  public $school;
  public $major;
  public $interests;
  public $quote;
  public $website;
  public $email;

  //constructor
  public function __construct(string $user) {

    parent::__construct($user); //inherit user, firstname, lastname, profilePictureURL

    global $getProfileQuery, $getEmailQuery;

    try {
      $profileData = queryDB($getProfileQuery, [":user" => $this->user])[0];
    } catch (Exception $ex) {
      error_log("Cannot retrieve profile data: " . $ex);
      throw $ex;
    }

    $this->about = $profileData["about"];
    $this->gender = $profileData["gender"];
    $this->dob = $profileData["dob"]; //epoch timestamp
    $this->age = $this->calculateAge(); //calculate age from dob
    $this->city = $profileData["city"]; 
    $this->country = $profileData["country"];
    $this->job = $profileData["job"]; 
    $this->company = $profileData["company"];
    $this->school = $profileData["school"];
    $this->major = $profileData["major"];
    $this->interests = $profileData["interests"];
    $this->quote = $profileData["quote"];
    $this->website = $profileData["website"];    
    $this->socialmedia = $profileData["socialmedia"];  
    $this->email = queryDB($getEmailQuery, [":user" => $this->user])[0]["email"];

  }

  //getter for all profile data about this user
  public function getData(): array {

    $basicData = parent::getData();
    $moreData = ["about" => $this->about, "gender" => $this->gender, "dob" => $this->dob, "age" => $this->age, "city" => $this->city, "country" => $this->country, "job" => $this->job, "company" => $this->company, "school" => $this->school, "major" => $this->major, "interests" => $this->interests, "quote" => $this->quote, "website" => $this->website, "socialmedia" => $this->socialmedia, "email" => $this->email];
    return array_merge($basicData, $moreData);

  }

  //getter for basic data
  public function getBasicData(): array {
    return parent::getData();
  }

  //function to update bio
  public function updateAbout(string $newAbout): bool {

    global $updateProfileAboutQuery;
    
    try {
      $newAbout = self::cleanString($newAbout);
      queryDB($updateProfileAboutQuery, [":about" => $newAbout, ":user" => $this->user]);
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to date gender
  public function updateGender(string $newGender): bool {

    global $updateProfileGenderQuery;
    
    try {
      $newGender = self::cleanString($newGender);
      queryDB($updateProfileGenderQuery, [":gender" => $newGender, ":user" => $this->user]);
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to update date of birth
  public function updateDob(int $year, int $month, int $day): bool {

    global $updateProfileDobQuery;
    
    try {
      $newDob = (new DateTime())->setDate($year, $month, $day); //DateTime obj for inputted dob
      $newDob = $newDob->getTimestamp(); //convert to epoch for inputted dob
      $newDob = filter_var($newDob, FILTER_VALIDATE_INT); //validate integer
      queryDB($updateProfileDobQuery, [":dob" => $newDob, ":user" => $this->user]);
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to update interests
  public function updateInterests(string $newInterests): bool {

    global $updateProfileInterestsQuery;
    
    try {
      $newInterests = self::cleanString($newInterests);
      queryDB($updateProfileInterestsQuery, [":interests" => $newInterests, ":user" => $this->user]);
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to update favorite quote
  public function updateQuote(string $newQuote): bool {

    global $updateProfileQuoteQuery;
    
    try {
      $newQuote = self::cleanString($newQuote);
      queryDB($updateProfileQuoteQuery, [":quote" => $newQuote, ":user" => $this->user]);
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to update location which is an 2-size arry of city and country
  public function updateLocation(array $newLocation): bool {

    global $updateProfileLocationQuery;
    
    try {
      $newCity = self::cleanString($newLocation["city"]); //if null will become an empty string
      $newCountry = self::cleanString($newLocation["country"]); //if null will become an empty string

      if (strlen($newCity) == 0 && strlen($newCountry) > 0) { //only country is updated
        queryDB($updateProfileLocationQuery, [":city" => $this->city, ":country" => $newCountry, ":user" => $this->user]);
      } elseif (strlen($newCity) > 0 && strlen($newCountry) == 0) { //only city is updated
        queryDB($updateProfileLocationQuery, [":city" => $newCity, ":country" => $this->country, ":user" => $this->user]);
      } else { //both city and country updated
        queryDB($updateProfileLocationQuery, [":city" => $newCity, ":country" => $newCountry, ":user" => $this->user]);
      }

      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to update work which is an 2-size arry of job/title and company
  public function updateWork(array $newWork): bool {

    global $updateProfileWorkQuery;
    
    try {
      $newJob = self::cleanString($newWork["job"]); //if null will become an empty string
      $newCompany = self::cleanString($newWork["company"]); //if null will become an empty string

      if (strlen($newJob) == 0 && strlen($newCompany) > 0) { //only company is updated
        queryDB($updateProfileWorkQuery, [":job" => $this->job, ":company" => $newCompany, ":user" => $this->user]);
      } elseif (strlen($newJob) > 0 && strlen($newCompany) == 0) { //only job is updated
        queryDB($updateProfileWorkQuery, [":job" => $newJob, ":company" => $this->company, ":user" => $this->user]);
      } else { //both job and company updated
        queryDB($updateProfileWorkQuery, [":job" => $newJob, ":company" => $newCompany, ":user" => $this->user]);
      }

      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to update study which is an 2-size arry of school and subject/major
  public function updateStudy(array $newStudy): bool {

    global $updateProfileStudyQuery;
    
    try {
      $newSchool = self::cleanString($newStudy["school"]); //if null will become an empty string
      $newMajor = self::cleanString($newStudy["major"]); //if null will become an empty string

      if (strlen($newSchool) == 0 && strlen($newMajor) > 0) { //only major/subject is updated
        queryDB($updateProfileStudyQuery, [":school" => $this->school, ":major" => $newMajor, ":user" => $this->user]);
      } elseif (strlen($newSchool) > 0 && strlen($newMajor) == 0) { //only school is updated
        queryDB($updateProfileStudyQuery, [":school" => $newSchool, ":major" => $this->major, ":user" => $this->user]);
      } else { //both school and major updated
        queryDB($updateProfileStudyQuery, [":school" => $newSchool, ":major" => $newMajor, ":user" => $this->user]);
      }

      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  //function to update contact info which is an 2-size arry of email and website, email is stored in members
  public function updateContact(array $newContact): bool {

    global $updateMembersEmailQuery, $updateProfileWebsiteQuery;
    
    try {
      $newEmail = self::cleanString($newContact["email"]); //if null will become an empty string
      $newWebsite = self::cleanString($newContact["website"]); //if null will become an empty string

      if (strlen($newEmail) == 0 && strlen($newWebsite) > 0) { //only website is updated
        queryDB($updateProfileWebsiteQuery, [":website" => $newWebsite, ":user" => $this->user]);
      } elseif (strlen($newEmail) > 0 && strlen($newWebsite) == 0) { //only email is updated
        queryDB($updateMembersEmailQuery, [":email" => $newEmail, ":user" => $this->user]);
      } else { //both school and major updated
        queryDB($updateMembersEmailQuery, [":email" => $newEmail, ":user" => $this->user]);
        queryDB($updateProfileWebsiteQuery, [":website" => $newWebsite, ":user" => $this->user]);
      }

      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  protected function calculateAge() {

    if (isset($this->dob)) {

      $nowDateTime = new DateTime(); //DateTime obj for today
      $dobDateTime = (new DateTime())->setTimestamp($this->dob); //from epoch timestamp to DateTime obj
      $ageDateInterval = $dobDateTime->diff($nowDateTime); //DateInterval obj between today and dob
      $age = intval($ageDateInterval->format("%y")); //get the year value of the DateInterval obj and cast to int
    
    } else {
      $age = null;
    }
    
    return $age;    

  }

} //close class

?>