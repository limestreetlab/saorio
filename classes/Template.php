<?php

class Template {

  protected $template; //tempalte file name with extension
  protected $data = []; //data to bind

  //constructor
  public function __construct(string $template, array $data = null) {
    $this->template = TEMPLATE_DIR . $template; //concatenate with templates folder path
    $this->data = $data;
  }

  //function to load the template and fill in the data
  public function render(): string {

    if (!file_exists($this->template)) {

      throw new Exception("Cannot locate the provided template file " . $this->template );
    
    } elseif ($this->data != null && count(array_filter(array_keys($this->data), "is_string")) == 0) { //if the data array is not an associative array
      
      throw new Exception("Data array in a Template object should be associative.");
      
    } else {

      ob_start();
      include $this->template;
      $contents = ob_get_clean();  

      if (!is_null($this->data)) { //when data isn't null

        foreach ($this->data as $key => $value) {

          $tagToReplace = array('{{'.$key.'}}', '{{ '.$key.' }}', '{{ '.$key.'}}', '{{'.$key.' }}'); //space variations of {{variable}}
          $contents = str_replace($tagToReplace, $value, $contents);

        }

      }

      return $contents;

    }

  }

}

?>