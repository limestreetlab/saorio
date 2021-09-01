<?php
/*
Class to bind PHP variables to placeholders in a given HTML template.
Placeholder variables are defined as {{var}}.
Conditional html can be included using {{If <condition>}} html...{{ENDIF}}, <condition> must be PHP-valid and no-nesting allowed
*/
class Template {

  const DIR = TEMPLATE_DIR; //project directory for template files
  protected $view; //template filename with extension
  protected $data = []; //data to bind to template

  /*
  constructor, empty
  */
  public function __construct() {
  }

  /*
  function to assign a given HTML template file to $view
  @param $template filename of the template to use, with extension 
  */
  public function load(string $view): self {

    $path = self::DIR . $view; //fullpath to the template file
    
    if (!file_exists($path)) { //file does not exist
      throw new Exception("Cannot load template file " . $path);
    } 

    ob_start();
    include $path; //include, not _once, because the same file $path might be included repeatedly in a loop
    $this->view = ob_get_contents(); 
    ob_end_clean();

    return $this;

  } //function end

  /*
  function to bind variables to loaded template; no need to call this method if template if variable-free
  @param $data array containing var-name => var-value, where var-name matches {{var-name}} in template
  */ 
  public function bind(array $data): self {
    
    $this->data = $data;

    //check if input data is associative array
    if (count(array_filter(array_keys($this->data), "is_string")) == 0) { 
      unset($this->data);
      throw new Exception("Data array in a Template object should be associative.");
    }        

    foreach ($this->data as $key => $value) {

      $varPattern = '/{{\s*' . $key .'\s*}}/i' ; //patterns having {{ }}, with $key and potential whitespaces in between
      $this->view = preg_replace($varPattern, $value, $this->view);

    }

    return $this;

  } //function end

  /*
  function to output the loaded and data-bound (if any data) template
  */
  public function render(): void {

    $this->parseIf(); //parse If..Endif conditional blocks
   
    print $this->view; //output the current template

  } //function end

  /*
  helper function to parse IF <con> ...ENDIF conditional html codes
  */
  protected function parseIf(): void {

    //{{IF <con>}}<conditional html>{{ENDIF}}, with i and s modifiers for multiline if-blocks. capturing all codes not having {, } between {{IF<con>}} and {{ENDIF}} as conditional codes
    $conditionalPattern = '/{{\s*IF(.*?)}}([^}{]*?){{\s*ENDIF}}/si' ; 
    preg_match_all($conditionalPattern, $this->view, $matches); //assign any matches in template to an array var
    $conditionalBlocks = $matches[0]; //retain only full pattern matches

    //for each {{IF <con>}}...{{ENDIF}} block
    foreach ($conditionalBlocks as $conditionalBlock) {
      
      preg_match($conditionalPattern, $conditionalBlock, $match); //assign captured patterns to an array
      $condition = trim($match[1]); //assign the 1st captured contents (whatever after IF and until }}) as condition, in string
      $conditionalContents = trim($match[2]); //assign the 2nd captured contents (whatever between }} and {{ENDIF}}) as conditional html
      $conditionBool = @eval("return $condition ;"); //evaluate the condition string and store its resulting truth/false 

      if ($conditionBool) { //condition met, apply its conditional html

        $this->view = str_replace($conditionalBlock, $conditionalContents, $this->view);

      } else { //condition not met, purge this {{IF <con>}}...{{ENDIF}} block

        $this->view = str_replace($conditionalBlock, "", $this->view);

      }

    }

  } //function end

  


} //end class

?>