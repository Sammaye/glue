<?php
class GMongoCursor implements Iterator, Countable{

	private $className;
	private $cursor;

	private $isMr;

    public function __construct($cursor, $className = __CLASS__, $isMR = false) {
    	$this->cursor = $cursor;
    	$this->className = $className;
    	$this->isMr = $isMR;

    	if($this->cursor)
        	$this->cursor->reset();
    }

    function cursor(){
    	return $this->cursor;
    }

    function count(){
    	if($this->cursor())
    		return $this->cursor()->count();
    }

    function sort(array $fields){
		$this->cursor()->sort($fields);
		return $this;
    }

    function skip($num){
		$this->cursor()->skip($num);
		return $this;
    }

    function limit($num){
		$this->cursor()->limit($num);
		return $this;
    }

    function rewind() {
        $this->cursor()->rewind();
        return $this;
    }

    function current() {
        $o = new $this->className();

        $o->setIsNewRecord(false);
        $o->setScenario('update');

        if($this->isMr){
			$doc = $this->cursor()->current();
			$o->__attributes(array_merge(array('_id'=>$doc['_id']), $doc['value']));
        }else{
        	$o->__attributes($this->cursor()->current());
        }
        return $o;
    }

    function key() {
        return $this->cursor()->key();
    }

    function next() {
        return $this->cursor()->next();
    }

    function valid() {
        return $this->cursor()->valid();
    }
}