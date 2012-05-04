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

/** Run the framework */
Glue::setConfigFile('application/core/config.php');
Glue::run($_GET['url']);


/** EOF **/