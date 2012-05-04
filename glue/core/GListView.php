<?php

/**
 * GDbList Class
 *
 * This class is the Database driven equiviliant of a GComplexList class.
 * This class is used in conjunction with a sourced list to provide additional functions
 * over the normal list class such as pagination.
 *
 * @author Sam Millman
 *
 */
class GListView extends GWidget{

 	public $list = array();

 	public $sort = array();
 	public $sortColumns = array();

 	public $template = "{items}{pager}";
 	public $itemView = "";

	public $page = 1;
	public $elPerPage = 20;

	public $maxPage = 1;

	public $enableSorting = true;
	public $enablePaging = true;
	public $lockPage = false;

	public $count;
	public $count_elPage;

	public $additionalParams = array();

	public $ajaxPaging = false;
	public $ajaxPagingSettings = array();

	private $sortField;
	private $orderField;

	function pages(){
		return $this->maxPage;
	}

	function init(){
		$this->elPerPage = isset($_GET['elperpage']) ? $_GET['elperpage'] : $this->elPerPage;
		$this->page = isset($_GET['page']) && $_GET['page'] > 1 ? $_GET['page'] : $this->page;
		$this->sortField = $_GET['sfield'];
		$this->orderField = $_GET['ofield'];
	}

 	function __sortAttributes($key, $order){
		if($this->list){
			if($order == "asc"){
				$this->list->sort(array($key=>1));
			}else{
				$this->list->sort(array($key=>-1));
			}

			$this->sortField = $key;
			$this->orderField = $order;
		}
	}

	function get_url($morph = null){

		$_get = array_merge($moprh, array(
			"mode"=>urlencode($this->mode),
			"elperpage"=>urlencode($this->elPerPage),
			"page"=>urlencode($this->page),
			"sfield"=>urlencode($this->sortField),
			"ofield"=>urlencode($this->orderField))
		);

		glue::url()->query(array_merge($this->additionalParams, $_get), GUrlManager::QUERY_MERGE); // TODO make this query merge only return
		return glue::url()->get();
	}

	function render(){

		if($this->list){
			$this->count = $this->list->count();

			if($this->enableSorting && isset($this->sortColumns[$this->sortField])){
				if(array_key_exist($this->orderField, $this->sortColumns)){
					$this->__sortAttributes($this->sortField, $this->orderField);
				}else{
					$defaultSort = $this->sort($this->list);
					$this->__sortAttributes($defaultSort[0], $defaultSort[1]);
				}
			}elseif(is_callable($fn)){
				$fn = $this->sort;
				$defaultSort = $fn($this->list);
				$this->__sortAttributes($defaultSort[0], $defaultSort[1]);
			}

			// Get the current page
			if($this->enablePaging){
				// Double check current page and make amendmants if needed
				$this->maxPage = ceil($this->count / $this->elPerPage) < 1 ? 1 : ceil(($this->count) / $this->elPerPage);
				if($this->page < 0 || $this->maxPage < 0){
					$this->maxPage = 1;
					$this->page = 1;
				}

				if($this->page > $this->maxPage) $this->page = $this->maxPage;
				$this->list = $this->list->skip(($this->page-1)*$this->elPerPage)->limit($this->elPerPage);
			}
		}

		$pager = $this->__renderPager();

		ob_start();
			$this->__renderItems();
			$items = ob_get_contents();
		ob_end_clean();

		$html = preg_replace("/{pager}/", $pager, $this->template);
		$html = preg_replace("/{items}/", $items, $html);

		echo $html;
	}

 	function __renderPager(){

 		//$this->max_page = 10;

		$start = $this->page - 5 > 0 ? $this->page - 5 : 1;
		$end = $this->page + 5 <= $this->maxPage ? $this->page + 5 : $this->maxPage;
		$ret = "";

		$url = glue::url()->get(true);

		$ret .= "<div class='GListView_Pager'>";


	    if($this->page != 1 && $this->maxPage > 1) {
	    	if($this->ajaxPaging){
	        	$ret .= '<div class="control"><a href="#page_'.($this->page-1).'">Previous</a></div>';
	    	}else{
	        	$ret .= '<div class="control"><a href="'.
	        		glue::url()->create($url['path'], array_merge($url['query'], array('page' => $this->page-1))).'">Previous</a></div>';
	    	}
	    }

	    if($this->maxPage > 1){
	    	$ret .= '<ul>';
		    for ($i = $start; $i <= $end && $i <= $this->maxPage; $i++){

		        if($i==$this->page) {
		        	$ret .= '<li><div class="active" data-page="'.$i.'" style="margin-right:6px;"><span>'.$i.'</span></div></li>';
		        } else {
		        	if($this->ajaxPaging){
		            	$ret .= '<li><a style="margin-right:6px;" href="#page_'.($i).'"><span>'.$i.'</span></a></li>';
		        	}else{
		            	$ret .= '<li><a style="margin-right:6px;" href="'.
		            		glue::url()->create($url['path'], array_merge($url['query'], array('page' => $i))).'"><span>'.$i.'</span></a></li>';
		        	}
		        }
		    }
		    $ret .= '</ul>';
	    }

	    if($this->page < $this->maxPage) {
	    	if($this->ajaxPaging){
				$ret .= '<div class="control"><a href="#page_'.($this->page+1).'">Next</a></div>';
	    	}else{
				$ret .= '<div class="control"><a href="'.glue::url()->create($url['path'], array_merge($url['query'], array('page' => $this->page+1))).'">Next</a></div>';
	    	}
	    }

	    $ret .= "</div>";

	    return $ret;
	}

	function __renderItems(){
		$i = 0;

		if($this->additionalParams){
			foreach($this->additionalParams as $k=>$v){
				$$k = $v;
			}
		}

		foreach($this->list as $_id => $item){
			if(is_string($this->itemView)){ // Is it a file location?
				ob_start();
					include ROOT.'/application/views/'.$this->itemView;
					$item = ob_get_contents();
				ob_end_clean();

				echo $item;
			}else{
				echo $this->itemView($_id, $item);
			}
			$i++;
		}
	}

  	function __toJSON(){

 		$list = array();
 		foreach($this->list as $_id => $listItem){
 			$list[$_id] = $listItem;
 		}
		return json_encode($list);
	}

	function __toArray(){
		return $this->list;
	}

	function end(){}
}