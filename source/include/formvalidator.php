<?PHP
/*
  -------------------------------------------------------------------------
      PHP Form Validator (formvalidator.php)
              Version 1.1
    This program is free software published under the
    terms of the GNU Lesser General Public License.

    This program is distributed in the hope that it will
    be useful - WITHOUT ANY WARRANTY; without even the
    implied warranty of MERCHANTABILITY or FITNESS FOR A
    PARTICULAR PURPOSE.
		
	For updates, please visit:
	http://www.html-form-guide.com/php-form/php-form-validation.html
	
	Questions & comments please send to info@html-form-guide.com
  -------------------------------------------------------------------------  
*/

/**
* Carries information about each of the form validations
*/
class ValidatorObj
{
	var $variable_name;
	var $validator_string;
	var $error_string;
}

/**
* Base class for custom validation objects
**/
class CustomValidator 
{
	function DoValidate(&$formars,&$error_hash)
	{
		return true;
	}
}

/** Default error messages*/
define("E_VAL_REQUIRED_VALUE","Please enter the value for %s");
define("E_VAL_MAXLEN_EXCEEDED","Maximum length exceeded for %s.");
define("E_VAL_MINLEN_CHECK_FAILED","Please enter input with length more than %d for %s");
define("E_VAL_ALNUM_CHECK_FAILED","Please provide an alpha-numeric input for %s");
define("E_VAL_ALNUM_S_CHECK_FAILED","Please provide an alpha-numeric input for %s");
define("E_VAL_NUM_CHECK_FAILED","Please provide numeric input for %s");
define("E_VAL_ALPHA_CHECK_FAILED","Please provide alphabetic input for %s");
define("E_VAL_ALPHA_S_CHECK_FAILED","Please provide alphabetic input for %s");
define("E_VAL_EMAIL_CHECK_FAILED","Please provide a valida email address");
define("E_VAL_LESSTHAN_CHECK_FAILED","Enter a value less than %f for %s");
define("E_VAL_GREATERTHAN_CHECK_FAILED","Enter a value greater than %f for %s");
define("E_VAL_REGEXP_CHECK_FAILED","Please provide a valid input for %s");
define("E_VAL_DONTSEL_CHECK_FAILED","Wrong option selected for %s");
define("E_VAL_SELMIN_CHECK_FAILED","Please select minimum %d options for %s");
define("E_VAL_SELONE_CHECK_FAILED","Please select an option for %s");
define("E_VAL_EQELMNT_CHECK_FAILED","Value of %s should be same as that of %s");
define("E_VAL_NEELMNT_CHECK_FAILED","Value of %s should not be same as that of %s");



/**
* FormValidator: The main class that does all the form validations
**/
class FormValidator 
{
	var $validator_array;
    var $error_hash;
	var $custom_validators;
	
	function FormValidator()
	{
		$this->validator_array = array();
        $this->error_hash = array();
		$this->custom_validators=array();
	}
	
	function AddCustomValidator(&$customv)
	{
		array_push($this->custom_validators,$customv);
	}

	function addValidation($variable,$validator,$error)
	{
		$validator_obj = new ValidatorObj();
		$validator_obj->variable_name = $variable;
		$validator_obj->validator_string = $validator;
		$validator_obj->error_string = $error;
		array_push($this->validator_array,$validator_obj);
	}
    function GetErrors()
    {
        return $this->error_hash;
    }

	function ValidateForm()
	{
		$bret = true;

		$error_string="";
		$error_to_display = "";

        
		if(strcmp($_SERVER['REQUEST_METHOD'],'POST')==0)
		{
			$form_variables = $_POST;
		}
		else
		{
			$form_variables = $_GET;
		}

        $vcount = count($this->validator_array);
        

		foreach($this->validator_array as $val_obj)
		{
			if(!$this->ValidateObject($val_obj,$form_variables,$error_string))
			{
				$bret = false;
                $this->error_hash[$val_obj->variable_name] = $error_string;
			}
		}

		if(true == $bret && count($this->custom_validators) > 0)
		{
            foreach( $this->custom_validators as $custom_val)
			{
				if(false == $custom_val->DoValidate($form_variables,$this->error_hash))
				{
					$bret = false;
				}
			}
		}
		return $bret;
	}


	function ValidateObject($validatorobj,$formvariables,&$error_string)
	{
		$bret = true;

		$splitted = explode("=",$validatorobj->validator_string);
		$command = $splitted[0];
		$command_value = '';

		if(isset($splitted[1]) && strlen($splitted[1])>0)
		{
			$command_value = $splitted[1];
		}

		$default_error_message="";
		
		$input_value ="";

		if(isset($formvariables[$validatorobj->variable_name]))
		{
		 $input_value = $formvariables[$validatorobj->variable_name];
		}

		$bret = $this->ValidateCommand($command,$command_value,$input_value,
									$default_error_message,
									$validatorobj->variable_name,
									$formvariables);

		
		if(false == $bret)
		{
			if(isset($validatorobj->error_string) &&
				strlen($validatorobj->error_string)>0)
			{
				$error_string = $validatorobj->error_string;
			}
			else
			{
				$error_string = $default_error_message;
			}

		}//if
		return $bret;
	}
    	
	function validate_req($input_value, &$default_error_message,$variable_name)
	{
	  $bret = true;
      	if(!isset($input_value) ||
			strlen($input_value) <=0)
		{
			$bret=false;
			$default_error_message = sprintf(E_VAL_REQUIRED_VALUE,$variable_name);
		}	
	  return $bret;	
	}

	function validate_maxlen($input_value,$max_len,$variable_name,&$default_error_message)
	{
		$bret = true;
		if(isset($input_value) )
		{
			$input_length = strlen($input_value);
			if($input_length > $max_len)
			{
				$bret=false;
				$default_error_message = sprintf(E_VAL_MAXLEN_EXCEEDED,$variable_name);
			}
		}
		return $bret;
	}

	function validate_minlen($input_value,$min_len,$variable_name,&$default_error_message)
	{
		$bret = true;
		if(isset($input_value) )
		{
			$input_length = strlen($input_value);
			if($input_length < $min_len)
			{
				$bret=false;
				$default_error_message = sprintf(E_VAL_MINLEN_CHECK_FAILED,$min_len,$variable_name);
			}
		}
		return $bret;
	}

	function test_datatype($input_value,$reg_exp)
	{
		if(ereg($reg_exp,$input_value))
		{
			return false;
		}
		return true;
	}

	function validate_email($email) 
	{
		return preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $email);
	}

	function validate_for_numeric_input($input_value,&$validation_success)
	{
		
		$more_validations=true;
		$validation_success = true;
		if(strlen($input_value)>0)
		{
			
			if(false == is_numeric($input_value))
			{
				$validation_success = false;
				$more_validations=false;
			}
		}
		else
		{
			$more_validations=false;
		}
		return $more_validations;
	}

	function validate_lessthan($command_value,$input_value,
                $variable_name,&$default_error_message)
	{
		$bret = true;
		if(false == $this->validate_for_numeric_input($input_value,
                                    $bret))
		{
			return $bret;
		}
		if($bret)
		{
			$lessthan = doubleval($command_value);
			$float_inputval = doubleval($input_value);
			if($float_inputval >= $lessthan)
			{
				$default_error_message = sprintf(E_VAL_LESSTHAN_CHECK_FAILED,
										$lessthan,
										$variable_name);
				$bret = false;
			}//if
		}
		return $bret ;
	}

	function validate_greaterthan($command_value,$input_value,$variable_name,&$default_error_message)
	{
		$bret = true;
		if(false == $this->validate_for_numeric_input($input_value,$bret))
		{
			return $bret;
		}
		if($bret)
		{
			$greaterthan = doubleval($command_value);
			$float_inputval = doubleval($input_value);
			if($float_inputval <= $greaterthan)
			{
				$default_error_message = sprintf(E_VAL_GREATERTHAN_CHECK_FAILED,
										$greaterthan,
										$variable_name);
				$bret = false;
			}//if
		}
		return $bret ;
	}

    function validate_select($input_value,$command_value,&$default_error_message,$variable_name)
    {
	    $bret=false;
		if(is_array($input_value))
		{
			foreach($input_value as $value)
			{
				if($value == $command_value)
				{
					$bret=true;
					break;
				}
			}
		}
		else
		{
			if($command_value == $input_value)
			{
				$bret=true;
			}
		}
        if(false == $bret)
        {
            $default_error_message = sprintf(E_VAL_SHOULD_SEL_CHECK_FAILED,
                                            $command_value,$variable_name);
        }
	    return $bret;
    }

	function validate_dontselect($input_value,$command_value,&$default_error_message,$variable_name)
	{
	   $bret=true;
		if(is_array($input_value))
		{
			foreach($input_value as $value)
			{
				if($value == $command_value)
				{
					$bret=false;
					$default_error_message = sprintf(E_VAL_DONTSEL_CHECK_FAILED,$variable_name);
					break;
				}
			}
		}
		else
		{
			if($command_value == $input_value)
			{
				$bret=false;
				$default_error_message = sprintf(E_VAL_DONTSEL_CHECK_FAILED,$variable_name);
			}
		}
	  return $bret;
	}



	function ValidateCommand($command,$command_value,$input_value,&$default_error_message,$variable_name,$formvariables)
	{
		$bret=true;
		switch($command)
		{
			case 'req':
						{
							$bret = $this->validate_req($input_value, $default_error_message,$variable_name);
							break;
						}

			case 'maxlen':
						{
							$max_len = intval($command_value);
							$bret = $this->validate_maxlen($input_value,$max_len,$variable_name,
												$default_error_message);
							break;
						}

			case 'minlen':
						{
							$min_len = intval($command_value);
							$bret = $this->validate_minlen($input_value,$min_len,$variable_name,
											$default_error_message);
							break;
						}

			case 'alnum':
						{
							$bret= $this->test_datatype($input_value,"[^A-Za-z0-9]");
							if(false == $bret)
							{
								$default_error_message = sprintf(E_VAL_ALNUM_CHECK_FAILED,$variable_name);
							}
							break;
						}

			case 'alnum_s':
						{
							$bret= $this->test_datatype($input_value,"[^A-Za-z0-9 ]");
							if(false == $bret)
							{
								$default_error_message = sprintf(E_VAL_ALNUM_S_CHECK_FAILED,$variable_name);
							}
							break;
						}

			case 'num':
            case 'numeric':
						{
							$bret= $this->test_datatype($input_value,"[^0-9]");
							if(false == $bret)
							{
								$default_error_message = sprintf(E_VAL_NUM_CHECK_FAILED,$variable_name);
							}
							break;							
						}

			case 'alpha':
						{
							$bret= $this->test_datatype($input_value,"[^A-Za-z]");
							if(false == $bret)
							{
								$default_error_message = sprintf(E_VAL_ALPHA_CHECK_FAILED,$variable_name);
							}
							break;
						}
			case 'alpha_s':
						{
							$bret= $this->test_datatype($input_value,"[^A-Za-z ]");
							if(false == $bret)
							{
								$default_error_message = sprintf(E_VAL_ALPHA_S_CHECK_FAILED,$variable_name);
							}
							break;
						}
			case 'email':
						{
							if(isset($input_value) && strlen($input_value)>0)
							{
								$bret= $this->validate_email($input_value);
								if(false == $bret)
								{
									$default_error_message = E_VAL_EMAIL_CHECK_FAILED;
								}
							}
							break;
						}
			case "lt": 
			case "lessthan": 
						{
							$bret = $this->validate_lessthan($command_value,
													$input_value,
													$variable_name,
													$default_error_message);
							break;
						}
			case "gt": 
			case "greaterthan": 
						{
							$bret = $this->validate_greaterthan($command_value,
													$input_value,
													$variable_name,
													$default_error_message);
							break;
						}

			case "regexp":
						{
							if(isset($input_value) && strlen($input_value)>0)
							{
								if(!preg_match("$command_value",$input_value))
								{
									$bret=false;
									$default_error_message = sprintf(E_VAL_REGEXP_CHECK_FAILED,$variable_name);
								}
							}
							break;
						}
		  case "dontselect": 
		  case "dontselectchk":
          case "dontselectradio":
						{
							$bret = $this->validate_dontselect($input_value,
															   $command_value,
															   $default_error_message,
																$variable_name);
							 break;
						}//case

          case "shouldselchk":
          case "selectradio":
                      {
                            $bret = $this->validate_select($input_value,
							       $command_value,
							       $default_error_message,
								    $variable_name);
                            break;
                      }//case
		  case "selmin":
						{
							$min_count = intval($command_value);

							if(isset($input_value))
                            {
							    if($min_count > 1)
							    {
							        $bret = (count($input_value) >= $min_count )?true:false;
							    }
                                else
                                {
                                  $bret = true;
                                }
                            }
							else
							{
								$bret= false;
								$default_error_message = sprintf(E_VAL_SELMIN_CHECK_FAILED,$min_count,$variable_name);
							}

							break;
						}//case
		 case "selone":
						{
							if(false == isset($input_value)||
								strlen($input_value)<=0)
							{
								$bret= false;
								$default_error_message = sprintf(E_VAL_SELONE_CHECK_FAILED,$variable_name);
							}
							break;
						}
		 case "eqelmnt":
						{

							if(isset($formvariables[$command_value]) &&
							   strcmp($input_value,$formvariables[$command_value])==0 )
							{
								$bret=true;
							}
							else
							{
								$bret= false;
								$default_error_message = sprintf(E_VAL_EQELMNT_CHECK_FAILED,$variable_name,$command_value);
							}
						break;
						}
		  case "neelmnt":
						{
							if(isset($formvariables[$command_value]) &&
							   strcmp($input_value,$formvariables[$command_value]) !=0 )
							{
								$bret=true;
							}
							else
							{
								$bret= false;
								$default_error_message = sprintf(E_VAL_NEELMNT_CHECK_FAILED,$variable_name,$command_value);
							}
							break;
						}
		 
		}//switch
		return $bret;
	}//validdate command


}

?>