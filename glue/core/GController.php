<?php

class GController {

	public $layout = "blank_page";
	public $pageTitle;
	public $pageDescription;
	public $pageKeywords;

	public function filters(){ return array(); }

	function addCssFile($map, $path){
		glue::clientScript()->addCssFile($map, $path);
	}

	function addCssScript($map, $script){
		glue::clientScript()->addCssScript($map, $script);
	}

	function addJsFile($map, $script){
		glue::clientScript()->addJsFile($map, $script);
	}

	function addJsScript($map, $script){
		glue::clientScript()->addJsScript($map, $script);
	}

	function addHeadTag($html){
		glue::clientScript()->addTag($html, GClientScript::HEAD);
	}

	function widget($path, $args = array()){
		return glue::widget($path, $args);
	}

	function beginWidget($path, $args = array()){
		return glue::beginWidget($path, $args);
	}

	function accessRules(){ return array(); }

	function render($page, $args = null){

		if(isset($args['page']) || isset($args['args'])) throw new Exception("The \$page and \$args variables are reserved variables within the render function.");

		if($args){
			foreach($args as $k=>$v){
				$$k = $v;
			}
		}

		if(!$this->pageTitle){
			$this->pageTitle = glue::config('pageTitle');
		}

		if(!$this->pageDescription){
			$this->pageDescription = glue::config('pageDescription');
		}

		if(!$this->pageKeywords){
			$this->pageKeywords = glue::config('pageKeywords');
		}

		ob_start();
			include ROOT.'/application/views/'.str_replace(".", "/", $page).'.php';
			$page = ob_get_contents();
		ob_clean();
	//	echo $page;
//echo "hewre";
		ob_start();
			include_once ROOT.'/application/layouts/'.$this->layout.'.php';
			$layout = ob_get_contents();
		ob_clean();

		$layout = glue::clientScript()->renderHead($layout);
		$layout = glue::clientScript()->renderBodyEnd($layout);
		echo $layout;
	}

	function partialRender($page, $args = null){

		if($args){
			foreach($args as $k=>$v){
				$$k = $v;
			}
		}

		include ROOT.'/application/views/'.str_replace(".", "/", $page).'.php';
	}
}
