<?php

class GJSON{

	const DENIED = 1;
	const LOGIN = 2;
	const UNKNOWN = 3;

	static function kill($params, $success = false){
		if(!$success)
			echo self::error($params);
		else
			echo self::success($params);
		exit();
	}

	static function success($params){
		if(is_string($params)){
			return json_encode(array('success' => true, 'messages' => array($params)));
		}else{
			return json_encode(array_merge(array('success' => true), $params));
		}
	}

	static function error($params){
		switch(true){
			case $params == self::DENIED:
				return json_encode(array('success' => false, 'messages' => array('Action not Permitted')));
				break;
			case $params == self::LOGIN:
				return json_encode(array('success' => false, 'messages' => array('You must login to continue')));
				break;
			case $params == self::UNKNOWN:
				return json_encode(array('success' => false, 'messages' => array('An unknown error was encountered')));
				break;
			default:
				if(is_string($params)){
					return json_encode(array('success' => false, 'messages' => array($params)));
				}else{
					return json_encode(array_merge(array('success' => false), $params));
				}
				break;
		}
	}
}