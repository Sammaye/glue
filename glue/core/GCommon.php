<?php

function init_mongo_id_array($a){
	$ret = array();
	foreach($a as $k=>$v){
		$ret[$k] = new MongoId($v);
	}
	return $ret;
}

function isTimestamp( $string ) {
    return ( 1 === preg_match( '/^[1-9][0-9]*$/', $string ) );
}

function convert_size_human($size){
	$unit=array('','KB','MB','GB','TB','PB');
	$byte_size = $size/pow(1024,($i=floor(log($size,1024))));

	if(is_really_int($byte_size)){
		return $byte_size.' '.$unit[$i];
	}else{
		preg_match('/^[0-9]+\.[0-9]{2}/', $byte_size, $matches);
		return $matches[0].' '.$unit[$i];
	}
}

function compressFile($buffer) {
	/* remove comments */
	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	/* remove tabs, spaces, newlines, etc. */
	$buffer = preg_replace('/(?:\s\s+|\n|\t)/', '', $buffer);
	return $buffer;
}

function getDirectoryFileList($directory, $exts = array()){
	$files = array();

	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if(count($exts) <= 0){
					$files[] = $file;
				}else{
					foreach($exts as $extension){
						if(preg_match("/".$extension."/i", $file) > 0){
							$files[] = $file;
						}
					}
				}
			}
		}
		closedir($handle);
	}
	return $files;
}

function get_ua_browser(){
	$u_agent = $_SERVER['HTTP_USER_AGENT'];
	$u_brows_key = 'u';
	if(preg_match('/MSIE/i',$u_agent)){
		$u_brows_key = "ie";
	}elseif(preg_match('/Firefox/i',$u_agent)){
		$u_brows_key = "ff";
	}elseif(preg_match('/Chrome/i',$u_agent)){
		$u_brows_key = "chrome";
	}elseif(preg_match('/Safari/i',$u_agent)){
		$u_brows_key = "safari";
	}elseif(preg_match('/Opera/i',$u_agent)){
		$u_brows_key = "opera";
	}elseif(preg_match('/Netscape/i',$u_agent)){
		$u_brows_key = "netscape";
	}
	return $u_brows_key;
}

function summarise_array_row($new_array, $old_array){
	$ret = array();
	foreach($old_array as $k=>$v){
		$ret[$k] = $v+$new_array[$k];
		unset($new_array[$k]);
	}

	if(!is_array($new_array))
		$new_array = array();

	$ret = array_merge($ret, $new_array);
	return $ret;
}

/**
 * Generate a new password
 *
 * @author Sam Millman
 *
 * This function will make a new password for anyone who asks.
 * This function is not always used for user passwords and can be used
 * for anything that requires a relatively secure combination of numbers, letters
 * and symbols for either encryption or hashing.
 */
function generate_new_pass(){

	$length=9; // Length of the returned password
	$strength=8; // A strength denominator

	$vowels = 'aeuy'; // Vowels to use
	$consonants = 'bdghjmnpqrstvz'; // Consonants to use

	// Repitition throughout the strengths to make the new password
	if ($strength & 1) {
		$consonants .= 'BDGHJLMNPQRSTVWXZ';
	}
	if ($strength & 2) {
		$vowels .= "AEUY";
	}
	if ($strength & 4) {
		$consonants .= '23456789';
	}
	if ($strength & 8) {
		$consonants .= '@#$%';
	}

	// Randomise the placement of text entities
	$password = '';
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {
		if ($alt == 1) {
			$password .= $consonants[(rand() % strlen($consonants))];
			$alt = 0;
		} else {
			$password .= $vowels[(rand() % strlen($vowels))];
			$alt = 1;
		}
	}

	// Return the password
	return $password;
}

function generateSalt(){
	return substr(md5(uniqid(rand(), true)), 0, 32);
}

/**
 * Strips all from string including symbols and punctuation
 *
 * @param $blurb
 */
function strip_all($blurb){

	$blurb = stripslashes(strip_tags($blurb));

	$blurb = preg_replace('/<[^>]*>/', '', $blurb);

	$blurb = trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $blurb));

	$blurb = preg_replace("/[^a-zA-Z0-9\s]/", "", $blurb);

	return $blurb;
}

function make_alpha_numeric($blurb){
	$blurb = preg_replace("/[^a-zA-Z0-9\s]/", "", $blurb);
	return $blurb;
}

function stripTags_whitespace($blurb){
	$blurb = stripslashes(strip_tags($blurb));

	$blurb = preg_replace('/<[^>]*>/', '', $blurb);

	$blurb = trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $blurb));

	return $blurb;
}

function strip_whitespace($str){
	return trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $str));
}

function strip_to_single($str){
	return trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $str));
}

function truncate_string($title_string, $truncate_after_nr_chars = 50){

	$nr_of_chars = strlen($title_string);
	if($nr_of_chars >= $truncate_after_nr_chars){
		$title_string = substr_replace( $title_string, "...", $truncate_after_nr_chars, $nr_of_chars - $truncate_after_nr_chars);
	}
	return $title_string;
}

function formUrlSegment($blurb){
	return str_replace(" ", ".", strip_all(strtolower($blurb)));
}

function concat() {
	$vars=func_get_args();
	$array=array();
	foreach ($vars as $var) {
		if (is_array($var)) {
			foreach ($var as $val) {$array[]=$val;}
		} else {
			$array[]=$var;
		}
	}
	return $array;
}

// Read a file and display its content chunk by chunk
function readfile_chunked($filename, $retbytes = TRUE) {
	$buffer = '';
	$cnt =0;
	// $handle = fopen($filename, 'rb');
	$handle = fopen($filename, 'rb');
	if ($handle === false) {
		return false;
	}
	while (!feof($handle)) {
		$buffer = fread($handle, 1024*1024);
		echo $buffer;
		ob_flush();
		flush();
		if ($retbytes) {
			$cnt += strlen($buffer);
		}
	}
	$status = fclose($handle);
	if ($retbytes && $status) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}
	return $status;
}

function ago($datefrom,$dateto=-1)
{
	// Defaults and assume if 0 is passed in that
	// its an error rather than the epoch

	if($datefrom==0) { return "A long time ago"; }
	if($dateto==-1) { $dateto = time(); }

	// Make the entered date into Unix timestamp from MySQL datetime field

	$datefrom = $datefrom;

	// Calculate the difference in seconds betweeen
	// the two timestamps

	$difference = $dateto - $datefrom;

	// Based on the interval, determine the
	// number of units between the two dates
	// From this point on, you would be hard
	// pushed telling the difference between
	// this function and DateDiff. If the $datediff
	// returned is 1, be sure to return the singular
	// of the unit, e.g. 'day' rather 'days'

	switch(true)
	{
		// If difference is less than 60 seconds,
		// seconds is a good interval of choice
		case(strtotime('-1 min', $dateto) < $datefrom):
			$datediff = $difference;
			$res = ($datediff==1) ? $datediff.' second ago' : $datediff.' seconds ago';
			break;
			// If difference is between 60 seconds and
			// 60 minutes, minutes is a good interval
		case(strtotime('-1 hour', $dateto) < $datefrom):
			$datediff = floor($difference / 60);
			$res = ($datediff==1) ? $datediff.' minute ago' : $datediff.' minutes ago';
			break;
			// If difference is between 1 hour and 24 hours
			// hours is a good interval
		case(strtotime('-1 day', $dateto) < $datefrom):
			$datediff = floor($difference / 60 / 60);
			$res = ($datediff==1) ? $datediff.' hour ago' : $datediff.' hours ago';
			break;
			// If difference is between 1 day and 7 days
			// days is a good interval
		case(strtotime('-1 week', $dateto) < $datefrom):
			$day_difference = 1;
			while (strtotime('-'.$day_difference.' day', $dateto) >= $datefrom)
			{
				$day_difference++;
			}

			$datediff = $day_difference;
			$res = ($datediff==1) ? 'yesterday' : $datediff.' days ago';
			break;
			// If difference is between 1 week and 30 days
			// weeks is a good interval
		case(strtotime('-1 month', $dateto) < $datefrom):
			$week_difference = 1;
			while (strtotime('-'.$week_difference.' week', $dateto) >= $datefrom)
			{
				$week_difference++;
			}

			$datediff = $week_difference;
			$res = ($datediff==1) ? 'last week' : $datediff.' weeks ago';
			break;
			// If difference is between 30 days and 365 days
			// months is a good interval, again, the same thing
			// applies, if the 29th February happens to exist
			// between your 2 dates, the function will return
			// the 'incorrect' value for a day
		case(strtotime('-1 year', $dateto) < $datefrom):
			$months_difference = 1;
			while (strtotime('-'.$months_difference.' month', $dateto) >= $datefrom)
			{
				$months_difference++;
			}

			$datediff = $months_difference;
			$res = ($datediff==1) ? $datediff.' month ago' : $datediff.' months ago';

			break;
			// If difference is greater than or equal to 365
			// days, return year. This will be incorrect if
			// for example, you call the function on the 28th April
			// 2008 passing in 29th April 2007. It will return
			// 1 year ago when in actual fact (yawn!) not quite
			// a year has gone by
		case(strtotime('-1 year', $dateto) >= $datefrom):
			$year_difference = 1;
			while (strtotime('-'.$year_difference.' year', $dateto) >= $datefrom)
			{
				$year_difference++;
			}

			$datediff = $year_difference;
			$res = ($datediff==1) ? $datediff.' year ago' : $datediff.' years ago';
			break;

	}
	return $res;
}

function is_search_bot($bot_string){
	// TODO detect by browser
	if(strlen($bot_string) <= 0) return true;

	$spam_array = array(
		"^Java",
		"^Jakarta",
		"User-Agent",
		"^Mozilla$",
		"[A-Z][a-z]{3,} [a-z]{4,} [a-z]{4,}"
		);

		while(list($key, $val) = each($spam_array)){
			if(preg_match("/".$val."/", $bot_string) > 0){
				//This is a robot
				return true;
			}
		}

		$bot_array = array(
		"googlebot",
		"Yahoo! Slurp",
	 	"shopwiki",
		"YahooSeeker",
  		"inktomisearch",
		"Ask Jeeves",
		"MSNbot",
		"BecomeBot",
		"Gigabot",
		"libwww-perl",
		"exabot.com",
		"FAST Enterprise Crawler",
		"Speedy Spider",
        "Xenu Link Sleuth",
        "charlotte.betaspider.com",
        "ConveraCrawler",
	    "YandexBot",
	    "bingbot",
	    "DotBot",
	    "Sogou",
	    "psbot",
	    "MJ12bot",
	    "Ezooms",
        "Baiduspider",
        "ia_archiver",
        "SiteBot",
        "FatBot",
        "discobot",
        "yrspider",
        "spbot",
        "LexxeBot",
        "ichiro",
		"HyperEstraier",
		"Giant",
		"heeii/Nuts Java",
		"VadixBot",
		"Mozilla/5.0 (compatible; Jim +http://­www.­hanzo­archives.­com)",
		"Gungho",
		"Missouri College Browse",
		"panscient.com"
		);

		while(list($key, $val) = each($bot_array)){
			if(stristr($bot_string, $val) != false){
				//This is a robot
				return true;
			}
		}
		return false;
}

function is_really_int(&$val) {
	$num = (int)$val;
	if ($val==$num) {
		$val=$num;
		return true;
	}
	return false;
}

function getMonthsOfYear(){
	$months = array(
	1 => 'January',
	2 => 'February',
	3 => 'March',
	4 => 'April',
	5 => 'May',
	6 => 'June',
	7 => 'July',
	8 => 'August',
	9 => 'September',
	10 => 'October',
	11 => 'November',
	12 => 'December'
	);

	return $months;
}

function getDaysOfMonth(){
	$ret = array();
	$days = range(1, 32);

	foreach($days as $day){
		$ret[$day] = $day;
	}
	return $ret;
}

function getYearRange($start = 0, $end = 100){
	$data = Array();

	$thisYear = $start == 0 ? date('Y') : $start;
	$startYear = ($thisYear - $end);

	foreach (range($thisYear, $startYear) as $year) {
		$data[$year] = $year;
	}

	return $data;
}


/**
 * @author Paul Gregg <pgregg@pgregg.com>
 * @copyright 10 January 2008
 * @version 1.2
 * @link http://www.pgregg.com/projects/php/ip_in_range/
 * @link http://www.pgregg.com/donate/
 *
 * @license This software is Donationware - if you feel you have benefited from the use of this tool then please consider a donation.
 *
 * @tutorial ip_in_range.php - Function to determine if an IP is located in a
 *                   specific range as specified via several alternative
 *                   formats.
 *
 * Network ranges can be specified as:
 * 1. Wildcard format:     1.2.3.*
 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
 *
 * Return value BOOLEAN : ip_in_range($ip, $range);
 *
 * Please do not remove this header, or source attibution from this file.
 */


// decbin32
// In order to simplify working with IP addresses (in binary) and their
// netmasks, it is easier to ensure that the binary strings are padded
// with zeros out to 32 characters - IP addresses are 32 bit numbers
Function decbin32 ($dec) {
	return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
}

// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
Function ip_in_range($ip, $range) {
	if (strpos($range, '/') !== false) {
		// $range is in IP/NETMASK format
		list($range, $netmask) = explode('/', $range, 2);
		if (strpos($netmask, '.') !== false) {
			// $netmask is a 255.255.0.0 format
			$netmask = str_replace('*', '0', $netmask);
			$netmask_dec = ip2long($netmask);
			return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
		} else {
			// $netmask is a CIDR size block
			// fix the range argument
			$x = explode('.', $range);
			while(count($x)<4) $x[] = '0';
			list($a,$b,$c,$d) = $x;
			$range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
			$range_dec = ip2long($range);
			$ip_dec = ip2long($ip);

			# Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
			#$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

			# Strategy 2 - Use math to create it
			$wildcard_dec = pow(2, (32-$netmask)) - 1;
			$netmask_dec = ~ $wildcard_dec;

			return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
		}
	} else {
		// range might be 255.255.*.* or 1.2.3.0-1.2.3.255
		if (strpos($range, '*') !==false) { // a.b.*.* format
			// Just convert to A-B format by setting * to 0 for A and 255 for B
			$lower = str_replace('*', '0', $range);
			$upper = str_replace('*', '255', $range);
			$range = "$lower-$upper";
		}

		if (strpos($range, '-')!==false) { // A-B format
			list($lower, $upper) = explode('-', $range, 2);
			$lower_dec = (float)sprintf("%u",ip2long($lower));
			$upper_dec = (float)sprintf("%u",ip2long($upper));
			$ip_dec = (float)sprintf("%u",ip2long($ip));
			return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
		}

		echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';
		return false;
	}

}

////////////////////////////////////////////////////////
// Function:         do_dump
// Inspired from:     PHP.net Contributions
// Description: Better GI than print_r or var_dump

function do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL)
{
	$do_dump_indent = "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
	$reference = $reference.$var_name;
	$keyvar = 'the_do_dump_recursion_protection_scheme'; $keyname = 'referenced_object_name';

	if (is_array($var) && isset($var[$keyvar]))
	{
		$real_var = &$var[$keyvar];
		$real_name = &$var[$keyname];
		$type = ucfirst(gettype($real_var));
		echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
	}
	else
	{
		$var = array($keyvar => $var, $keyname => $reference);
		$avar = &$var[$keyvar];

		$type = ucfirst(gettype($avar));
		if($type == "String") $type_color = "<span style='color:green'>";
		elseif($type == "Integer") $type_color = "<span style='color:red'>";
		elseif($type == "Double"){ $type_color = "<span style='color:#0099c5'>"; $type = "Float"; }
		elseif($type == "Boolean") $type_color = "<span style='color:#92008d'>";
		elseif($type == "NULL") $type_color = "<span style='color:black'>";

		if(is_array($avar))
		{
			$count = count($avar);
			echo "$indent" . ($var_name ? "$var_name => ":"") . "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
			$keys = array_keys($avar);
			foreach($keys as $name)
			{
				$value = &$avar[$name];
				do_dump($value, "['$name']", $indent.$do_dump_indent, $reference);
			}
			echo "$indent)<br>";
		}
		elseif(is_object($avar))
		{
			echo "$indent$var_name <span style='color:#a2a2a2'>$type</span><br>$indent(<br>";
			foreach($avar as $name=>$value) do_dump($value, "$name", $indent.$do_dump_indent, $reference);
			echo "$indent)<br>";
		}
		elseif(is_int($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
		elseif(is_string($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color\"$avar\"</span><br>";
		elseif(is_float($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
		elseif(is_bool($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color".($avar == 1 ? "TRUE":"FALSE")."</span><br>";
		elseif(is_null($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> {$type_color}NULL</span><br>";
		else echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $avar<br>";

		$var = $var[$keyvar];
	}
}




/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  AES implementation in PHP (c) Chris Veness 2005-2010. Right to use and adapt is granted for   */
/*    under a simple creative commons attribution licence. No warranty of any form is offered.    */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */


/**
 * AES Cipher function: encrypt 'input' with Rijndael algorithm
 *
 * @param input message as byte-array (16 bytes)
 * @param w     key schedule as 2D byte-array (Nr+1 x Nb bytes) -
 *              generated from the cipher key by KeyExpansion()
 * @return      ciphertext as byte-array (16 bytes)
 */
function Cipher($input, $w) {    // main Cipher function [�5.1]
  $Nb = 4;                 // block size (in words): no of columns in state (fixed at 4 for AES)
  $Nr = count($w)/$Nb - 1; // no of rounds: 10/12/14 for 128/192/256-bit keys

  $state = array();  // initialise 4xNb byte-array 'state' with input [�3.4]
  for ($i=0; $i<4*$Nb; $i++) $state[$i%4][floor($i/4)] = $input[$i];

  $state = AddRoundKey($state, $w, 0, $Nb);

  for ($round=1; $round<$Nr; $round++) {  // apply Nr rounds
    $state = SubBytes($state, $Nb);
    $state = ShiftRows($state, $Nb);
    $state = MixColumns($state, $Nb);
    $state = AddRoundKey($state, $w, $round, $Nb);
  }

  $state = SubBytes($state, $Nb);
  $state = ShiftRows($state, $Nb);
  $state = AddRoundKey($state, $w, $Nr, $Nb);

  $output = array(4*$Nb);  // convert state to 1-d array before returning [�3.4]
  for ($i=0; $i<4*$Nb; $i++) $output[$i] = $state[$i%4][floor($i/4)];
  return $output;
}


function AddRoundKey($state, $w, $rnd, $Nb) {  // xor Round Key into state S [�5.1.4]
  for ($r=0; $r<4; $r++) {
    for ($c=0; $c<$Nb; $c++) $state[$r][$c] ^= $w[$rnd*4+$c][$r];
  }
  return $state;
}

function SubBytes($s, $Nb) {    // apply SBox to state S [�5.1.1]
  global $Sbox;  // PHP needs explicit declaration to access global variables!
  for ($r=0; $r<4; $r++) {
    for ($c=0; $c<$Nb; $c++) $s[$r][$c] = $Sbox[$s[$r][$c]];
  }
  return $s;
}

function ShiftRows($s, $Nb) {    // shift row r of state S left by r bytes [�5.1.2]
  $t = array(4);
  for ($r=1; $r<4; $r++) {
    for ($c=0; $c<4; $c++) $t[$c] = $s[$r][($c+$r)%$Nb];  // shift into temp copy
    for ($c=0; $c<4; $c++) $s[$r][$c] = $t[$c];         // and copy back
  }          // note that this will work for Nb=4,5,6, but not 7,8 (always 4 for AES):
  return $s;  // see fp.gladman.plus.com/cryptography_technology/rijndael/aes.spec.311.pdf
}

function MixColumns($s, $Nb) {   // combine bytes of each col of state S [�5.1.3]
  for ($c=0; $c<4; $c++) {
    $a = array(4);  // 'a' is a copy of the current column from 's'
    $b = array(4);  // 'b' is a�{02} in GF(2^8)
    for ($i=0; $i<4; $i++) {
      $a[$i] = $s[$i][$c];
      $b[$i] = $s[$i][$c]&0x80 ? $s[$i][$c]<<1 ^ 0x011b : $s[$i][$c]<<1;
    }
    // a[n] ^ b[n] is a�{03} in GF(2^8)
    $s[0][$c] = $b[0] ^ $a[1] ^ $b[1] ^ $a[2] ^ $a[3]; // 2*a0 + 3*a1 + a2 + a3
    $s[1][$c] = $a[0] ^ $b[1] ^ $a[2] ^ $b[2] ^ $a[3]; // a0 * 2*a1 + 3*a2 + a3
    $s[2][$c] = $a[0] ^ $a[1] ^ $b[2] ^ $a[3] ^ $b[3]; // a0 + a1 + 2*a2 + 3*a3
    $s[3][$c] = $a[0] ^ $b[0] ^ $a[1] ^ $a[2] ^ $b[3]; // 3*a0 + a1 + a2 + 2*a3
  }
  return $s;
}

/**
 * Key expansion for Rijndael Cipher(): performs key expansion on cipher key
 * to generate a key schedule
 *
 * @param key cipher key byte-array (16 bytes)
 * @return    key schedule as 2D byte-array (Nr+1 x Nb bytes)
 */
function KeyExpansion($key) {  // generate Key Schedule from Cipher Key [�5.2]
  global $Rcon;  // PHP needs explicit declaration to access global variables!
  $Nb = 4;              // block size (in words): no of columns in state (fixed at 4 for AES)
  $Nk = count($key)/4;  // key length (in words): 4/6/8 for 128/192/256-bit keys
  $Nr = $Nk + 6;        // no of rounds: 10/12/14 for 128/192/256-bit keys

  $w = array();
  $temp = array();

  for ($i=0; $i<$Nk; $i++) {
    $r = array($key[4*$i], $key[4*$i+1], $key[4*$i+2], $key[4*$i+3]);
    $w[$i] = $r;
  }

  for ($i=$Nk; $i<($Nb*($Nr+1)); $i++) {
    $w[$i] = array();
    for ($t=0; $t<4; $t++) $temp[$t] = $w[$i-1][$t];
    if ($i % $Nk == 0) {
      $temp = SubWord(RotWord($temp));
      for ($t=0; $t<4; $t++) $temp[$t] ^= $Rcon[$i/$Nk][$t];
    } else if ($Nk > 6 && $i%$Nk == 4) {
      $temp = SubWord($temp);
    }
    for ($t=0; $t<4; $t++) $w[$i][$t] = $w[$i-$Nk][$t] ^ $temp[$t];
  }
  return $w;
}

function SubWord($w) {    // apply SBox to 4-byte word w
  global $Sbox;  // PHP needs explicit declaration to access global variables!
  for ($i=0; $i<4; $i++) $w[$i] = $Sbox[$w[$i]];
  return $w;
}

function RotWord($w) {    // rotate 4-byte word w left by one byte
  $tmp = $w[0];
  for ($i=0; $i<3; $i++) $w[$i] = $w[$i+1];
  $w[3] = $tmp;
  return $w;
}

// Sbox is pre-computed multiplicative inverse in GF(2^8) used in SubBytes and KeyExpansion [�5.1.1]
$Sbox =  array(0x63,0x7c,0x77,0x7b,0xf2,0x6b,0x6f,0xc5,0x30,0x01,0x67,0x2b,0xfe,0xd7,0xab,0x76,
               0xca,0x82,0xc9,0x7d,0xfa,0x59,0x47,0xf0,0xad,0xd4,0xa2,0xaf,0x9c,0xa4,0x72,0xc0,
               0xb7,0xfd,0x93,0x26,0x36,0x3f,0xf7,0xcc,0x34,0xa5,0xe5,0xf1,0x71,0xd8,0x31,0x15,
               0x04,0xc7,0x23,0xc3,0x18,0x96,0x05,0x9a,0x07,0x12,0x80,0xe2,0xeb,0x27,0xb2,0x75,
               0x09,0x83,0x2c,0x1a,0x1b,0x6e,0x5a,0xa0,0x52,0x3b,0xd6,0xb3,0x29,0xe3,0x2f,0x84,
               0x53,0xd1,0x00,0xed,0x20,0xfc,0xb1,0x5b,0x6a,0xcb,0xbe,0x39,0x4a,0x4c,0x58,0xcf,
               0xd0,0xef,0xaa,0xfb,0x43,0x4d,0x33,0x85,0x45,0xf9,0x02,0x7f,0x50,0x3c,0x9f,0xa8,
               0x51,0xa3,0x40,0x8f,0x92,0x9d,0x38,0xf5,0xbc,0xb6,0xda,0x21,0x10,0xff,0xf3,0xd2,
               0xcd,0x0c,0x13,0xec,0x5f,0x97,0x44,0x17,0xc4,0xa7,0x7e,0x3d,0x64,0x5d,0x19,0x73,
               0x60,0x81,0x4f,0xdc,0x22,0x2a,0x90,0x88,0x46,0xee,0xb8,0x14,0xde,0x5e,0x0b,0xdb,
               0xe0,0x32,0x3a,0x0a,0x49,0x06,0x24,0x5c,0xc2,0xd3,0xac,0x62,0x91,0x95,0xe4,0x79,
               0xe7,0xc8,0x37,0x6d,0x8d,0xd5,0x4e,0xa9,0x6c,0x56,0xf4,0xea,0x65,0x7a,0xae,0x08,
               0xba,0x78,0x25,0x2e,0x1c,0xa6,0xb4,0xc6,0xe8,0xdd,0x74,0x1f,0x4b,0xbd,0x8b,0x8a,
               0x70,0x3e,0xb5,0x66,0x48,0x03,0xf6,0x0e,0x61,0x35,0x57,0xb9,0x86,0xc1,0x1d,0x9e,
               0xe1,0xf8,0x98,0x11,0x69,0xd9,0x8e,0x94,0x9b,0x1e,0x87,0xe9,0xce,0x55,0x28,0xdf,
               0x8c,0xa1,0x89,0x0d,0xbf,0xe6,0x42,0x68,0x41,0x99,0x2d,0x0f,0xb0,0x54,0xbb,0x16);

// Rcon is Round Constant used for the Key Expansion [1st col is 2^(r-1) in GF(2^8)] [�5.2]
$Rcon = array( array(0x00, 0x00, 0x00, 0x00),
               array(0x01, 0x00, 0x00, 0x00),
               array(0x02, 0x00, 0x00, 0x00),
               array(0x04, 0x00, 0x00, 0x00),
               array(0x08, 0x00, 0x00, 0x00),
               array(0x10, 0x00, 0x00, 0x00),
               array(0x20, 0x00, 0x00, 0x00),
               array(0x40, 0x00, 0x00, 0x00),
               array(0x80, 0x00, 0x00, 0x00),
               array(0x1b, 0x00, 0x00, 0x00),
               array(0x36, 0x00, 0x00, 0x00) );


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

/**
 * Encrypt a text using AES encryption in Counter mode of operation
 *  - see http://csrc.nist.gov/publications/nistpubs/800-38a/sp800-38a.pdf
 *
 * Unicode multi-byte character safe
 *
 * @param plaintext source text to be encrypted
 * @param password  the password to use to generate a key
 * @param nBits     number of bits to be used in the key (128, 192, or 256)
 * @return          encrypted text
 */
function AESEncryptCtr($plaintext, $password = "S4M__1-L-2_-+M6N__00c=++./..#+", $nBits = 256) {
  $blockSize = 16;  // block size fixed at 16 bytes / 128 bits (Nb=4) for AES
  if (!($nBits==128 || $nBits==192 || $nBits==256)) return '';  // standard allows 128/192/256 bit keys
  // note PHP (5) gives us plaintext and password in UTF8 encoding!

  // use AES itself to encrypt password to get cipher key (using plain password as source for
  // key expansion) - gives us well encrypted key
  $nBytes = $nBits/8;  // no bytes in key
  $pwBytes = array();
  for ($i=0; $i<$nBytes; $i++) $pwBytes[$i] = ord(substr($password,$i,1)) & 0xff;
  $key = Cipher($pwBytes, KeyExpansion($pwBytes));
  $key = array_merge($key, array_slice($key, 0, $nBytes-16));  // expand key to 16/24/32 bytes long

  // initialise counter block (NIST SP800-38A �B.2): millisecond time-stamp for nonce in
  // 1st 8 bytes, block counter in 2nd 8 bytes
  $counterBlock = array();
  $nonce = floor(microtime(true)*1000);   // timestamp: milliseconds since 1-Jan-1970
  $nonceSec = floor($nonce/1000);
  $nonceMs = $nonce%1000;
  // encode nonce with seconds in 1st 4 bytes, and (repeated) ms part filling 2nd 4 bytes
  for ($i=0; $i<4; $i++) $counterBlock[$i] = urs($nonceSec, $i*8) & 0xff;
  for ($i=0; $i<4; $i++) $counterBlock[$i+4] = $nonceMs & 0xff;
  // and convert it to a string to go on the front of the ciphertext
  $ctrTxt = '';
  for ($i=0; $i<8; $i++) $ctrTxt .= chr($counterBlock[$i]);

  // generate key schedule - an expansion of the key into distinct Key Rounds for each round
  $keySchedule = KeyExpansion($key);

  $blockCount = ceil(strlen($plaintext)/$blockSize);
  $ciphertxt = array();  // ciphertext as array of strings

  for ($b=0; $b<$blockCount; $b++) {
    // set counter (block #) in last 8 bytes of counter block (leaving nonce in 1st 8 bytes)
    // done in two stages for 32-bit ops: using two words allows us to go past 2^32 blocks (68GB)
    for ($c=0; $c<4; $c++) $counterBlock[15-$c] = urs($b, $c*8) & 0xff;
    for ($c=0; $c<4; $c++) $counterBlock[15-$c-4] = urs($b/0x100000000, $c*8);

    $cipherCntr = Cipher($counterBlock, $keySchedule);  // -- encrypt counter block --

    // block size is reduced on final block
    $blockLength = $b<$blockCount-1 ? $blockSize : (strlen($plaintext)-1)%$blockSize+1;
    $cipherByte = array();

    for ($i=0; $i<$blockLength; $i++) {  // -- xor plaintext with ciphered counter byte-by-byte --
      $cipherByte[$i] = $cipherCntr[$i] ^ ord(substr($plaintext, $b*$blockSize+$i, 1));
      $cipherByte[$i] = chr($cipherByte[$i]);
    }
    $ciphertxt[$b] = implode('', $cipherByte);  // escape troublesome characters in ciphertext
  }

  // implode is more efficient than repeated string concatenation
  $ciphertext = $ctrTxt . implode('', $ciphertxt);
  $ciphertext = base64_encode($ciphertext);
  return $ciphertext;
}


/**
 * Decrypt a text encrypted by AES in counter mode of operation
 *
 * @param ciphertext source text to be decrypted
 * @param password   the password to use to generate a key
 * @param nBits      number of bits to be used in the key (128, 192, or 256)
 * @return           decrypted text
 */
function AESDecryptCtr($ciphertext, $password = "S4M__1-L-2_-+M6N__00c=++./..#+", $nBits = 256) {
  $blockSize = 16;  // block size fixed at 16 bytes / 128 bits (Nb=4) for AES
  if (!($nBits==128 || $nBits==192 || $nBits==256)) return '';  // standard allows 128/192/256 bit keys
  $ciphertext = base64_decode($ciphertext);

  // use AES to encrypt password (mirroring encrypt routine)
  $nBytes = $nBits/8;  // no bytes in key
  $pwBytes = array();
  for ($i=0; $i<$nBytes; $i++) $pwBytes[$i] = ord(substr($password,$i,1)) & 0xff;
  $key = Cipher($pwBytes, KeyExpansion($pwBytes));
  $key = array_merge($key, array_slice($key, 0, $nBytes-16));  // expand key to 16/24/32 bytes long

  // recover nonce from 1st element of ciphertext
  $counterBlock = array();
  $ctrTxt = substr($ciphertext, 0, 8);
  for ($i=0; $i<8; $i++) $counterBlock[$i] = ord(substr($ctrTxt,$i,1));

  // generate key schedule
  $keySchedule = KeyExpansion($key);

  // separate ciphertext into blocks (skipping past initial 8 bytes)
  $nBlocks = ceil((strlen($ciphertext)-8) / $blockSize);
  $ct = array();
  for ($b=0; $b<$nBlocks; $b++) $ct[$b] = substr($ciphertext, 8+$b*$blockSize, 16);
  $ciphertext = $ct;  // ciphertext is now array of block-length strings

  // plaintext will get generated block-by-block into array of block-length strings
  $plaintxt = array();

  for ($b=0; $b<$nBlocks; $b++) {
    // set counter (block #) in last 8 bytes of counter block (leaving nonce in 1st 8 bytes)
    for ($c=0; $c<4; $c++) $counterBlock[15-$c] = urs($b, $c*8) & 0xff;
    for ($c=0; $c<4; $c++) $counterBlock[15-$c-4] = urs(($b+1)/0x100000000-1, $c*8) & 0xff;

    $cipherCntr = Cipher($counterBlock, $keySchedule);  // encrypt counter block

    $plaintxtByte = array();
    for ($i=0; $i<strlen($ciphertext[$b]); $i++) {
      // -- xor plaintext with ciphered counter byte-by-byte --
      $plaintxtByte[$i] = $cipherCntr[$i] ^ ord(substr($ciphertext[$b],$i,1));
      $plaintxtByte[$i] = chr($plaintxtByte[$i]);

    }
    $plaintxt[$b] = implode('', $plaintxtByte);
  }

  // join array of blocks into single plaintext string
  $plaintext = implode('',$plaintxt);

  return $plaintext;
}


/*
 * Unsigned right shift function, since PHP has neither >>> operator nor unsigned ints
 *
 * @param a  number to be shifted (32-bit integer)
 * @param b  number of bits to shift a to the right (0..31)
 * @return   a right-shifted and zero-filled by b bits
 */
function urs($a, $b) {
  $a &= 0xffffffff; $b &= 0x1f;  // (bounds check)
  if ($a&0x80000000 && $b>0) {   // if left-most bit set
    $a = ($a>>1) & 0x7fffffff;   //   right-shift one bit & clear left-most bit
    $a = $a >> ($b-1);           //   remaining right-shifts
  } else {                       // otherwise
    $a = ($a>>$b);               //   use normal right-shift
  }
  return $a;
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */



/**
 * To validate an email address according to RFC 5322 and others
 *
 * Copyright (c) 2008-2010, Dominic Sayers							<br>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *     - Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *     - Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *     - Neither the name of Dominic Sayers nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package	is_email
 * @author	Dominic Sayers <dominic@sayers.cc>
 * @copyright	2008-2010 Dominic Sayers
 * @license	http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link	http://www.dominicsayers.com/isemail
 * @version	1.18.1 - Releasing I got the version number wrong for the last release. No other change :-|
 */

// The quality of this code has been improved greatly by using PHPLint
// Copyright (c) 2009 Umberto Salsi
// This is free software; see the license for copying conditions.
// More info: http://www.icosaedro.it/phplint/
/*.
 require_module 'standard';
 require_module 'pcre';
 .*/
/*.mixed.*/ function is_email (/*.string.*/ $email, $checkDNS = false, $diagnose = false) {
// Check that $email is a valid address. Read the following RFCs to understand the constraints:
// 	(http://tools.ietf.org/html/rfc5322)
// 	(http://tools.ietf.org/html/rfc3696)
// 	(http://tools.ietf.org/html/rfc5321)
// 	(http://tools.ietf.org/html/rfc4291#section-2.2)
// 	(http://tools.ietf.org/html/rfc1123#section-2.1)

if (!defined('ISEMAIL_VALID')) {
	define('ISEMAIL_VALID'			, 0);
	define('ISEMAIL_TOOLONG'		, 1);
	define('ISEMAIL_NOAT'			, 2);
	define('ISEMAIL_NOLOCALPART'		, 3);
	define('ISEMAIL_NODOMAIN'		, 4);
	define('ISEMAIL_ZEROLENGTHELEMENT'	, 5);
	define('ISEMAIL_BADCOMMENT_START'	, 6);
	define('ISEMAIL_BADCOMMENT_END'		, 7);
	define('ISEMAIL_UNESCAPEDDELIM'		, 8);
	define('ISEMAIL_EMPTYELEMENT'		, 9);
	define('ISEMAIL_UNESCAPEDSPECIAL'	, 10);
	define('ISEMAIL_LOCALTOOLONG'		, 11);
	define('ISEMAIL_IPV4BADPREFIX'		, 12);
	define('ISEMAIL_IPV6BADPREFIXMIXED'	, 13);
	define('ISEMAIL_IPV6BADPREFIX'		, 14);
	define('ISEMAIL_IPV6GROUPCOUNT'		, 15);
	define('ISEMAIL_IPV6DOUBLEDOUBLECOLON'	, 16);
	define('ISEMAIL_IPV6BADCHAR'		, 17);
	define('ISEMAIL_IPV6TOOMANYGROUPS'	, 18);
	define('ISEMAIL_TLD'			, 19);
	define('ISEMAIL_DOMAINEMPTYELEMENT'	, 20);
	define('ISEMAIL_DOMAINELEMENTTOOLONG'	, 21);
	define('ISEMAIL_DOMAINBADCHAR'		, 22);
	define('ISEMAIL_DOMAINTOOLONG'		, 23);
	define('ISEMAIL_TLDNUMERIC'		, 24);
	define('ISEMAIL_DOMAINNOTFOUND'		, 25);
	define('ISEMAIL_NOTDEFINED'		, 99);
}

// the upper limit on address lengths should normally be considered to be 254
// 	(http://www.rfc-editor.org/errata_search.php?rfc=3696)
// 	NB My erratum has now been verified by the IETF so the correct answer is 254
//
// The maximum total length of a reverse-path or forward-path is 256
// characters (including the punctuation and element separators)
// 	(http://tools.ietf.org/html/rfc5321#section-4.5.3.1.3)
//	NB There is a mandatory 2-character wrapper round the actual address
$emailLength = strlen($email);
// revision 1.17: Max length reduced to 254 (see above)
if ($emailLength > 254)			return $diagnose ? ISEMAIL_TOOLONG	: false;	// Too long

// Contemporary email addresses consist of a "local part" separated from
// a "domain part" (a fully-qualified domain name) by an at-sign ("@").
// 	(http://tools.ietf.org/html/rfc3696#section-3)
$atIndex = strrpos($email,'@');

if ($atIndex === false)			return $diagnose ? ISEMAIL_NOAT		: false;	// No at-sign
if ($atIndex === 0)			return $diagnose ? ISEMAIL_NOLOCALPART	: false;	// No local part
if ($atIndex === $emailLength - 1)	return $diagnose ? ISEMAIL_NODOMAIN	: false;	// No domain part
// revision 1.14: Length test bug suggested by Andrew Campbell of Gloucester, MA

// Sanitize comments
// - remove nested comments, quotes and dots in comments
// - remove parentheses and dots from quoted strings
$braceDepth	= 0;
$inQuote	= false;
$escapeThisChar	= false;

for ($i = 0; $i < $emailLength; ++$i) {
	$char = $email[$i];
	$replaceChar = false;

	if ($char === '\\') {
		$escapeThisChar = !$escapeThisChar;	// Escape the next character?
	} else {
		switch ($char) {
			case '(':
				if ($escapeThisChar) {
					$replaceChar = true;
				} else {
					if ($inQuote) {
						$replaceChar = true;
					} else {
						if ($braceDepth++ > 0) $replaceChar = true;	// Increment brace depth
					}
				}

				break;
			case ')':
				if ($escapeThisChar) {
					$replaceChar = true;
				} else {
					if ($inQuote) {
						$replaceChar = true;
					} else {
						if (--$braceDepth > 0) $replaceChar = true;	// Decrement brace depth
						if ($braceDepth < 0) $braceDepth = 0;
					}
				}

				break;
			case '"':
				if ($escapeThisChar) {
					$replaceChar = true;
				} else {
					if ($braceDepth === 0) {
						$inQuote = !$inQuote;	// Are we inside a quoted string?
					} else {
						$replaceChar = true;
					}
				}

				break;
			case '.':	// Dots don't help us either
				if ($escapeThisChar) {
					$replaceChar = true;
				} else {
					if ($braceDepth > 0) $replaceChar = true;
				}

				break;
			default:
		}

		$escapeThisChar = false;
		//			if ($replaceChar) $email[$i] = 'x';	// Replace the offending character with something harmless
		// revision 1.12: Line above replaced because PHPLint doesn't like that syntax
		if ($replaceChar) $email = (string) substr_replace($email, 'x', $i, 1);	// Replace the offending character with something harmless
	}
}

$localPart	= substr($email, 0, $atIndex);
$domain		= substr($email, $atIndex + 1);
$FWS		= "(?:(?:(?:[ \\t]*(?:\\r\\n))?[ \\t]+)|(?:[ \\t]+(?:(?:\\r\\n)[ \\t]+)*))";	// Folding white space
// Let's check the local part for RFC compliance...
//
// local-part      =       dot-atom / quoted-string / obs-local-part
// obs-local-part  =       word *("." word)
// 	(http://tools.ietf.org/html/rfc5322#section-3.4.1)
//
// Problem: need to distinguish between "first.last" and "first"."last"
// (i.e. one element or two). And I suck at regexes.
$dotArray	= /*. (array[int]string) .*/ preg_split('/\\.(?=(?:[^\\"]*\\"[^\\"]*\\")*(?![^\\"]*\\"))/m', $localPart);
$partLength	= 0;

foreach ($dotArray as $element) {
	// Remove any leading or trailing FWS
	$element	= preg_replace("/^$FWS|$FWS\$/", '', $element);
	$elementLength	= strlen($element);

	if ($elementLength === 0)								return $diagnose ? ISEMAIL_ZEROLENGTHELEMENT	: false;	// Can't have empty element (consecutive dots or dots at the start or end)
	// revision 1.15: Speed up the test and get rid of "unitialized string offset" notices from PHP

	// We need to remove any valid comments (i.e. those at the start or end of the element)
	if ($element[0] === '(') {
		$indexBrace = strpos($element, ')');
		if ($indexBrace !== false) {
			if (preg_match('/(?<!\\\\)[\\(\\)]/', substr($element, 1, $indexBrace - 1)) > 0) {
				return $diagnose ? ISEMAIL_BADCOMMENT_START	: false;	// Illegal characters in comment
			}
			$element	= substr($element, $indexBrace + 1, $elementLength - $indexBrace - 1);
			$elementLength	= strlen($element);
		}
	}

	if ($element[$elementLength - 1] === ')') {
		$indexBrace = strrpos($element, '(');
		if ($indexBrace !== false) {
			if (preg_match('/(?<!\\\\)(?:[\\(\\)])/', substr($element, $indexBrace + 1, $elementLength - $indexBrace - 2)) > 0) {
				return $diagnose ? ISEMAIL_BADCOMMENT_END	: false;	// Illegal characters in comment
			}
			$element	= substr($element, 0, $indexBrace);
			$elementLength	= strlen($element);
		}
	}

	// Remove any leading or trailing FWS around the element (inside any comments)
	$element = preg_replace("/^$FWS|$FWS\$/", '', $element);

	// What's left counts towards the maximum length for this part
	if ($partLength > 0) $partLength++;	// for the dot
	$partLength += strlen($element);

	// Each dot-delimited component can be an atom or a quoted string
	// (because of the obs-local-part provision)
	if (preg_match('/^"(?:.)*"$/s', $element) > 0) {
		// Quoted-string tests:
		//
		// Remove any FWS
		$element = preg_replace("/(?<!\\\\)$FWS/", '', $element);
		// My regex skillz aren't up to distinguishing between \" \\" \\\" \\\\" etc.
		// So remove all \\ from the string first...
		$element = preg_replace('/\\\\\\\\/', ' ', $element);
		if (preg_match('/(?<!\\\\|^)["\\r\\n\\x00](?!$)|\\\\"$|""/', $element) > 0)	return $diagnose ? ISEMAIL_UNESCAPEDDELIM	: false;	// ", CR, LF and NUL must be escaped, "" is too short
	} else {
		// Unquoted string tests:
		//
		// Period (".") may...appear, but may not be used to start or end the
		// local part, nor may two or more consecutive periods appear.
		// 	(http://tools.ietf.org/html/rfc3696#section-3)
		//
		// A zero-length element implies a period at the beginning or end of the
		// local part, or two periods together. Either way it's not allowed.
		if ($element === '')								return $diagnose ? ISEMAIL_EMPTYELEMENT	: false;	// Dots in wrong place

		// Any ASCII graphic (printing) character other than the
		// at-sign ("@"), backslash, double quote, comma, or square brackets may
		// appear without quoting.  If any of that list of excluded characters
		// are to appear, they must be quoted
		// 	(http://tools.ietf.org/html/rfc3696#section-3)
		//
		// Any excluded characters? i.e. 0x00-0x20, (, ), <, >, [, ], :, ;, @, \, comma, period, "
		if (preg_match('/[\\x00-\\x20\\(\\)<>\\[\\]:;@\\\\,\\."]/', $element) > 0)	return $diagnose ? ISEMAIL_UNESCAPEDSPECIAL	: false;	// These characters must be in a quoted string
	}
}

if ($partLength > 64) return $diagnose ? ISEMAIL_LOCALTOOLONG	: false;	// Local part must be 64 characters or less

// Now let's check the domain part...

// The domain name can also be replaced by an IP address in square brackets
// 	(http://tools.ietf.org/html/rfc3696#section-3)
// 	(http://tools.ietf.org/html/rfc5321#section-4.1.3)
// 	(http://tools.ietf.org/html/rfc4291#section-2.2)
if (preg_match('/^\\[(.)+]$/', $domain) === 1) {
	// It's an address-literal
	$addressLiteral = substr($domain, 1, strlen($domain) - 2);
	$matchesIP	= array();

	// Extract IPv4 part from the end of the address-literal (if there is one)
	if (preg_match('/\\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', $addressLiteral, $matchesIP) > 0) {
		$index = strrpos($addressLiteral, $matchesIP[0]);

		if ($index === 0) {
			// Nothing there except a valid IPv4 address, so...
			return $diagnose ? ISEMAIL_VALID : true;
		} else {
			// Assume it's an attempt at a mixed address (IPv6 + IPv4)
			if ($addressLiteral[$index - 1] !== ':')	return $diagnose ? ISEMAIL_IPV4BADPREFIX	: false;	// Character preceding IPv4 address must be ':'
			if (substr($addressLiteral, 0, 5) !== 'IPv6:')	return $diagnose ? ISEMAIL_IPV6BADPREFIXMIXED	: false;	// RFC5321 section 4.1.3

			$IPv6		= substr($addressLiteral, 5, ($index ===7) ? 2 : $index - 6);
			$groupMax	= 6;
		}
	} else {
		// It must be an attempt at pure IPv6
		if (substr($addressLiteral, 0, 5) !== 'IPv6:')		return $diagnose ? ISEMAIL_IPV6BADPREFIX	: false;	// RFC5321 section 4.1.3
		$IPv6 = substr($addressLiteral, 5);
		$groupMax = 8;
	}

	$groupCount	= preg_match_all('/^[0-9a-fA-F]{0,4}|\\:[0-9a-fA-F]{0,4}|(.)/', $IPv6, $matchesIP);
	$index		= strpos($IPv6,'::');

	if ($index === false) {
		// We need exactly the right number of groups
		if ($groupCount !== $groupMax)				return $diagnose ? ISEMAIL_IPV6GROUPCOUNT	: false;	// RFC5321 section 4.1.3
	} else {
		if ($index !== strrpos($IPv6,'::'))			return $diagnose ? ISEMAIL_IPV6DOUBLEDOUBLECOLON : false;	// More than one '::'
		$groupMax = ($index === 0 || $index === (strlen($IPv6) - 2)) ? $groupMax : $groupMax - 1;
		if ($groupCount > $groupMax)				return $diagnose ? ISEMAIL_IPV6TOOMANYGROUPS	: false;	// Too many IPv6 groups in address
	}

	// Check for unmatched characters
	array_multisort($matchesIP[1], SORT_DESC);
	if ($matchesIP[1][0] !== '')					return $diagnose ? ISEMAIL_IPV6BADCHAR		: false;	// Illegal characters in address

	// It's a valid IPv6 address, so...
	return $diagnose ? ISEMAIL_VALID : true;
} else {
	// It's a domain name...

	// The syntax of a legal Internet host name was specified in RFC-952
	// One aspect of host name syntax is hereby changed: the
	// restriction on the first character is relaxed to allow either a
	// letter or a digit.
	// 	(http://tools.ietf.org/html/rfc1123#section-2.1)
	//
	// NB RFC 1123 updates RFC 1035, but this is not currently apparent from reading RFC 1035.
	//
	// Most common applications, including email and the Web, will generally not
	// permit...escaped strings
	// 	(http://tools.ietf.org/html/rfc3696#section-2)
	//
	// the better strategy has now become to make the "at least one period" test,
	// to verify LDH conformance (including verification that the apparent TLD name
	// is not all-numeric)
	// 	(http://tools.ietf.org/html/rfc3696#section-2)
	//
	// Characters outside the set of alphabetic characters, digits, and hyphen MUST NOT appear in domain name
	// labels for SMTP clients or servers
	// 	(http://tools.ietf.org/html/rfc5321#section-4.1.2)
	//
	// RFC5321 precludes the use of a trailing dot in a domain name for SMTP purposes
	// 	(http://tools.ietf.org/html/rfc5321#section-4.1.2)
	$dotArray	= /*. (array[int]string) .*/ preg_split('/\\.(?=(?:[^\\"]*\\"[^\\"]*\\")*(?![^\\"]*\\"))/m', $domain);
	$partLength	= 0;
	$element	= ''; // Since we use $element after the foreach loop let's make sure it has a value
	// revision 1.13: Line above added because PHPLint now checks for Definitely Assigned Variables

	if (count($dotArray) === 1)					return $diagnose ? ISEMAIL_TLD	: false;	// Mail host can't be a TLD (cite? What about localhost?)

	foreach ($dotArray as $element) {
		// Remove any leading or trailing FWS
		$element	= preg_replace("/^$FWS|$FWS\$/", '', $element);
		$elementLength	= strlen($element);

		// Each dot-delimited component must be of type atext
		// A zero-length element implies a period at the beginning or end of the
		// local part, or two periods together. Either way it's not allowed.
		if ($elementLength === 0)				return $diagnose ? ISEMAIL_DOMAINEMPTYELEMENT	: false;	// Dots in wrong place
		// revision 1.15: Speed up the test and get rid of "unitialized string offset" notices from PHP

		// Then we need to remove all valid comments (i.e. those at the start or end of the element
		if ($element[0] === '(') {
			$indexBrace = strpos($element, ')');
			if ($indexBrace !== false) {
				if (preg_match('/(?<!\\\\)[\\(\\)]/', substr($element, 1, $indexBrace - 1)) > 0) {
					// revision 1.17: Fixed name of constant (also spotted by turboflash - thanks!)
					return $diagnose ? ISEMAIL_BADCOMMENT_START	: false;	// Illegal characters in comment
				}
				$element	= substr($element, $indexBrace + 1, $elementLength - $indexBrace - 1);
				$elementLength	= strlen($element);
			}
		}

		if ($element[$elementLength - 1] === ')') {
			$indexBrace = strrpos($element, '(');
			if ($indexBrace !== false) {
				if (preg_match('/(?<!\\\\)(?:[\\(\\)])/', substr($element, $indexBrace + 1, $elementLength - $indexBrace - 2)) > 0)
				// revision 1.17: Fixed name of constant (also spotted by turboflash - thanks!)
				return $diagnose ? ISEMAIL_BADCOMMENT_END	: false;	// Illegal characters in comment

				$element	= substr($element, 0, $indexBrace);
				$elementLength	= strlen($element);
			}
		}

		// Remove any leading or trailing FWS around the element (inside any comments)
		$element = preg_replace("/^$FWS|$FWS\$/", '', $element);

		// What's left counts towards the maximum length for this part
		if ($partLength > 0) $partLength++;	// for the dot
		$partLength += strlen($element);

		// The DNS defines domain name syntax very generally -- a
		// string of labels each containing up to 63 8-bit octets,
		// separated by dots, and with a maximum total of 255
		// octets.
		// 	(http://tools.ietf.org/html/rfc1123#section-6.1.3.5)
		if ($elementLength > 63)				return $diagnose ? ISEMAIL_DOMAINELEMENTTOOLONG	: false;	// Label must be 63 characters or less

		// Any ASCII graphic (printing) character other than the
		// at-sign ("@"), backslash, double quote, comma, or square brackets may
		// appear without quoting.  If any of that list of excluded characters
		// are to appear, they must be quoted
		// 	(http://tools.ietf.org/html/rfc3696#section-3)
		//
		// If the hyphen is used, it is not permitted to appear at
		// either the beginning or end of a label.
		// 	(http://tools.ietf.org/html/rfc3696#section-2)
		//
		// Any excluded characters? i.e. 0x00-0x20, (, ), <, >, [, ], :, ;, @, \, comma, period, "
		if (preg_match('/[\\x00-\\x20\\(\\)<>\\[\\]:;@\\\\,\\."]|^-|-$/', $element) > 0) {
			return $diagnose ? ISEMAIL_DOMAINBADCHAR	: false;
		}
	}

	if ($partLength > 255) 						return $diagnose ? ISEMAIL_DOMAINTOOLONG	: false;	// Domain part must be 255 characters or less (http://tools.ietf.org/html/rfc1123#section-6.1.3.5)

	if (preg_match('/^[0-9]+$/', $element) > 0)			return $diagnose ? ISEMAIL_TLDNUMERIC		: false;	// TLD can't be all-numeric (http://www.apps.ietf.org/rfc/rfc3696.html#sec-2)

	// Check DNS?
	if ($checkDNS && function_exists('checkdnsrr')) {
		if (!(checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX'))) {
			return $diagnose ? ISEMAIL_DOMAINNOTFOUND	: false;	// Domain doesn't actually exist
		}
	}
}

// Eliminate all other factors, and the one which remains must be the truth.
// 	(Sherlock Holmes, The Sign of Four)
return $diagnose ? ISEMAIL_VALID : true;
}