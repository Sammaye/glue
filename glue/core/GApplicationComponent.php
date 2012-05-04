<?php
class GApplicationComponent{

	function init(){ return true; }

	function attributes($a){
		if($a){
			foreach($a as $k=>$v){
				$this->$k = $v;
			}
		}
	}

	function beforeControllerAction($controller, $action){ return true; }

	function afterControllerAction($controler, $action){ return true; }
}