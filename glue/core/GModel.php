<?php

class GModel{

	private $_scenario;

	private $_valid = false;
	private $_success = false;
	private $_validated = false;
	private $_success_message;

	private $_error_codes = array();
	private $_error_messages = array();
	private $_remembered_attributes;

	function __get($k){
		return $this->$k;
	}

	function __set($k, $v){
		$this->$k = $v;
	}

	public function getScenario(){
		return $this->_scenario;
	}

	public function setScenario($scenario){
		$this->_scenario = $scenario;
	}

	public function setSuccess($bool){
		$this->_success = $bool;
	}

	public function getSuccess(){
		return $this->_success;
	}

	public function setSuccessMessage($message){
		$this->_success = true;
		$this->_success_message = $message;
	}

	public function getSuccessMessage(){
		return $this->_success_message;
	}

	public function getHasBeenValidated(){
		return $this->_validated;
	}

	public function setHasBeenValidated($validated){
		$this->_validated = $validated;
	}

	public function isValid(){
		return $this->_valid;
	}

	function attributes($a){
		$scenario = $this->getScenario();

		// Set main model fields
		foreach($this->rules() as $rule){

			$scenarios = explode(", ", $rule['on']);

			if(array_key_exists($scenario, array_flip($scenarios)) || !isset($rule['on'])){
				if($rule[1] == 'required' || $rule[1] == 'safe'){
					$fields = explode(", ", $rule[0]);

					foreach($fields as $field){
						$this->$field = $a[$field];
					}
				}
			}
		}
	}

	/**
	 * This is an internal only function
	 * It must not be used externally due to security risks.
	 *
	 * This function is mainly used by the activeRecord itself on finds to populate the model initially.
	 */
	public function __attributes($a){
		if($a){
			foreach($a as $k=>$v){
				if(!array_key_exists($k, $this->relations())){
					$this->$k = $v;
				}
			}
		}
	}

	public function getAttributes() {
		$attributes = Array();

		$reflect = new ReflectionClass(get_class($this));
		$class_vars = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

	  	foreach ($class_vars as $prop) {
	  		$attributes[$prop->getName()] = $this->{$prop->getName()};
	  	}
	  	return $attributes;
	}

	function getAttribute($k){
		if(!$this->_remembered_attributes){
			$attributes = Array();

			$reflect = new ReflectionClass(get_class($this));
			$class_vars = $reflect->getProperties(ReflectionProperty::IS_PROTECTED);

		  	foreach ($class_vars as $prop) {
		  		$attributes[$prop->getName()] = $this->{$prop->getName()};
		  	}
		  	$this->_remembered_attributes = $attributes;
		}

		if(isset($this->_remembered_attributes[$k])){
			return $this->$k;
		}else
			return null;
	}

	function files($a){
		$scenario = $this->getScenario();

		// Set main model fields
		foreach($this->rules() as $rule){

			$scenarios = explode(", ", $rule['on']);

			if(array_key_exists($scenario, array_flip($scenarios)) || !isset($rule['on'])){
				if($rule[1] == 'file' || $rule[1] == 'multifile'){
					$fields = explode(", ", $rule[0]);

					foreach($fields as $field){
						if($rule[1] == "file"){
							$this->$field = array(
								"name" => $a['name'][$field],
								"type" => $a['type'][$field],
								"tmp_name" => $a['tmp_name'][$field],
								"error" => $a['error'][$field],
								"size" => $a['size'][$field]
							);
						}elseif($rule[1] == "multifile"){
							$c = count($a['name'][$field]);
							$files = array();

							for($i=0; $i < $c; $i++){
								foreach($_FILES as $fileAttribute => $details){
									$files[$i][$fileAttribute] = $details[$field][$i];
								}
							}
							$this->$field = $files;
						}
					}
				}
			}
		}
	}

	public function getFiles($byScenario = false){
		$files = array();
		$valid = true;

		$scenario = $this->getScenario();

		foreach($this->rules() as $rule){
			if($rule[1] == 'file' || $rule[1] == 'multifile'){
				if($byScenario){
					$scenarios = explode(", ", $rule['on']);

					if(array_key_exists($scenario, array_flip($scenarios)) || !isset($rule['on'])){
						$valid = true;
					}else{
						$valid = false;
					}
				}

				if($valid){
					$fields = explode(", ", $rule[0]);
					foreach($fields as $field){
						$files[] = $this->$field;
					}
				}
			}
		}
		return $files;
	}

	public function getFormVariableName($attribute){
		$model = $this;

		if(($pos=strpos($attribute,'['))!==false)
		{
			if($pos!==0)  // e.g. name[a][b]
				return get_class($model).'['.substr($attribute,0,$pos).']'.substr($attribute,$pos);
			if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
			{
				$sub=substr($attribute,0,$pos+1);
				$attribute=substr($attribute,$pos+1);
				return get_class($model).$sub.'['.$attribute.']';
			}
			if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
			{
				$name=get_class($model).'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
				$attribute=$matches[1];
				return $name;
			}
		}
		else
			return get_class($model).'['.$attribute.']';
	}

	public function getFormVariableValue($attribute, $options = array()){

		$model = $this;

		if(isset($options['value'])){
			return $options['value'];
		}else{
			if(($pos=strpos($attribute,'['))!==false)
			{
				if($pos!==0)  // e.g. name[a][b]
					return $this->getAttribute(substr($attribute,0,$pos));
				if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
				{
					$sub=substr($attribute,0,$pos+1);
					$attribute=substr($attribute,$pos+1);
					return $this->getAttribute($attribute);
				}
				if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
				{
					$name=get_class($model).'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
					$attribute=$matches[1];
					return $this->getAttribute($attribute);
				}
			}
			else
				return $this->getAttribute($attribute);
		}

		return "";
	}

	public function rules(){ return array(); }

    public function hasErrors($fields = array(), $errors = array(), $useAnd = false){

		if(count($fields) <= 0 && count($errors) <= 0){
			return count($this->_error_codes) > 0 || count($this->_error_messages) > 0;
		}elseif(count($fields) > 0 && count($errors) <= 0){
			foreach($fields as $field){
				if(isset($this->_error_codes[$field])){
					return true;
				}

				if(isset($this->_error_messages[$field])){
					return true;
				}
			}
			return false;
		}elseif(count($fields) <= 0 && count($errors) > 0){
			$fields = $this->__getAttributeList();
			foreach($fields as $field){
				foreach($errors as $match){
					if(is_array($this->_error_codes[$field])){
						if(array_key_exists($match, array_flip($this->_error_codes[$field])))
							return true;
					}
				}
			}
			return false;
		}else{

			$errors_found = 0;
			$fields_matched = 0;
			foreach($fields as $field){
				foreach($errors as $search_code){
					if(is_array($this->_error_codes[$field])){
						if(array_key_exists($search_code, array_flip($this->_error_codes[$field]))){
							if(!$useAnd)
								return true;
							else
								$errors_found++;
						}
					}
				}

				if($useAnd){
					if($errors_found > 1) $fields_matched++;
				}
			}

			if($useAnd)
				return count($fields_matched) == $this->count();
			else
				return false;
		}

    }

    function getErrors($field){
		if(isset($this->_error_messages[$field])){
			return $this->_error_messages[$field];
		}
		return array();
    }

    function getFirstError($field){
		if(isset($this->_error_messages[$field])){
			return $this->_error_messages[$field][0];
		}
		return '';
    }

    public function addErrorCode($field, $code){
		$this->_error_codes[$field][] = $code;
		return true;
    }

    function getErrorCodes(){
		return $this->_error_codes;
    }

    public function getErrorMessages(){
		return $this->_error_messages;
    }

	function addErrorMessage($field, $message = null){
		if(!$message){
			$this->_error_messages[] = $field;
		}else{
			$this->_error_messages[$field][] = $message;
		}
	}

	/**
	 * This function decides if the form has a summary waiting to be used
	 */
	function hasSummary(){
		if($this->getSuccess() || $this->hasErrors())
			return true;
		return false;
	}

	function getErrorSummary_JSON($models = array(), $options = array()){

		if($this->getSuccess()){
			return json_encode(array( 'valid' => false ));
		}

		if($this->hasErrors()){
			$messages = $this->getErrorMessages();
		}

		foreach($models as $model){
			if($model->hasErrors()){
				$messages = concat($messages, $model->getErrorMessages());
			}
		}

		return json_encode(array( 'valid' => false, 'errors' => $messages ));
	}

	public function errorMessages(){ return array(); }

	public function successMessages(){ return array(); }

	/**
	 * Validates the rules for a given GModel or GForm
	 *
	 * @param array $rules The rules of the model
	 * @param array $attributes The attributes of the model and their values
	 */
	function validate($fields = array()){

		$valid = $this->beforeValidate();
		$paramFields = $fields;

		foreach($this->rules() as $rule){

			$fields = explode(", ", $rule[0]);
			$scenarios = explode(", ", $rule['on']);

			if(array_key_exists($this->getScenario(), array_flip($scenarios)) || !isset($rule['on'])){ // Set scenario says only this scenario, no scenario means global

				foreach($fields as $field){ // Lets apply this to each field that has been supplied in the "attributes" variable

					if(!(empty($paramFields) || is_null($paramFields))){
						if(!in_array($field, $paramFields)) continue;
					}

					// Run the required function to find out if the field is empty.
					$empty = $this->isempty($field, $this->$field);
					if($rule[1] == "required" && $empty){

						$valid = false;
						$this->addErrorCode($field, "EMPTY"); // Field is required but empty

					}elseif(!$empty && $rule[1] != "required" && $rule[1] != "safe"){ // Field is not required and we are not running the required rule
						// Go into the main translation block
						if(is_callable(array($this, $rule[1]))){
							$params = $rule;

							unset($params[0]);
							unset($params[1]);

							$valid = $this->$rule[1]($field, $this->$field, $params);
						}else{
							throw new Exception("That validator does not exist: {$rule[1]}!");
						}
					}
				}

				if(!$valid && isset($rule['message'])){
					$this->addErrorMessage($rule['message']);
				}
			}
		}

		$this->_valid = !$this->hasErrors() && $valid;

		if($this->afterValidate())
			$this->_valid = $this->_valid && true;
		else
			$this->_valid = $this->_valid && false; // We do this after so that we know if everything so far was valid

		$this->errorMessages();
		$this->setHasBeenValidated(true);

		if($this->_valid)
			return true;

		return false;
	}

	/**
	 * VALIDATORS
	 */

	/**
	 * Checks if field is empty
	 *
	 * @param string $field The field which to test
	 * @param mixed $value The value of the field to test
	 */
	public function isempty($field, $value){
		if(is_array($value)){
			foreach($value as $part){
				if(strlen(stripTags_whitespace($part)) <= 0){
					return true;
				}
			}
		}else{
			if(strlen(stripTags_whitespace($value)) <= 0)
			return true;
		}
		return false;
	}

	/**
	 * Checks if value entered is equal to 1 or 0, it also allows null values
	 *
	 * @param string $field The field to be tested
	 * @param mixed $value The field value to be tested
	 * @param array $params The parameters for the validator
	 */
	public function true_false($field, $value, $params){

		if(!isset($params['allowNull']) && !$params['allowNull'] && ($value === null || $value === false)){
			$this->addErrorCode($field, "TF_NULL");
		}

		if($value == 1 || ($value == 0 || !$value)){
			return true;
		}else{
			$this->addErrorCode($field, "TF_OOR");
		}
	}

	/**
	 * Detects the character length of a certain fields value
	 *
	 * @param $field
	 * @param $value
	 * @param $params
	 */
	public function charLength($field, $value, $params){

		if(isset($params['min'])){
			if($params['min'] > strlen($value)){ // Lower than min required
			$this->addErrorCode($field, "CL_OOR");
			return false;
			}
		}

		if(isset($params['max'])){
			if($params['max'] < strlen($value)){
			$this->addErrorCode($field, "CL_OOR");
			return false;
			}
		}
		return true;
	}

	public function notExist($field, $value, $params){
		$cName = $params['class'];
		$object = $cName::model('findOne', array($params['field']=>$value));

		if($object->_id instanceof MongoId){
			$this->addErrorCode($field, "NE_OBFOUND");
		}else{
			return true;
		}
	}

	public function isExist($field, $value, $params){
		if($params['allowNull']){
			if(strlen($value) <= 0 || !$value){
				return true;
			}
		}

		$cName = $params['class'];
		$object = $cName::model('findOne', array($params['field']=>$value));

		if($object->_id instanceof MongoId){
			return true;
		}else{
			$this->addErrorCode($field, "IE_OBNFOUND");
		}
	}

	public function in($field, $value, $params){

		$found = false;
		foreach($params['range'] as $match){
			if($match == $value){
				$found = true;
			}
		}

		if(!$found){
			$this->addErrorCode($field, "IN_OOR");
		}
		return true;
	}

	public function nin($field, $value, $params){
		$found = false;
		foreach($params['range'] as $match){
			if($match == $value){
				$found = true;
			}
		}

		if($found){
			$this->addErrorCode($field, "NIN_FOUND");
		}
		return true;
	}

	public function regex($field, $value, $params){

		if($params['nin']){
			if(preg_match($params['pattern'], $value) > 0){
				$this->addErrorCode($field, "REGEX_NOTVALID");
			}
		}else{
			if(preg_match($params['pattern'], $value) <= 0 || preg_match($params['pattern'], $value) === false){
				$this->addErrorCode($field, "REGEX_NOTVALID");
			}
		}
		return true;
	}

	public function range($field, $value, $params){

		if(isset($params['min'])){
			if((int)$value < $params['min'])
			$this->addErrorCode($field, "RNG_OOR");
		}

		if(isset($params['max'])){
			if((int)$value > $params['max'])
			$this->addErrorCode($field, "RNG_OOR");
		}
		return true;
	}

	public function hash($field, $value, $params){
		if($value != $_SESSION['form_hash']){
			$this->addErrorCode($field, "HASH_NF");
		}
		return true;
	}

	public function compare($field, $value, $params){
		if($value != $params['with']){
			$this->addErrorCode($field, "CMP_NOTMATCH");
		}
		return true;
	}


	/** DATA TYPES **/

	public function email($field, $value, $params){
		/** Asks the is_email function whether or not email is valid */
		if(!is_email($value, true)){

			/** Email is not valid */
			$this->addErrorCode($field, "EM_NOT_VALID");
		}
		return true;
	}

	function numerical($field, $val){
		if(preg_match("/^([0-9]+)$/i", $val) <= 0 || !preg_match("/^([0-9]+)$/i", $val)){
			$this->addErrorCode($field, "NOT_NUMERIC");
		}
		return true;
	}

	function url($field, $val){

		$parsed_url = parse_url($val);

		if(!$parsed_url){
			$this->addErrorCode($field, "NOT_URL");
		}

		if(!isset($parsed_url['scheme']) || !isset($parsed_url['host'])){
			$this->addErrorCode($field, "NOT_URL");
		}
		return true;
	}

	function file($field, $value, $params){

		$fieldValue = $this->$field;

		if($fieldValue['error'] === UPLOAD_ERR_OK){
			if(isset($params['ext'])){
				$path = pathinfo($fieldValue['name']);

				$found = false;
				foreach($params['ext'] as $ext){
					if($ext == $path['extension'])
					$found = true;
				}

				if(!$found)
				$this->addErrorCode($field, "FILE_EXT");
			}

			if(isset($params['size'])){
				if(isset($params['size']['gt'])){
					if($fieldValue['size'] < $params['size']['gt'])
					$this->addErrorCode($field, "FILE_SIZE");
				}elseif(isset($params['size']['lt'])){
					if($fieldValue['size'] > $params['size']['lt'])
					$this->addErrorCode($field, "FILE_SIZE");
				}
			}

			if(isset($params['type'])){
				if(preg_match("/".$params['type']."/i", $fieldValue['type']) === false || preg_match("/".$params['type']."/i", $fieldValue['type']) < 0)
				$this->addErrorCode($field, "FILE_TYPE");
			}
		}else{
			switch ($fieldValue['error']) { // TODO Sort out these errors to make them function correctly.
				case UPLOAD_ERR_INI_SIZE:
					//return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
				case UPLOAD_ERR_FORM_SIZE:
					//return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				case UPLOAD_ERR_PARTIAL:
					//return 'The uploaded file was only partially uploaded';
				case UPLOAD_ERR_NO_FILE:
					//return 'No file was uploaded';
				case UPLOAD_ERR_NO_TMP_DIR:
					///return 'Missing a temporary folder';
				case UPLOAD_ERR_CANT_WRITE:
					//return 'Failed to write file to disk';
				case UPLOAD_ERR_EXTENSION:
					//return 'File upload stopped by extension';
				default:
					$this->addErrorCode($field, 'FILE_INVALID');
			}
		}
		return true;
	}

	function multifile($field, $value, $params){

	}

	function charRange($field, $value, $params){
		if(isset($params['max'])){
			if($params['max'] < strlen($value)){
				return false;
			}
		}
		return true;
	}

	function tokenize($field, $value, $params){

		$ex_val = explode($params['del'], $value);

		if(isset($params['max'])){
			if(sizeof($ex_val) > $params['max']){
				$this->addErrorCode($field, 'TOK_MAX');
				return false;
			}
		}
		return true;
	}

	/**
	 * EVENTS
	 */

	function beforeValidate(){ return true; }

	function afterValidate(){ return true; }


	/**
	 * HELPER FUNCTIONS
	 */

	function __toArray(){

		foreach($this->rules() as $rule){
			if($rule[1] == "true_false"){ // If it is set to zero null then do the tedious part of finding the field in it
				$null_fields = explode(", ", $rule[0]);
				foreach($null_fields as $field){
					if($this->$field == null || !$this->$field || $this->$field == ""){
						$this->$field = 0;
					}
				}
			}
		} // Now that we know which fields are to be zero nulled lets actually form the fields

		return $this->getAttributes();
	}

	function __getAttributeList(){
		$attrs = $this->getAttributes();
		$at_ar = array();

		foreach($attrs as $k=>$v){
			$at_ar[] = $k;
		}
		return $at_ar;
	}
}