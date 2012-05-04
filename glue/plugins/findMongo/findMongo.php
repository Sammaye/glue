<?php
class findMongo extends GApplicationComponent{

	public $term;

	function search($collection, $fields, $term, $extra = array()){

		$query = array();

		$broken_term = explode(' ', $term);

		// Strip whitespace from query
		foreach($broken_term as $k => $term){
			$broken_term[$k] = trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $term));
		}

		// Now lets build a regex query
		$sub_query = array();
		foreach(fields as $k => $field){

			$field_regexes = array();
			foreach($broken_term as $k => $term){
				array($field => new MongoRegex('/'.$term.'/i'));
			}
			$sub_query[] = array('$or' => $field_regexes);
		}
		$query['$and'] = $sub_query;
		$query = array_merge($query, $extra);

		$result = glue::db()->query($query);

		return $result;
	}
}