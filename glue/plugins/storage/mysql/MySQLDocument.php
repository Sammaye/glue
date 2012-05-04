<?php
class MySQLDocument extends GModel{

	private $_meta = array();
	private $_new = false;

	public function table(){
		return '';
	}

	function __set($k, $v){
		$this->$k = $v;
	}

	function __get($k){
		if(!$k) trigger_error('Cannot set a property of nothing idiot');
		return $this->{$k};
	}

	public function setIsNewRecord($bool){
		$this->_new = $bool;
	}

	public function getIsNewRecord(){
		return $this->_new;
	}

	function __construct($scenario = 'insert'){
		$result = glue::mysql()->query('SHOW COLUMNS FROM '.$this->table());

		if(mysql_num_rows($result) <= 0)
			trigger_error('There were no columns for '.$this->table().' in '.__CLASS__);

		while($row = mysql_fetch_assoc($result)){
			$this->_meta[$row['Field']] = array(
				'type' => $row['Type'],
				'null' => $row['Null'],
				'key' => $row['Key'],
				'default' => $row['Default'],
				'extra' => $row['Extra']
			);
		}

		$this->setScenario($scenario);
		$this->setIsNewRecord(true);
	}

	static function findOne($query = '', $fields = array(), $className = __CLASS__){
		$result = glue::mysql()->findOne($query, $fields);

		if(!$result)
			return null;

		$o = new $className;
		$o->__attributes($result);
		$o->setIsNewRecord(false);
		return $o;
	}

	static function find(){}

	function set_attribute($k, $v){
		$this->$k = $v;
	}

	function set_attributes($attrs, $db_only = false){
		if($db_only){
			$fields = $this->_meta;
		}else{
			$fields = $attrs;
			//$fields = array_merge($this->_meta, $this->get_attributes());
		}
		foreach($fields as $field => $meta){
			$this->$field = $attrs[$field];
		}
	}

	function get_attributes($db_only = false){
		if($db_only){
			$fields = $this->_meta;

			$attributes = array();
			foreach($fields as $field => $meta){
				$attributes[$field] = $this->{$field};
			}
			return $attributes;
		}else{
			$attributes = Array();

			$reflect = new ReflectionClass(get_class($this));
			$class_vars = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
		  	foreach ($class_vars as $prop) {
		  		$attributes[$prop->getName()] = $this->{$prop->getName()};
		  	}
		  	return $attributes;
		}
	}

	function save(){

		$model_attributes = $this->get_attributes(true);
		$sql = '';
		$pdo_attributes = array();

		if($this->getIsNewRecord()){
			$sql = 'INSERT INTO '.$this->table().' (#cols) VALUES (#vals)';

			$cols_names = array();
			$pdo_cols = array();
			$pdo_attributes = array();
			foreach($model_attributes as $field => $value){
				$cols_names[] = $field;
				$pdo_cols[] = ':'.$field;
				$pdo_attributes[':'.$field] = $value;
			}

			$sql = str_replace('#cols', implode(', ', $cols_names), $sql);
			$sql = str_replace('#vals', implode(',', $pdo_cols), $sql);
		}else{
			$sql = 'UPDATE '.$this->table().' SET #vals WHERE id = :id';

			$pdo_cols = array();
			$pdo_attributes = array();
			foreach($model_attributes as $field => $value){
				$pdo_cols[] = $field.'=:'.$field;
				$pdo_attributes[':'.$field] = $value;
			}
			$pdo_attributes[':id'] = $this->id;

			$sql = str_replace('#vals', implode(',', $pdo_cols), $sql);
		}
		glue::mysql()->query($sql, $pdo_attributes);
	}
}