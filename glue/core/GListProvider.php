<?php
class GListProvider implements ArrayAccess, Iterator, Countable{

	private $_container;
	private $_class;

	public function __construct($list_file, $fields, $class = null){

		if($class)
			$this->_class = $class;

		$return_array = array();
		$list = include ROOT.'/application/lists/'.$list_file.'.php';

		if(count($fields) <= 0)
			$this->_container = $list;
		elseif(is_array($fields)){
			foreach($list as $row){
				$return_array[$row[$fields[0]]] = $row[$fields[1]];
			}
			$this->_container = $return_array;
		}elseif(is_string($fields)){
			foreach($list as $row){
				$return_array[] = $row[$fields];
			}
			$this->_container = $return_array;
		}
	}

	public function sort(){}

	public function count(){
		return count($this->_container);
	}

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_container[] = $value;
        } else {
            $this->_container[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_container[$offset]);
    }

    public function offsetGet($offset) {
    	//var_dump(__METHOD__);
        if(isset($this->_container[$offset])){
			if($this->_class){
	        	$o = new $this->_class;
	        	$o->__attributes($this->_container[$offset]);
				return $o;
			}else{
				return $this->_container[$offset];
			}
        }

       	return null; //Else lets just return normal
    }

    public function rewind() {
        reset($this->_container);
    }

    public function current() {
    	if(current($this->_container) !== false){
    		if($this->_class){
	        	$o = new $this->_class();
	        	$o->__attributes(current($this->_container));
	        	return $o;
    		}else{
    			return current($this->_container);
    		}
    	}else{
    		return false;
    	}
    }

    public function key() {
        return key($this->_container);
    }

    public function next() {
        return next($this->_container);
    }

    public function valid() {
        return $this->current() !== false;
    }
}