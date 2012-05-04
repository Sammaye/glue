<?php
/**
 * This is an iterator class for Mongodb Subdocuments
 *
 * Welcome to PHP Complexity Hell!
 *
 * @author smillman
 */
class MongoEmbeddedCursor implements ArrayAccess, Iterator, Countable{

	private $_class;
	private $_container = array();

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
	        $o = new $this->_class();
	        $o->__attributes(current($this->_container));
	        return $o;
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