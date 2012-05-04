<?php
setlocale(LC_ALL, 'en_GB.UTF8');

class Glue{

	public static $params = array();

	public static $action;

	private static $_classMapper = array(
		"GClientScript"			=> "glue/core/GClientScript.php",
		"GCommon" 				=> "glue/core/GCommon.php",
		"GCommandLine"			=> "glue/core/GCommandLine.php",
		"GController"			=> "glue/core/GController.php",
		"GErrorHandler" 		=> "glue/core/GErrorHandler.php",
		"GListProvider"			=> "glue/core/GListProvider.php",
		"GListView"				=> "glue/core/GListView.php",
		"GModel"				=> "glue/core/GModel.php",
		"GUrlManager"			=> "glue/core/GUrlManager.php",
		"GWidget" 				=> "glue/core/GWidget.php",
		"GApplicationComponent" => 'glue/core/GApplicationComponent.php',
		"GJSON" 				=> 'glue/core/GJSON.php',
		"html"					=> "glue/core/html.php",
	);

	private static $_components = array();
	private static $_componentLoaded = array();
	private static $_classLoaded = array();
	private static $_configVars;
	private static $_url;
	private static $_GRBAM;
	private static $_error;
	private static $_clientScript;

	public static function __callStatic($name, $arguments){
		$compConfig = self::config($name, "components");

		if(!isset($compConfig) && !$compConfig){ // Then lets try the alias
			$name = self::config($name, "alias");
			$compConfig = self::config($name, "components");
		}

		if(isset($compConfig) && $compConfig){ // If is still unset then go to error clause
			if(isset(self::$_components[$name])){
				return self::$_components[$name];
			}else{
				self::import($compConfig['class'], $compConfig['path']);
				$o = new $compConfig['class'];
				unset($compConfig['class']);
				unset($compConfig['path']);

				foreach($compConfig as $k => $v){
					$o->$k = $v;
				}
				$o->init();
				return self::$_components[$name] = $o;
			}
		}else{
			trigger_error("The component or variable or alias of a variable or plugin (".$name.") in the glue class could not be found");
		}
	}

	public static function cli_run(){
		self::registerAutoloader();
		self::import("GErrorHandler");
		self::import("GCommon");

		set_error_handler("GErrorHandler", E_ALL & ~E_NOTICE);
		set_exception_handler("GErrorHandler"); // Exceptions are costly beware!
		register_shutdown_function('shutdown');

		//PRELOAD
		foreach(self::config('preload') as $k => $path){
			self::import($path);
		}

		foreach(self::config('params') as $k => $v){
			self::$params[$k] = $v;
		}
		//self::session()->start(); // SESSION INERT IN CLI
	}

	/**
	 * Main Call Function
	 *
	 * @param string $url This is the url defined within the address bar which translates down to $_GET['url']
	 */
	public static function run($url){

		self::registerAutoloader();
		self::import("GErrorHandler");
		self::import("GCommon");

		set_error_handler("GErrorHandler", E_ALL & ~E_NOTICE);
		set_exception_handler("GErrorHandler"); // Exceptions are costly beware!
		register_shutdown_function('shutdown');

		//PRELOAD
		self::url()->set($_SERVER['REQUEST_URI']);
		foreach(self::config('preload') as $k => $path){
			self::import($path);
		}

		foreach(self::config('params') as $k => $v){
			self::$params[$k] = $v;
		}
		self::session()->start();

		$url = (empty($url)) || ($url == "/") ? 'index' : $url;
		self::route($url);
	}

	/**
	 * Routes a url segment to a controller and displays that controller action
	 *
	 * @param string $route
	 */
	public static function route($route = null){
		if(!$route){
			trigger_error("You cannot get no controller. You must supply a controller to get in the params.", E_USER_ERROR);
			exit();
		}

		$route = (empty($route)) || ($route == "/") ? 'index' : $route;

		/** Explode the url so we can analyse it */
		$urlParts = array_merge(array_filter(explode('/', $route)));

		/** Define the controller name as a variable to stop ambiquity within PHP */
		$controller_name = $urlParts[0]."Controller";

		/** Lets get the controller path ready. */
		$controllerFile = ROOT.'/application/controllers/'.$urlParts[0].'Controller.php';

		/** Lets see if an action is defined */
		$action = isset($urlParts[1]) && (string)$urlParts[1] ? $urlParts[1] : "";

		if(!isset($urlParts[1]))
			$action = 'index';

		/** Does the page exist? */
		if(!file_exists($controllerFile)){
			if(!file_exists(ROOT.'/application/controllers/'.ucfirst($urlParts[0]).'Controller.php')){
				self::route(self::config("404", "errorPages"));
			}else{
				$controllerFile = ROOT.'/application/controllers/'.ucfirst($urlParts[0]).'Controller.php';
			}
		}

		/** So lets load the controller now that it exists */
		include_once $controllerFile;
		if(is_callable(array($controller_name, 'action_'.$action))){
			$action = 'action_'.$action;
		}else{
			self::route(self::config("404", "errorPages"));
		}

		/** run the action */
		$controller = new $controller_name();

		$reflector = new ReflectionClass($controller_name);
		$method = $reflector->getMethod($action);
		self::$action = array('controller' => $method->class, 'name' => str_replace('action_', '', $method->name), 'actionID' => $method->name,  'params' => $method->getParameters());

		// Now run the filters for the controller
		$filters = is_array($controller->filters()) ? $controller->filters() : array();
		$runAction = true;
		foreach($filters as $k => $v){
			$runAction = glue::$v()->beforeControllerAction($controller, self::$action) && $runAction;
		}

		if($runAction)
			$controller->$action();

		foreach($filters as $k => $v){
			if(!is_numeric($k)){
				glue::$v()->afterControllerAction($controller, self::$action);
			}
		}
		exit(); // Finished rendering exit now
	}

	/**
	 * Imports a single file or directory into the app
	 *
	 * @param string $cName
	 * @param string $cPath
	 */
	public static function import($cName, $cPath = null){

		if(substr($cName, -2) == "/*"){

			$d_name = ROOT.'/'.substr($cName, 0, -2);
			$d_files = getDirectoryFileList($d_name, array("\.php")); // Currently only accepts .php

			foreach($d_files as $file){
				self::$_classMapper[strstr($file, '.', true)] = substr($cName, 0, -2).'/'.$file;
			}
		}else{
			if(!$cPath) $cPath = self::$_classMapper[$cName];

			if(!isset(self::$_classLoaded[$cName])){
				self::$_classLoaded[$cName] = true;
				if($cPath[0] == "/" || preg_match("/^application/i", $cPath) > 0 || preg_match("/^glue/i", $cPath) > 0){
					return include ROOT.'/'.$cPath;
				}else{
					if(!$cPath){
						//echo $cName;
						//var_dump($cPath);
						//exit();
					}else{
						return include $cPath;
					}
				}
			}
			return true;
		}
	}

	public static function registerAutoloader($callback = null){
		spl_autoload_unregister(array('Glue','import'));
		if($callback) spl_autoload_register($callback);
		spl_autoload_register(array('Glue','import'));
	}

	/**
	 * Sets the current configuration values
	 *
	 * @param string $path
	 */
	public static function setConfigFile($path){
		self::$_configVars = self::import('config', $path);
	}

	/**
	 * Gets a top level configuration variable
	 *
	 * @param string $key
	 * @param string $section
	 */
	public static function config($key = null, $section = null){
		if(!$key && !$section){
			return self::$_configVars;
		}elseif($key && !$section){
			return self::$_configVars[$key];
		}elseif($key && $section){
			return isset(self::$_configVars[$section][$key]) ? self::$_configVars[$section][$key] : null;
		}
	}

	/**
	 * Returns the url manager for the app
	 */
	public static function url(){
		if(!self::$_url) self::$_url = new GUrlManager();
		return self::$_url;
	}

	/**
	 * Returns the client script object for the app
	 */
	public static function clientScript(){
		if(!self::$_clientScript instanceof GClientScript)
			self::$_clientScript = new GClientScript();
		return self::$_clientScript;
	}

	/**
	 * Sends a email to the specified address with the template and variables and headers.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $template
	 * @param array $variables
	 * @param string $headers
	 */
	public static function sendMail($to, $subject, $template, $variables, $headers = null){

		foreach ($variables as $key => $value){
        	$$key = $value;
        }

        if($headers){
        	$headers .= "\r\n";
        }

		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		ob_start();
		include ROOT.'/'.$template;
		$pagecontent=ob_get_contents();
        ob_end_clean();

		if(mail($to, $subject, $pagecontent, $headers)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Returns true if the HTTP headers denote an AJAX call
	 */
	public static function isAjax() {
    	return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest");
	}

	/**
	 * Checks if there is a flash message in session of the index $flash
	 *
	 * @param string $flash
	 */
	public static function hasFlash(){
		if(isset($_SESSION['flash_message']))
			return true;
	}

	/**
	 * Adds a error flash to the session
	 *
	 * @param string $i
	 * @param string $message
	 */
	public static function errorFlash($message){
		$_SESSION['flash_message'] = array($message, "MOTD_ERROR");
	}

	/**
	 * Adds a success type flash to the session
	 *
	 * @param string $i
	 * @param string $message
	 */
	public static function successFlash($message){
		$_SESSION['flash_message'] = array($message, "MOTD_SUCCESS");
	}

	/**
	 * Displays the flash message stored in session
	 *
	 * @param string $flash
	 */
	public static function getFlash(){
		$session_flash = $_SESSION['flash_message'];

		$message = $session_flash[0] ? $session_flash[0] : $message;
		$class = $session_flash[1] ? $session_flash[1] : $class;

		$html .= html::openTag('div', array('class' => 'MOTD '.$class));
		$html .= html::openTag('div', array('class' => 'MOTD_message')).$message.html::closeTag('div');

		$html .= html::openTag('div', array('class' => 'MOTD_action')).html::openTag('a', array('href' => glue::url()->get()));
		$html .= html::img(array('src' => '/images/close_16.png', 'alt' => 'close'));
		$html .= html::closeTag('a').html::closeTag('div');
		$html .= html::open_closeTag(array('class' => 'clearer'));

		$html .= html::closeTag('div');

		unset($_SESSION['flash_message']);

		return $html;
	}

	/**
	 * Starts a widget but does not run the render() function
	 *
	 * @param string $path
	 * @param array $args
	 */
	public static function beginWidget($path, $args = null){
		$pieces = explode("/", $path);
		$cName = substr($pieces[sizeof($pieces)-1], 0, strrpos($pieces[sizeof($pieces)-1], "."));
		Glue::import($cName, $path);
		$widget = new $cName();
		$widget->attributes($args);
		$widget->init();
		return $widget;
	}

	/**
	 * Starts a widget and runs the render() function of a widget
	 *
	 * @param string $path
	 * @param array $params
	 */
	public static function widget($path, $params = null){
		$widget = self::beginWidget($path, $params);
		return $widget->render();
	}
}