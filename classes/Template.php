<?php
/*
Class to bind PHP variables to placeholders in a given HTML template.
Placeholder variables are defined as {{var}}.
Conditional html can be included using {{If <condition>}} html...{{ENDIF}}, <condition> must be PHP-valid and no-nesting allowed
Iterative html can be included using {{FOREACH <array1> AS <var1> <array2> AS <var2>...}} html...{{ENDFOREACH}}
Any HTML codes passed will be treated as strings
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
    require $path; //import, not _once, because the same file $path might be imported repeatedly in a loop
    $this->view = ob_get_contents(); 
    ob_end_clean();

    return $this;

  } //function load end

  /*
  function to bind variables to loaded template; no need to call this method if template if variable-free
  @param $data array containing var-name => var-value, where var-name matches {{var-name}} in template
  nested array [var-name => [var-values] ] possible, where values are used in a for-loop
  */ 
  public function bind(array $data): self {
    
    $this->data = $data;

    //check if input data is associative array
    if (count(array_filter(array_keys($this->data), "is_string")) == 0) { 

      unset($this->data);
      throw new Exception("Data array in a Template object should be associative.");

    }        

    //swap data {{var-name => var-value}} with placeholders {{var-name}} in template
    foreach ($this->data as $key => $value) {

      //if value is array, list all its elements, separated by ','
      if (is_array($value)) {
        
        $value = implode(",", $value);

      } 

      $varPattern = '/{{\s*' . $key .'\s*}}/si' ; //patterns having {{ }}, with $key and potential whitespaces in between
      $this->view = preg_replace($varPattern, $value, $this->view); //replace placeholder pattern with data value

    }

    return $this;

  } //function bind end

  /*
  function to display (print out) the loaded and data-bound (if any data) template
  */
  public function render(): void {

    $this->parseForeach(); //parse Foreach...Endforeach blocks

    $this->parseIf(); //parse If...Endif conditional blocks
   
    print $this->view; //output the current template

  } //function render end

  /*
  equivalent to render() but instead of rendering the view, this returns the view to caller
  */
  public function getView(): string {

    $this->parseForeach(); //parse Foreach...Endforeach blocks

    $this->parseIf(); //parse If...Endif conditional blocks
   
    return $this->view; //return the current template

  }

  /*
  helper function to parse IF <con> ...ENDIF conditional html codes
  */
  protected function parseIf(): void {

    //{{IF <con>}}<conditional html>{{ENDIF}}, with i and s modifiers for multiline if-blocks. capturing all codes not having { or } between {{IF<con>}} and {{ENDIF}} as conditional codes
    $conditionalPattern = '/{{\s*IF(.*?)}}([^}{]*?){{\s*ENDIF}}/si' ; 
    preg_match_all($conditionalPattern, $this->view, $matches); //assign any pattern matches in template to an array var
    $conditionalBlocks = $matches[0]; //retain only full pattern matches

    //for each of the {{IF <con>}}...{{ENDIF}} block
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

  } //function If end

  /*
  helper function to parse FOREACH <array1> AS <var1> <array2> AS <var2> ... ENDFOREACH iterative html codes
  */
  protected function parseForeach(): void {

    //{{FOREACH <array> AS <var>}}<iterative html>{{ENDFOREACH}}, with i and s modifiers for multiline blocks. capturing codes between {{FOREACH}} and {{ENDFOREACH}} as iterative codes
    $iterativePattern = '/{{\s*FOREACH(.*?)}}(.*?){{\s*ENDFOREACH}}/si' ; 
    preg_match_all($iterativePattern, $this->view, $matches); //assign any pattern matches in template to an array var
    $iterativeBlocks = $matches[0]; //retain only full pattern matches

    //for each of the FOREACH ...ENDFOREACH block
    foreach ($iterativeBlocks as $iterativeBlock) {

      //parse the block for different elements
      preg_match($iterativePattern, $iterativeBlock, $match); //assign captured patterns to an array
      $iterativeExpression = trim($match[1]); //whatever inside {{FOREACH}} after FOREACH, so var1,var2,... AS varname
      $iterativeContents = trim($match[2]); //contents between {{FOREACH...}}...{{ENDFOREACH}}

      $loopExpressionPattern = '/(.*?)\s*AS\s*?(\w+)\s*/s'; //used to capture the varibles before AS, and variable name after AS
      preg_match_all($loopExpressionPattern, $iterativeExpression, $matches); //capturing variables and names into $matches
      $variablesAsStrings = $matches[1]; //array of strings, each string element being elements of an array parameter values separated by comma like "x1, x2, x3, ...", "y1, y2, y3, ...", ...
      $variables = array_map(function (string $s): array {return explode(",", $s);}, $variablesAsStrings); //go from array of strings to array of arrays, each parameter value as array array element
      $keys = $matches[2]; //array of variable names after AS
      /** @var array $variables */
      $numberOfVariables = !empty($variables[0][0]) ? count($variables) : 0; //number of variable arrays for this for-loop, 0 in case variable is null or empty string
      $numberOfIteration = !empty($variables[0][0]) ? count($variables[0]) : 0; //number of variables inside the first variable array, 0 in case variable is null or empty string
      $loopContents = ""; //to accumulate contents for each loop iteration

      //ensure the numbers of iteration of each variable are equal 
      for ($i = 1; $i < $numberOfVariables; $i++) {
        if (count($variables[$i]) != $numberOfIteration) {
          throw new Exception("numbers of variables to iterate through in a foreach loop must be equal.");
        }
      }

      //looping through each iteration (outer loop) and substituting variables in each iteration (inner loop)
      for ($i = 0; $i < $numberOfIteration; $i++) {

        $varReplacedContents = $iterativeContents; //initialized to raw contents with placeholders

        for ($j = 0; $j < $numberOfVariables; $j++) { //for each iteration, replace placeholders with variables of this iteration

          $varPattern = '/{{\s*' . $keys[$j] .'\s*}}/si' ; 
          $varReplacedContents = preg_replace($varPattern, $variables[$j][$i], $varReplacedContents); //replace placeholder for this loop variable of this iteration

        }

        $loopContents .= $varReplacedContents; //concatenate to accumulate placeholder-replaced contents of each iteration

      }

      $this->view = str_replace($iterativeBlock, $loopContents, $this->view); //replace the entire {{FOREACH...{{ENDFOREACH}} block with the accumulated contents

    } //end foreach

  }

  


} //end class

?>

