<?php
class GMySQL extends GApplicationComponent{

	public $host;
	public $user;
	public $password;
	public $db;

	private $link;

	function __construct(){}

	function init(){

		$this->link = mysql_connect($this->host, $this->user, $this->password);
		if(!$this->link)
			trigger_error('Could not connect to SQL server over TCP/IP');

		if(!mysql_select_db($this->db, $this->link))
			trigger_error('Could not use DB '.$this->db);

		return $this;
	}

	function findOne($query, $params = array()){
		$sql_row = array();
		$result = $this->query($query, $params);

		if(mysql_num_rows($result) <= 0){
			return null;
		}

		while($row = mysql_fetch_assoc($result)){
			$sql_row = $row;
		}
		return $sql_row;
	}

	function query($query, $params = array()){

		if(!$this->link)
			trigger_error('Could not query unconnected Database '.$this->db);

		if($query){
			if(count($params) > 0){
				foreach($params as $field => $value){
					if(is_array($value)){
						$in_array = array();
						foreach($value as $k => $v){
							$in_array[] = "'".mysql_real_escape_string($v)."'";
						}
						$query = str_replace($field, "(".implode(',', $in_array).")", $query);
					}else{
						$query = str_replace($field, "'".mysql_real_escape_string($value)."'", $query);
					}
				}
			}

			$result = mysql_query($query, $this->link) or $this->sqlerrorhandler("(".mysql_errno().") ".mysql_error(), $query, $_SERVER['PHP_SELF'], __LINE__);

			if(!is_bool($result)){
				if(mysql_num_rows($result) <= 0){
					return null;
				}
			}

			return $result;
		}else{
			trigger_error('Could not query nothing');
		}
	}

	function sqlerrorhandler($ERROR, $QUERY, $PHPFILE, $LINE){
		define("SQLQUERY", $QUERY);
		define("SQLMESSAGE", $ERROR);
		define("SQLERRORLINE", $LINE);
		define("SQLERRORFILE", $PHPFILE);
		trigger_error("(SQL)", E_USER_ERROR);
	}
}