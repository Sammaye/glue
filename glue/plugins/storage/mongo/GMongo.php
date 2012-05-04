<?php

/**
 * Mongo Connection Class Plugin
 *
 * @author Sam Millman
 *
 * This is a plugin for the glue framework
 * which provides Mongo DB connectivity for
 * this app.
 */
class GMongo extends GApplicationComponent{

	/**
	 * The connection string for connecting with Db
	 *
	 * @access protected
	 * @var string
	 */
	public $connection;

	/**
	 * Set to true for persistent connections
	 *
	 * @access protected
	 * @var boolean
	 */
	public $persistent = true;

	/**
	 * Set to true for autoconnect
	 *
	 * @access protected
	 * @var boolean
	 */
	public $autoConnect = true;

	/**
	 * Name of Db to connect with
	 *
	 * @access protected
	 * @var string
	 */
	public $db;

	public $indexPath;

	private $_indexes;

	/**
	 * The mongo instance
	 *
	 * @var Mongo
	 */
	private $_mongo;

	public function __get($key){
		return $this->getCollection($key);
	}

	/**
	 * Destructor
	 *
	 * Will call close on the Db connection
	 */
	function __destruct(){
		$this->close();
	}

	/** Construct */
	function init(){

		$this->_mongo = new Mongo($this->connection, array("connect" => $this->autoConnect, "persist" => $this->persistent));

		if($this->autoConnect){
			$this->_mongo->connect();
		}

		if(empty($this->_mongo)){
			trigger_error("Could not connect to DB with connection string ".$this->connection);
		}else{
			return $this;
		}
	}

	/**
	 * Opens a connection with the Db
	 */
	public function connect(){
		$this->_mongo->connect();
	}

	/**
	 * This function drops a database
	 *
	 * This function drops and effectively deletes
	 * the database from mongo
	 *
	 * @return boolean $fail
	 */
	public function drop(){
		if(empty($this->db)){
			return false;
		}

		$this->_mongo->drop($this->db);
	}

	/**
	 * This will get the raw collection
	 *
	 * This function returns the pointer
	 * to a raw collection object
	 *
	 * @param string $collectionName
	 * @throws Exception
	 * @return MongoCollection $collection The collection found
	 */
	public function getCollection($collectionName){

		if(!$this->_mongo){

			/** Cannot connect to nothing */
			trigger_error("Mongo Object was empty whilst attempting to get a collection");
		}

		if($collectionName == '' || !$collectionName){
			var_dump(debug_backtrace());
			var_dump($collectionName); exit();
		}

		$collection = $this->getDb()->selectCollection($collectionName);

		if($collection != '_sub'){
			if(!$this->_indexes){
				$this->_indexes = glue::import('_mongoIndex', $this->indexPath);
			}

			if(isset($this->_indexes[$collectionName])){
				$index_info = $this->_indexes[$collectionName];

				foreach($index_info as $k=>$v){
					$collection->ensureIndex($v[0], isset($v[1]) ? $v[1] : array());
				}
			}
		}

		/** Return the mongo collection found */
		return $collection;
	}

	/**
	 * Get GridFS
	 */
	function getGridFS(){
		if(!$this->_mongo){

			/** Cannot connect to nothing */
			trigger_error("Mongo Object was empty whilst attempting to get a GridFS");
		}

		return $this->getDb()->getGridFS();
	}

	/**
	 * Get a raw DB object
	 *
	 * This function gets a raw Database object
	 * from the mongo connection. If the mongo
	 * connection is not active then it will throw
	 * a new exception.
	 *
	 * @throws Exception
	 * @return Mongo $database
	 */
	public function getDb($dbname = null){

		if($dbname) $this->db = $dbname;

		if(empty($this->_mongo)){
			trigger_error("Mongo Object was empty whilst attempting to get a Database");
		}

		return $this->_mongo->selectDB($this->db);
	}

	/**
	 * Run a terminal command
	 *
	 * This function provides the jumper required
	 * to produce and execute terminal (base) commands
	 * on the mongo DB itself. This command does not
	 * concern itself with querying more with settings
	 * and configuration.
	 *
	 * @param mixed $data
	 * @return Mongo $results
	 */
	public function command($data){
		return $this->getDb()->command($data);
	}

	function getActiveCursor($mongoCursor, $className, $isMR = false){
		return new GMongoCursor($mongoCursor, $className, $isMR);
	}

	/**
	 * Close Mongo connection
	 *
	 * It is incredibly rare you would wish
	 * to close the Database connection but when you
	 * do this function will allow you to do just
	 * that.
	 *
	 * Please Note: This function has no return and
	 * the persistant connection variable must be set
	 * to false for this function to run correctly.
	 *
	 * @param void
	 * @return void
	 */
	public function close(){
		if($this->_mongo){
			if(!$this->persistent){
				$this->_mongo->close();
			}
		}
	}

    /**
     * Stop users from cloning the Singleton
     */
    public function __clone(){
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }
}