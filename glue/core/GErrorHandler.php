<?php
/**
 * Default Error Handler
 *
 * @author Sam Millman
 *
 * This file concerns itself with error handling only.
 * It is the functions that produce and display error
 * messages for user friendly debugging, or in the case
 * of a production environment, error messaging.
 *
 * The variables in this file could have been placed into
 * the main ini file but then errors could occur there too
 * so I have made this kinda free standing.
 *
 */

/** Main error handler function. Call this on set_error_handler **/
function GErrorHandler($errno, $errstr='', $errfile='', $errline=''){

	$handlers = ob_list_handlers();
	while ( ! empty($handlers) )    {
		ob_end_clean();
		$handlers = ob_list_handlers();
	}

	$attributes = Glue::config("errorHandler", 'components');

	$CorePHP_errorTypes = array (
	E_ERROR              => 'ERROR',
	E_WARNING            => 'WARNING',
	E_PARSE              => 'PARSING ERROR',
	E_NOTICE             => 'NOTICE',
	E_CORE_ERROR         => 'CORE ERROR',
	E_CORE_WARNING       => 'CORE WARNING',
	E_COMPILE_ERROR      => 'COMPILE ERROR',
	E_COMPILE_WARNING    => 'COMPILE WARNING',
	E_USER_ERROR         => 'USER ERROR',
	E_USER_WARNING       => 'USER WARNING',
	E_USER_NOTICE        => 'USER NOTICE',
	E_STRICT             => 'STRICT NOTICE',
	E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR'
	);

	if (error_reporting() == 0) {

		/** Error has been surpress via an @ **/
		return;
	}

	/**
	 * Was this function called by an exception?
	 *
	 * Shouldn't be! Exceptions are costly!
	 */
	if(func_num_args() == 5) {

		// called by trigger_error()
		$exception = null;
		list($errno, $errstr, $errfile, $errline) = func_get_args();

		$backtrace = array_reverse(debug_backtrace());

	}else {

		// caught exception
		$exc = func_get_arg(0);
		$errno = $exc->getCode();
		$errstr = $exc->getMessage();
		$errfile = $exc->getFile();
		$errline = $exc->getLine();

		$backtrace = $exc->getTrace();
	}

	if (array_key_exists($errno, $CorePHP_errorTypes)) {

		/** It is a predefined error triggered by PHP **/
		$err = $CorePHP_errorTypes[$errno];
	} else {

		/** It is a exception **/
		$err = 'CAUGHT EXCEPTION';
	}

	/** Create Error Message **/
	$errMsg = "$err: $errstr in $errfile on line $errline";

	/** Start backtrace **/
	$i = 0;

	foreach ($backtrace as $v) {

		/**
		 * SECURITY FIX
		 *
		 * If someone malicous is able to generate a error
		 * in excess of 100 arguments the error handler itself
		 * will become erranous leaving the site open for spying
		 * An attacker could easily use this to understand whether or not
		 * your site porperly protected.
		 *
		 * To protect the script from amlicous use I have limited the amount
		 * of arguments to 40.
		 *
		 * "Why do a backtrace in production?" So we can see if any errors
		 * occur before we lose visitors and business. These errors will
		 * be emailed to admins under production scenarios.
		 */
		if($i < 40){

			if (isset($v['class'])) {

				$trace = 'in class '.$v['class'].'::'.$v['function'].'(';

				if (isset($v['args'])) {
					$separator = '';

					foreach($v['args'] as $arg ) {
						$trace .= "$separator".getArgument($arg);
						$separator = ', ';
					}
				}
				$trace .= ')';
			}elseif (isset($v['function']) && empty($trace)) {
				$trace = 'in function '.$v['function'].'(';

				if (!empty($v['args'])) {

					$separator = '';

					foreach($v['args'] as $arg ) {
						$trace .= "$separator".getArgument($arg);
						$separator = ', ';

					}
				}
				$trace .= ')';
			}

			$i++;
		}else{
			break;
		}
	}

	$backtracel = '';
	foreach(debug_backtrace() as $k=>$v){
		if($v['function'] == "include" || $v['function'] == "include_once" || $v['function'] == "require_once" || $v['function'] == "require"){
			$backtracel .= "#".$k." ".$v['function']."(".$v['args'][0].") called at [".$v['file'].":".$v['line']."]<br />";
		}else{
			$backtracel .= "#".$k." ".$v['function']."() called at [".$v['file'].":".$v['line']."]<br />";
		}
	}

	/** Now lets form the message **/
	$errorText = '<h2>Debug Msg</h2>
    				<p>'.nl2br($errMsg).'</p>
    				<p>Trace: '.nl2br($trace).'</p>
    				<p>Back Trace:</p><p>'.$backtracel.'</p>
    				<p>On: '.Glue::url()->get().'</p>';

    if($errstr == "(SQL)"){
    	$errorText .= '<h2>SQL Trace</h2>
    				  <p>File: '.SQLERRORFILE.'</p>
    				  <p>Line: '.SQLERRORLINE.'</p>
    				  <p>Query: '.SQLQUERY.'</p>
    				  <p>Response: '.SQLMESSAGE.'</p>';
    }

	/**
	 * How to process error codes
	 *
	 * You can use this to add your own error code
	 * handling simply add the case and a constant
	 * to the loop to set a new error type such as SQL
	 */
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
		default:

			if(array_key_exists("email", array_flip($attributes['output']))){
				foreach($attributes['emailAddresses'] as $k=>$v){
					mail($v, 'Critical error of type '.$err, $errorText, 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset=iso-8859-1'."\r\n");
				}
			}

			if(!Glue::config("DEBUG")){
				glue::route(glue::config("*", "errorPages"));
			}else{
				if(array_key_exists("screen", array_flip($attributes['output']))){
					if(glue::isAjax()){
						header("HTTP/1.1 500 Internal Server Error");
						echo $errorText;
					}else{
						echo $errorText;
					}
				}
			}
			exit();
			break;
	}

} // end of errorHandler()

/**
 * Get argument list for the error
 *
 * WARNING: There is a serious bug with this normally
 * Once an argument list reaches 100 elements it hits the PHP
 * maximum at which point it will break itself.
 *
 * To stop this a counter has been added to the loop where this is used
 * that stops the argument list at a max of 40. This includes nested
 * argument lists so if you have an argument top level of 1 with 39
 * arugment rows to it the script will still break and stop a second
 * level from showing.
 *
 * @param mixed $arg
 * @return string $argument_list
 */
function getArgument($arg){

	switch (strtolower(gettype($arg))) {

		case 'string':
			return( '"'.str_replace( array("\n"), array(''), $arg ).'"' );

		case 'boolean':
			return (bool)$arg;

		case 'object':
			return 'object('.get_class($arg).')';

		case 'array':
			return $arg;


		case 'resource':
			return 'resource('.get_resource_type($arg).')';

		default:
			return var_export($arg, true);
	}
}

function shutdown() {
    $isError = false;

    if ($error = error_get_last()){
    	switch($error['type']){
        	case E_ERROR:
        	case E_CORE_ERROR:
        	case E_COMPILE_ERROR:
        	case E_USER_ERROR:
            	$isError = true;
            	break;
        }
    }

    if ($isError){

    	$text = "<h1>Fatal Error</h1>
    		<p>File: ".$error['file']."</p>
    		<p>On Line: ".$error['line']."</p>
    		<p>Output Message: ".$error['message']."</p>
    		<p>On: ".$_SERVER['REQUEST_URI']."</p>
    		<h1>Backtrace</h1>
    		".printBacktrace();

    	if(array_key_exists("email", array_flip($attributes['output']))){
			foreach($attributes['emailAddresses'] as $k=>$v){
				mail($v, "FATAL ERROR HAS OCCURRED", $text, 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset=iso-8859-1'."\r\n");
			}
		}

    	if(!Glue::config("DEBUG")){
			glue::route(glue::config("*", "errorPages"));
		}else{
			if(array_key_exists("screen", array_flip($attributes['output']))){
				if(glue::isAjax()){
					header("HTTP/1.1 500 Internal Server Error");
					echo $text;
				}else{
					echo $text;
				}
			}
		}
    }
}