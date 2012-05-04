<?php
class MongoModel{

	public $class;

	public function update($find, $doc, $options = array()){
		$o = new $this->class;
		return glue::db()->{$o->getCollectionName()}->update($find, $doc, $options);
	}
}