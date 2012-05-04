<?php

require_once ( "sphinxapi.php" );

class sphinx_searcher extends GApplicationComponent{

	public $host;
	public $port;
	public $indexes = array();

	public $matches;
	public $total_found = 0;
	public $max_page;
	public $page = 1;
	public $limit;

	public $term;

	private $sphinx;

	function init(){
		$this->sphinx = new SphinxClient();
		$this->sphinx->SetServer ( $this->host, $this->port );
		$this->sphinx->SetConnectTimeout ( 1 );
		$this->sphinx->SetArrayResult ( true );
		$this->sphinx->SetFilter('deleted', array(1), true);

		return $this;
	}

	public function getSearcher(){
		return $this;
	}

	public function model(){
		return $this->sphinx;
	}

	function setSortMode($mode = SPH_SORT_RELEVANCE, $sortby = ''){
		$this->sphinx->SetSortMode($mode, $sortby);
	}

	function setRankingMode($mode = SPH_RANK_PROXIMITY_BM25){
		$this->sphinx->SetRankingMode($mode);
	}

	function setMatchMode($mode = SPH_MATCH_ALL){
		$this->sphinx->SetMatchMode($mode);
	}

	function setSelect($select){
		$this->sphinx->SetSelect($select);
	}

	function setFilter($attribute, $values = array(), $exclude = false){
		$this->sphinx->SetFilter($attribute, $values, $exclude);
	}

	function setFilterRange ( $attribute, $min, $max ) {
		$this->sphinx->SetFilterRange($attribute, $min, $max);
	}

	function UpdateAttributes($index, $attrs, $values){
		return $this->sphinx->UpdateAttributes($index, $attrs, $values);
	}

	function resetPage(){
		$this->page = 1;
	}

	function resetLimit(){
		unset($this->limit);
	}

	function resetAll(){
		$this->page = 1;
		unset($this->limit);
		$this->sphinx->ResetFilters();
		$this->sphinx->ResetGroupBy();
		$this->sphinx->ResetOverrides();
	}

	function formFields($query, $index = 'main'){
		if($query && strlen($query) > 0){
			$index_attr = $this->indexes[$index];
			$query_array = array();
			foreach($index_attr['query_fields'] as $field){
				$query_array[] = $field;
			}
			$query_string .= '@('.implode(',', $query_array).') '.$query;
			return $query_string;
		}else{
			return '';
		}
	}

	/**
	 * The main query function.
	 *
	 * @example query(array('select' => $_GET['q'], 'where' => array('uid' => array(strval($this->_id))), [ 'query' => '@title "the"' ]), 'media');
	 *
	 * @param $query
	 * @param $index
	 */
	function query($query = array(), $index = ''){

		$query_string = '';
		$this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);

		// Lets get the indexes information
		$index_attr = $this->indexes[$index];

		// If that index is to be passed into a cursor
		if(isset($index_attr['cursor'])){
			glue::import($index_attr['cursor'], 'glue/plugins/sphinx/'.$index_attr['cursor'].'.php');
			$cursor = $index_attr['cursor']; // Cos PHP is still a bit weird when trying to set a class name from a element array we use the predefined variable
		}

		// **
		// Does this index have a Delta?
		// **
		if($index_attr['type'] == 'delta'){ // If index has delta lets handle that
			$index_string = $index.' '.$index_attr['delta'];
		}

		// **
		// Lets form the query, if it is a raw query just place that in else go through
		// fields assigning the value
		// **
		if(isset($query['query']) && strlen($query['query']) > 0){
			$query_string .= $query['query'];
		}elseif(isset($query['select']) && strlen($query['select']) > 0){

			$query_array = array();
			foreach($index_attr['query_fields'] as $field){
				$query_array[] = $field;
			}
			$query_string .= '@('.implode(',', $query_array).') '.$query['select'];
		}

		//**
		// Now lets for the where up
		//**
		if(isset($query['where'])){ // then build the filter clause
			$query_array = array();
			foreach($query['where'] as $field => $values){

				$values_array = array();
				foreach($values as $value){
					$values_array[] = '"' . $value . '"';
				}
				$query_array[] = '@'.$field.' '.implode(' ', $values_array);
			}
			$query_string .= ' '.implode(' ', $query_array);
		}

		//**
		// Judge which path to take. If limit is installed then do that else do paging by default
		//**
		if(isset($query['limit']) || isset($this->limit)){
			$this->sphinx->SetLimits(0, isset($query['limit']) ? $query['limit'] : $this->limit); // Always get first page first.
			$result = $this->sphinx->Query($query_string, $index_string);

			$error = $this->sphinx->GetLastError();
//var_dump($result);
//exit();
			if(!$error){
				if($index_attr['cursor']){
					$this->matches = new $cursor(is_array($result['matches']) ? $result['matches'] : array());
				}else{
					$this->matches = $result['matches'];
				}
			}else{
				trigger_error($error);
			}
		}else{ // Lets just assume paging

			if(isset($query['results_per_page'])){
				$this->sphinx->SetLimits(0, $query['results_per_page']); // Always get first page first.
			}else{
				$this->sphinx->SetLimits(0, 20); // Always get first page first.
			}
			$first_page = $this->sphinx->Query($query_string, $index_string);
//var_dump($query_string); exit();
			$error = $this->sphinx->GetLastError();

			if(!$error){

				if(isset($first_page['matches']) && $first_page['total_found'] > 0){
					$this->total_found = $first_page['total_found'];
					$this->max_page = ceil($this->total_found/20);

					if($this->max_page <= 0) $this->max_page = 1;
					if($this->page > $this->max_page) $this->page = $this->max_page;
					if($this->page <= 0) $this->page = 1;

					if($this->page == 1){ // Then just respond with the original query.
						if($index_attr['cursor']){
							$this->matches = new $cursor(is_array($first_page['matches']) ? $first_page['matches'] : array());
						}else{
							$this->matches = $first_page['matches'];
						}
					}else{

						if($query['results_per_page']){
							$this->sphinx->SetLimits(($this->page-1)*$query['results_per_page'], $query['results_per_page']); // Testing first page
						}else{
							$this->sphinx->SetLimits(($this->page-1)*20, 20); // Testing first page
						}
						$res = $this->sphinx->Query($query_string, $index_string);

						if($res){
							if($index_attr['cursor']){
								$this->matches = new $cursor(is_array($res['matches']) ? $res['matches'] : array());
							}else{
								$this->matches = $res['matches'];
							}
						}else{
							trigger_error($this->sphinx->GetLastError());
							return $this->sphinx->GetLastError();
						}
					}
				}
			}else{
				trigger_error($error);
				return $error;
			}
		}
	}

 	function renderPager(){

 		//$this->max_page = 10;

		$start = $this->page - 5 > 0 ? $this->page - 5 : 1;
		$end = $this->page + 5 <= $this->max_page ? $this->page + 5 : $this->max_page;
		$ret = "";

		$url = glue::url()->get(true);

		$ret .= "<div class='GListView_Pager'>";

	    if($this->page != 1 && $this->max_page > 1) {
	        $ret .= '<div class="control"><a href="'.
	        	glue::url()->create($url['path'], array_merge($url['query'], array('page' => $this->page-1))).'">Previous</a></div>';
	    }

	    if($this->max_page > 1){
	    	$ret .= '<ul>';
		    for ($i = $start; $i <= $end && $i <= $this->max_page; $i++){

		        if($i==$this->page) {
		        	$ret .= '<li><div class="active" style="margin-right:6px;"><span>'.$i.'</span></div></li>';
		        } else {
		            $ret .= '<li><a style="margin-right:6px;" href="'.glue::url()->create($url['path'], array_merge($url['query'], array('page' => $i))).'"><span>'.$i.'</span></a></li>';
		        }
		    }
		    $ret .= '</ul>';
	    }

	    if($this->page < $this->max_page) {
	        $ret .= '<div class="control"><a href="'.glue::url()->create($url['path'], array_merge($url['query'], array('page' => $this->page+1))).'">Next</a></div>';
	    }

	    $ret .= "</div>";

	    return $ret;
	}
}