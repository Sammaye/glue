<?php
class HelpSearch_SphinxCursor implements ArrayAccess, Iterator, Countable{

	private $_container;

    public function __construct($matches) {
    	$this->_container = $matches;
        //$this->_container->reset();
        reset($this->_container);
    }

    function set($matches){
		$this->_container = $matches;
    }

    function get(){
		return $this->_container;
    }

    function count(){
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

        	$c = $this->_container[$offset]['attrs'];

			if($c['type']){
	        	switch($c['type']){
					case "topic":
						$o = HelpTopic::model('findOne', array('_id' => new MongoId($c['_id'])));
						break;
					case "article":
						$o = HelpArticle::model('findOne', array('_id' => new MongoId($c['_id'])));
						break;
	        	}
			}else{
				$o = (Object)$c;
			}
			return $o;
        }

       	return null; //Else lets just return normal
    }

    function rewind() {
    	reset($this->_container);
    }

    function current() {

    	if(current($this->_container) !== false){
	    	$c = current($this->_container);

	    	if($c['attrs']['type']){
	    		switch($c['attrs']['type']){
					case "topic":
						$o = HelpTopic::model('findOne', array('_id' => new MongoId($c['attrs']['_id'])));
						break;
					case "article":
						$o = HelpArticle::model('findOne', array('_id' => new MongoId($c['attrs']['_id'])));
						break;
				}
	    	}else{
				$o = (Object)$c['attrs'];
	    	}
	        return $o;
    	}else{
    		return  false;
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