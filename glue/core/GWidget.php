<?php

/**
 * Main widget class for the handling and production of widgets allowing for a base template of accesible functions with which to effectively build
 * widgets with RAD in mind.
 *
 * This class provides basic functionality for widgets allowing for both normal none-active-record widgets but also allowing for fully fletched
 * active record controlled widgets producing and manipulating models.
 *
 * @author Sam Millman
 *
 */
abstract class GWidget{

	/**
	 * The model to attach to this (must be of GModel)
	 * @var GModel
	 */
	public $model;

	/**
	 * The model attribute to attach to this widget
	 * @var string
	 */
	public $attribute;

	/**
	 * Populates the class attributes
	 * @param array $a
	 */
	function attributes($a){
		if($a){
			foreach($a as $k=>$v){
				$this->{$k} = $v;
			}
		}
	}

	/**
	 * Much like beginWidget this function produces a widget however it will call both init() and render() all in one call
	 *
	 * @param string $path
	 * @param array $args
	 */
	function widget($path, $args = array()){
		return glue::widget($path, $args);
	}

	/**
	 * This function starts a widget by calling its init() function but does not call its render() function
	 *
	 * @param string $path
	 * @param array $args
	 */
	function beginWidget($path, $args = array()){
		return glue::beginWidget($path, $args);
	}

	/**
	 * Gets the name and ID of the model field you are currently working on
	 */
	function getAttributeNameId(){

		$model = $this->model;
		$attribute = $this->attribute;

		if($model instanceof GModel){ // If the model is a valid model
			if(($pos=strpos($attribute,'['))!==false)
			{
				if($pos!==0){  // e.g. name[a][b]
					$id = str_replace(']', '_', strtr(get_class($model).'['.substr($attribute,0,$pos).']'.substr($attribute,$pos),array(']['=>']','['=>']')));
					return array(get_class($model).'['.substr($attribute,0,$pos).']'.substr($attribute,$pos), $id);
				}
				if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
				{
					$sub=substr($attribute,0,$pos+1);
					$attribute=substr($attribute,$pos+1);
					$id = str_replace(']', '_', trim(strtr(get_class($model).$sub.'['.$attribute.']',array(']['=>']','['=>']'))));
					return array(get_class($model).$sub.'['.$attribute.']', $id);
				}
				if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
				{
					$id = str_replace('[', '_', get_class($model).'['.str_replace(']','_',trim(strtr($attribute,array(']['=>']','['=>']')),']')));
					$name=get_class($model).'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
					$attribute=$matches[1];

					return array($name, $id);
				}
			}
			else
				return array(get_class($model).'['.$attribute.']', str_replace('[', '_', trim(get_class($model).'['.$attribute.']', ']')));
		}
	}

	/**
	 * Allows for pre-processing and is not required
	 */
	function init(){ return true; } // Init function allows for pre-processing, it is not required

	/**
	 * Allows for a widget to be rendered and is required
	 */
	abstract function render(); // Render function MUST be implemented

}