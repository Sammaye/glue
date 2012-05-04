<?php
/**
 * DOCUMENT ROOT Constant
 *
 * Defines the root of the website.
 * This saves us from having to use insecure header
 * variables to understand where the root is.
 */
define('ROOT', dirname(__FILE__));

/** Include the main point of entry */
include ROOT."/glue/glue.php";
include ROOT.'/glue/core/GCommandLine.php';

/** Run the framework */
Glue::setConfigFile('application/core/config.php');
GCommandLine::parseArgs($_SERVER['argv']);
Glue::cli_run();

include ROOT.'/application/cronjob/'.GCommandLine::$args['path'];
/** EOF **/