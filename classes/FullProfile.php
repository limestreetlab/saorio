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

  /*
  constructor
  @param: username of the Profile's owner
  */
  public function __construct(string $user) {

    parent::__construct($user); //inherit user, firstname, lastname, profilePictureURL, mysql

    try {

      $profileData = $this->mysql->request($this->mysql->readProfileQuery, [":user" => $this->user])[0];
    
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
    $this->email = $this->mysql->request($this->mysql->readEmailQuery, [":user" => $this->user])[0]["email"];

  }

  /*
  getter for all profile data about this user
  @return profile data array
  */
  public function getData(): array {

    $basicData = parent::getData();
    $moreData = ["about" => $this->about, "gender" => $this->gender, "dob" => $this->dob, "age" => $this->age, "city" => $this->city, "country" => $this->country, "job" => $this->job, "company" => $this->company, "school" => $this->school, "major" => $this->major, "interests" => $this->interests, "quote" => $this->quote, "website" => $this->website, "socialmedia" => $this->socialmedia, "email" => $this->email];
    return array_merge($basicData, $moreData);

  }

  /*
  getter for basic profile data about his user
  @return basic profile data array
  */
  public function getBasicData(): array {
    return parent::getData();
  }

  /*
  update the about section of profile
  @param new about data string
  @return success
  */
  public function updateAbout(string $newAbout): bool {
    
    try {
      $newAbout = self::cleanString($newAbout);

      if ($this->about != $newAbout) { //changed, so update
        $this->mysql->request($this->mysql->updateProfileAboutQuery, [":about" => $newAbout, ":user" => $this->user]);
      }

      return true;

    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the gender of profile
  @param new gender data string
  @return success
  */
  public function updateGender(string $newGender): bool {
    
    try {
      $newGender = self::cleanString($newGender);

      if ($this->gender != $newGender) {
        $this->mysql->request($this->mysql->updateProfileGenderQuery, [":gender" => $newGender, ":user" => $this->user]);
      }
      return true;

    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the date of birth of profile, as epoch value
  @param new date of birth data year, month, day integers
  @return success
  */
  public function updateDob(int $year, int $month, int $day): bool {
    
    try {
      if ( !self::checkDob($year, $month, $day) ) {
        throw new Exception("Invalid date of birth");
      }
      $newDob = (new DateTime())->setDate($year, $month, $day); //DateTime obj for inputted dob
      $newDob = $newDob->getTimestamp(); //convert to epoch for inputted dob

      if ($this->dob != $newDob) {
        $this->mysql->request($this->mysql->updateProfileDobQuery, [":dob" => $newDob, ":user" => $this->user]);
      }

      return true;

    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  helper function to validate date of birth year/month/day data
  @param date of birth data year, month, day integers
  @return success
  */
  static protected function checkDob(int $year, int $month, int $day): bool {
    $min_year = 1900;
    $max_year = 2021;
    $min_month = 1;
    $max_month = 12;
    $min_day = 1;
    $max_day = 31;

    $year = filter_var($year, FILTER_VALIDATE_INT, ["options" => ["min_range" => $min_year, "max_range" => $max_year]]);
    $month = filter_var($month, FILTER_VALIDATE_INT, ["options" => ["min_range" => $min_month, "max_range" => $max_month]]);
    $day = filter_var($day, FILTER_VALIDATE_INT, ["options" => ["min_range" => $min_day, "max_range" => $max_day]]);

    return $year != 0 && $month != 0 && $day != 0;

  }

  /*
  update the interests section of profile
  @param new interests data string
  @return success
  */
  public function updateInterests(string $newInterests): bool {
    
    try {
      $newInterests = self::cleanString($newInterests);

      if ($this->interests != $newInterests) {
        $this->mysql->request($this->mysql->updateProfileInterestsQuery, [":interests" => $newInterests, ":user" => $this->user]);
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the quote of profile
  @param new quote data string
  @return success
  */
  public function updateQuote(string $newQuote): bool {
   
    try {
      $newQuote = self::cleanString($newQuote);

      if ($this->quote != $newQuote) {
        $this->mysql->request($this->mysql->updateProfileQuoteQuery, [":quote" => $newQuote, ":user" => $this->user]);
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the city of profile
  @param new city
  @return success
  */
  public function updateCity(string $newCity): bool {
    
    try {
      $newCity = self::cleanString($newCity); //if null will become an empty string
      
      if ($this->city != $newCity) {
        $this->mysql->request($this->mysql->updateProfileCityQuery, [":city" => $newCity, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the country of profile
  @param new country
  @return success
  */
  public function updateCountry(string $newCountry): bool {
    
    try {
      $newCountry = self::cleanString($newCountry); //if null will become an empty string

      if ($this->country != $newCountry) {
        $this->mysql->request($this->mysql->updateProfileCountryQuery, [":country" => $newCountry, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the job of profile
  @param new job
  @return success
  */
  public function updateJob(string $newJob): bool {
    
    try {
      $newJob = self::cleanString($newJob); //if null will become an empty string

      if ($this->job != $newJob) {
        $this->mysql->request($this->mysql->updateProfileJobQuery, [":job" => $newJob, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the company of profile
  @param new company
  @return success
  */
  public function updateCompany(string $newCompany): bool {
    
    try {
      $newCompany = self::cleanString($newCompany); //if null will become an empty string

      if ($this->company != $newCompany) {
        $this->mysql->request($this->mysql->updateProfileCompanyQuery, [":company" => $newCompany, ":user" => $this->user]); 
      }
        return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the major of profile
  @param new major
  @return success
  */
  public function updateMajor(string $newMajor): bool {
    
    try {
      $newMajor = self::cleanString($newMajor); //if null will become an empty string

      if ($this->major != $newMajor) {
        $this->mysql->request($this->mysql->updateProfileMajorQuery, [":major" => $newMajor, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the school of profile
  @param new school
  @return success
  */
  public function updateSchool(string $newSchool): bool {
    
    try {
      $newSchool = self::cleanString($newSchool); //if null will become an empty string

      if ($this->school != $newSchool) {
        $this->mysql->request($this->mysql->updateProfileSchoolQuery, [":school" => $newSchool, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the email of profile
  @param new email
  @return success
  */
  public function updateEmail(string $newEmail): bool {
    
    try {
      $newEmail = self::cleanString($newEmail); //if null will become an empty string

      if ($this->email != $newEmail) {
        $this->mysql->request($this->mysql->updateMembersEmailQuery, [":email" => $newEmail, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the website of profile
  @param new website
  @return success
  */
  public function updateWebsite(string $newWebsite): bool {
    
    try {
      $newWebsite = self::cleanString($newWebsite); //if null will become an empty string

      if ($this->website != $newWebsite) {
        $this->mysql->request($this->mysql->updateProfileWebsiteQuery, [":website" => $newWebsite, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  update the socialmedia of profile
  @param new socialmedia
  @return success
  */
  public function updateSocialMedia(string $newSocialMedia): bool {
    
    try {
      $newSocialMedia = self::cleanString($newSocialMedia); //if null will become an empty string

      if ($this->socialmedia != $newSocialMedia) {
        $this->mysql->request($this->mysql->updateProfileSocialMediaQuery, [":socialmedia" => $newSocialMedia, ":user" => $this->user]); 
      }
      return true;
    } catch (Exception $ex) {
      return false;
    }

  }

  /*
  helper function to calculate age of user, using his date of birth
  @return age integer or null
  */
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