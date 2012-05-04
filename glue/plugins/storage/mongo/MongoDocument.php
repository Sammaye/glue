<?php
class MongoDocument extends GModel{

	const HAS_ONE = 4;
	const HAS_MANY = 8;

	public $_id; // required MongoDB Field

	private $_new = false;

	private $_collectionName;
	private $_collection;

	private $_oldRecord;
	private $_mongoModel;

	public function __get($k){
		if(array_key_exists($k, $this->relations())){
			return $this->with($k);
		}else{
			return $this->$k;
		}
	}

	public function __set($k, $v){
		$this->$k = $v;
	}

	public function indexes(){
		return array();
	}

	public function getAttributes() {
		$attributes = Array();

		$reflect = new ReflectionClass(get_class($this));
		$class_vars = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

		foreach ($class_vars as $prop) {
			$attributes[$prop->getName()] = $this->{$prop->getName()};
		}
		return $attributes;
	}

	function getAttribute($k){
		return $this->$k;
	}

	function __construct($scenario ='insert'){
		$this->setScenario($scenario);
		$this->setIsNewRecord(true);

		// Get collection
		$this->_collection = glue::db()->getCollection($this->getCollectionName());

//		// Set the indexes for this collection
//		$indexes = $this->indexes();
//		foreach($indexes as $k => $v){
//			if(!isset($v[2])){
//				$this->collection()->ensureIndex($v[0], isset($v[1]) ? $v[1] : array());
//			}else{
//				glue::db()->getCollection($v[2])->ensureIndex($v[0], isset($v[1]) ? $v[1] : array());
//			}
//		}
	}

	public function collection(){
		return $this->_collection;
	}

	public function getCollectionName(){
		return $this->_collectionName;
	}

	public function setCollectionName($collection){
		$this->_collectionName = $collection;
	}

	public function setIsNewRecord($bool){
		$this->_new = $bool;
	}

	public function getIsNewRecord(){
		return $this->_new;
	}

	public function primaryKey(){
		return '_id';
	}

	public function oldRecord(){
		return $this->_oldRecord;
	}

	public static function model($function, $query = array(), $fields = array(), $className = __CLASS__){
//var_dump($className); exit();
		if($function){
			$o = new $className;
			$ar = Glue::db()->{$o->getCollectionName()}->$function($query, $fields);
//var_dump($ar);
			if($function == "findOne"){

				if(!$ar)
					return null;

				$o->setIsNewRecord(false);
				$o->setScenario('update');

				$o->__attributes($ar);
				$o->afterFind();
				return $o;
			}elseif($function == "find"){
				return glue::db()->getActiveCursor($ar, $className);
			}
		}else{
//echo "her"; exit();
			if($this->_mongoModel){
				$this->_mongoModel->class = $className;
			}else{
				$this->_mongoModel = new MongoModel();
				$this->_mongoModel->class = $className;
			}
			return $this->_mongoModel;
		}
	}

	function relations(){
		return array();
	}

	function with($k, $where = array()){
		$relations = $this->relations();
		if(array_key_exists($k, $relations)){
			$relation = $relations[$k];

			$c_name = $relation[1];
			$f_key = $relation[2];

			$o = new $c_name();

			$f_key_val = isset($relation['on']) ? $this->{$relation['on']} : $this->{$this->primaryKey()};

			$clause = array_merge(array($f_key=>$f_key_val), $where);

			if($relation[0]&self::HAS_ONE){
				$o = self::model('findOne', $clause, array(), $c_name);
				return $o;
			}elseif($relation[0]&self::HAS_MANY){
				return glue::db()->getActiveCursor(Glue::db()->{$o->getCollectionName()}->find($clause), $c_name);
			}
		}else{
			return false;
		}
	}

	/**
	 * Only use to bypass active record! This function should only be used when active record might cause internal looping or scaling problems.
	 *
	 * This function is best used on tree classes where internal looping is easily done.
	 *
	 * @param $collection
	 */
	function Db($collection = null){
		if($collection){
			return Glue::db()->{$collection};
		}else{
			return Glue::db()->{$this->getCollectionName()};
		}
	}

	function save($params = array()){

		$this->_oldRecord = (Object)$this->__toArray();

		if($this->beforeSave()){
			$attributes = $this->__toArray();
//var_dump($attributes);

			// Get the fields we are saving
			if(isset($params['fields'])){
				foreach($attributes as $field=>$value){
					if(array_key_exists($field, array_flip($fields))){
						$doc[$field] = $value;
					}
				}
			}else{
				$doc = $attributes;
			}
//var_dump($doc);
//exit();
			if($this->getIsNewRecord()){ // If is new record insert
				if($params['safe'] === true) $queryOptions['safe'] = true;
				$this->insert($doc, $queryOptions);
			}else{ // Update
				if($params['upsert'] === true) $queryOptions['upsert'] = true;
				if($params['safe'] === true) $queryOptions['safe'] = true;
				$this->update($doc, $queryOptions);
			}

			return $this->afterSave();
		}else{
			return false;
		}
	}

	function insert($doc, $queryOptions){
		if(!$doc['_id'] instanceof MongoId)
			unset($doc['_id']);

		Glue::db()->{$this->getCollectionName()}->insert($doc, $queryOptions);

		$this->__attributes($doc);
		//$this->setIsNewRecord(false);
		return true;
	}

	function update($doc, $queryOptions){
		//echo "update"; var_dump($doc); exit();
		Glue::db()->{$this->getCollectionName()}->update(array(
			$this->primaryKey()=>$this->{$this->primaryKey()}),
			$doc,
			$queryOptions
		);
		return true;
	}

	function remove(){
		Glue::db()->{$this->getCollectionName()}->remove(array("_id" => $this->_id));
		return true;
	}

	function query($query, $queryOptions = array()){
		return Glue::db()->{$this->getCollectionName()}->update($query, $queryOptions);
	}

	static function search($fields, $term, $extra = array(), $class = __CLASS__){

		$query = array();

		$working_term = trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $term)); // Strip all whitespace to understand if there is actually characters in the string

		if(strlen($working_term) <= 0){ // I dont want to run the search if there is no term
			$result = self::model('find', $extra, array(), $class); // If no term is supplied just run the extra query placed in
			return $result;
		}

		$broken_term = explode(' ', $term);

		// Strip whitespace from query
		foreach($broken_term as $k => $term){
			$broken_term[$k] = trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $term));
		}

		// Now lets build a regex query
		$sub_query = array();
		foreach($broken_term as $k => $term){

			// Foreach of the terms we wish to add a regex to the field.
			// All terms must exist in the document but they can exist across any and all fields
			$field_regexes = array();
			foreach($fields as $k => $field){
				$field_regexes[] = array($field => new MongoRegex('/'.$term.'/i'));
			}
			$sub_query[] = array('$or' => $field_regexes);
		}
		$query['$and'] = $sub_query; // Lets make the $and part so as to make sure all terms must exist
		$query = array_merge($query, $extra); // Lets add on the additional query to ensure we find only what we want to.

		// TODO Add relevancy sorting
		$result = self::model('find', $query, array(), $class);
		return $result;
	}

	/** EVENTS **/
	function beforeFind(){ return true; }

	function afterFind(){ return true; }

	function beforeSave(){ return true; }

	function afterSave(){ return true; }

	function __modelToArray(){
		$props = $this->getAttributes();

		foreach($props as $k => $prop){
			if($prop instanceof MongoEmbeddedDocuments){
				$props[$k] = $prop->get();
			}
		}

		return $props;
	}


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

		$props = $this->getAttributes();

		foreach($props as $k => $prop){
			if($prop instanceof MongoEmbeddedDocuments){
				$props[$k] = $prop->get();
			}
		}

		return $props;
	}
}