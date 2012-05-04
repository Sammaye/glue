<?php
class MongoEmbeddedDocument extends MongoDocument{

	function getCollectionName(){
		return '_sub';
	}

	function primaryKey(){
		throw new Exception('A subdocument cannot have a primary key!');
	}

	function save(){
		throw new Exception('Saving etc is done from the MongoEmbeddedDocuments class. This allows you to effectively and efficently add new subdocuments so please use that.');
	}

	function update(){
		throw new Exception('Saving etc is done from the MongoEmbeddedDocuments class. This allows you to effectively and efficently add new subdocuments so please use that.');
	}
}