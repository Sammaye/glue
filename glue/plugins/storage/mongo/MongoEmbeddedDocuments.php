<?php
/**
 * This is an iterator class for Mongodb Subdocuments
 *
 * Welcome to PHP Complexity Hell!
 *
 * @author smillman
 */
class MongoEmbeddedDocuments implements ArrayAccess, Iterator, Countable{

	private $_class;
	private $_container = array();

	function get(){
		return $this->_container;
	}

	function getClass(){
		return $this->_class;
	}

	function setClass($class){
		$this->_class = $class;
	}

	public function __construct($container = array(), $class = null){
		if($container instanceof MongoEmbeddedDocuments){
			$this->_container = $container->get();
		}else{
			$this->_container = $container;
		}
		$this->_class = $class;
	}

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
			return $this->_container[$offset];
        }
       	return null; //Else lets just return normal
    }

    public function rewind() {
        reset($this->_container);
    }

    public function current() {
    	if(current($this->_container) !== false){
			return current($this->_container);
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

	function isUnique($field){
		$used = array();

		foreach($this->_container as $item){
			if(array_key_exists($item[$field], $used)){
				return false;
			}else{
				$used[$item[$field]] = 1;
			}
		}
		return true;
	}
}