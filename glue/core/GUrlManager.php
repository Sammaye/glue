<?php
class GUrlManager{

	const QUERY_MERGE = 2;
	const QUERY_OVERWRITE = 8;

	private $_url;

	public $_referer;

	public function __construct(){}

	public function create($path, $attr = array(), $useLit = false, $base = ''){
		$params = array();

		foreach($attr as $k=>$v){
			$params[] = urlencode($k).'='.urlencode($v);
		}

		if(count($params) > 0){
			if($useLit)
				$query_path = $path."?".implode("&", $params);
			else
				$query_path = $path."?".implode("&amp;", $params);
		}else{
			$query_path = $path;
		}

		if(strlen($base) > 0){
			return $base.$query_path;
		}else{
			return glue::$params['rootUrl'].$query_path;
		}
	}

	public function createSSL($path, $attr = array()){
		return "https://".$this->create($path, $attr);
	}

	public function path($path = null){
		if($path) $this->_url['path'] = $path;
		return $this->_url['path'];
	}

	public function query($attr = array(), $method = self::QUERY_MERGE){
		if(($method&self::QUERY_MERGE) && !empty($attr)){
			$this->_url['query'] = array_merge($this->_url['query'], $attr);
		}elseif(!empty($attr)){
			$this->_url['query'] = $attr;
		}

		if($this->_url['query']){
			$_get = array();
			foreach($this->_url['query'] as $field => $value){
				$_get[] = $field.'='.$value;
			}
			return implode("&amp;", $_get);
		}else
			return '';
	}

	function get_merge($attrs = array(), $path = null){
		//var_dump(glue::url()->get());

		$_s_get = array_merge($this->_url['query'], $attrs);
		foreach($_s_get as $field => $value){
			$_get[] = $field.'='.$value;
		}

		if($path){
			return $path.'?'.implode("&amp;", $_get);
		}
		return $this->path().'?'.implode("&amp;", $_get);
	}

	public function set($url){
		if(is_array($url)){
			$this->_url = $url;
			return $this->_url;
		}elseif(is_string($url)){
			$url_p = parse_url($url);
			$params = count($url_p['query']) > 0 ? explode("&", $url_p['query']) : null;

			$query = array();
			if($params){
				foreach($params as $value){
					$params = explode('=', $value);
					$query[$params[0]] = $params[1];
				}
			}

			$this->_url = array(
				"path"=>$url_p['path'],
				"query"=>$query,
				"fragment"=>$url_p['fragment']
			);
			return $this->_url;
		}
		return false;
	}

	public function get($returnObj = false){
		if($returnObj){
			return $this->_url;
		}

		$query = $this->query();
		if(strlen($query) > 0){
			return $this->path()."?".$this->query();
		}else{
			return $this->path();
		}
	}

	public function redirect($url, $attr = array()){
		header("Location: ".$this->create($url, $attr, true));
		exit();
	}

	public function method(){
		if(count($_GET) > 0){
			return "GET";
		}elseif(count($_POST) > 0){
			return "POST";
		}
		return false;
	}

	public function getNormalisedReferer(){
		$referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
		if($referer === false){
			return null;
		}

		if(strlen($referer) > 0){
			return $referer;
		}else{
			return null;
		}
	}
}