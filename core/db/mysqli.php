<?php


/**
 *  MySQL Connectivity
 *  @name    CoreDBMySQLi
 *  @type    class
 *  @package Core
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBMySQLi extends Konsolidate
{
	/**
	 *  The connection URI (parsed url)
	 *  @name    _URI
	 *  @type    array
	 *  @access  protected
	 */
	protected $_URI;

	/**
	 *  The connection resource
	 *  @name    _conn
	 *  @type    resource
	 *  @access  protected
	 */
	protected $_conn;

	/**
	 *  The query cache
	 *  @name    _cache
	 *  @type    array
	 *  @access  protected
	 */
	protected $_cache;

	/**
	 *  Wether or not a transaction is going on
	 *  @name    _transaction
	 *  @type    bool
	 *  @access  protected
	 */
	protected $_transaction;

	/**
	 *  The error object (Exception which isn't thrown)
	 *  @name    error
	 *  @type    object
	 *  @access  public
	 */
	public  $error;

	/**
	 *  Replacements for fingerprinting
	 *  @name    _fingerprintreplacement
	 *  @type    object
	 *  @access  protected
	 */
	protected $_fingerprintreplacement;


	/**
	 *  constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   object parent object
	 *  @returns object
	 *  @note    This object is constructed by one of Konsolidates modules
	 */
	public function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$this->_URI             = null;
		$this->_conn            = null;
		$this->_cache           = Array();
		$this->error            = null;
		$this->_transaction     = false;

		$this->_fingerprintreplacement = Array(
			'string' => $this->get('/Config/MySQL/fingerprint_string', '\'$\''),
			'number' => $this->get('/Config/MySQL/fingerprint_number', '#'),
			'NULL'   => $this->get('/Config/MySQL/fingerprint_null', 'NULL'),
			'names'  => $this->get('/Config/MySQL/fingerprint_names', '`?`')
		);
	}

	/**
	 *  Assign the connection DSN
	 *  @name    setConnection
	 *  @type    method
	 *  @access  public
	 *  @param   string DSN URI
	 *  @returns bool
	 */
	public function setConnection($dsn)
	{
		assert(is_string($dsn));

		$this->_URI = parse_url($dsn);
		if (empty($this->_URI['host']))
			$this->exception('Missing required host from the MySQLi DSN \'' . $dsn . '\'');
		else if (empty($this->_URI['user']))
			$this->exception('Missing required username from the MySQLi DSN \'' . $dsn . '\'');

		return true;
	}

	/**
	 *  Connect to the database
	 *  @name    connect
	 *  @type    method
	 *  @access  public
	 *  @returns bool
	 *  @note    An explicit call to this method is not required, since the query method will create the connection if
	 *           it isn't connected
	 */
	public function connect()
	{
		if (!$this->isConnected())
		{
			$this->_conn = new MySQLi(
				$this->_URI['host'],
				$this->_URI['user'],
				array_key_exists('pass', $this->_URI) ? $this->_URI['pass'] : '',
				trim($this->_URI['path'], '/'),
				isset($this->_URI['port']) ? $this->_URI['port'] : 3306
			);

			if (phpversion() > '5.3.0' ? $this->_conn->connect_error : mysqli_connect_error())
				$this->exception($this->_conn->connect_error, $this->_conn->connect_errno);
		}

		return true;
	}

	/**
	 *  Disconnect from the database
	 *  @name    disconnect
	 *  @type    method
	 *  @access  public
	 *  @returns bool
	 */
	public function disconnect()
	{
		if ($this->isConnected())
			return $this->_conn->close();

		return true;
	}

	/**
	 *  Check to see whether a connection is established
	 *  @name    isConnected
	 *  @type    method
	 *  @access  public
	 *  @returns bool
	 */
	public function isConnected()
	{
		return is_object($this->_conn);
	}

	/**
	 *  Query the database
	 *  @name    query
	 *  @type    method
	 *  @access  public
	 *  @param   string query
	 *  @paran   bool   usecache (default true)
	 *  @paran   bool   add info (default false)
	 *  @paran   bool   extended info (default true)
	 *  @returns object result
	 *  @note    Requesting extended information will automatically enable normal info aswel
	 */
	public function query($query, $cache=true, $addInfo=false, $extendedInfo=false)
	{
		$cacheKey = md5($query);
		if ($cache && isset($this->_cache[$cacheKey]))
		{
			$this->_cache[$cacheKey]->rewind();
			$this->_cache[$cacheKey]->cached = true;

			return $this->_cache[$cacheKey];
		}

		if ($this->connect())
		{
			$result = $this->instance('Query');
			$result->execute($query, $this->_conn);
			$result->info   = $addInfo || $extendedInfo ? $this->info($extendedInfo, Array('duration'=>$result->duration)) : 'additional query info not processed';
			$result->cached = false;

			if ($cache && $this->_isCachableQuery($query))
				$this->_cache[$cacheKey] = $result;

			return $result;
		}

		return false;
	}

	/**
	 *  create a fingerprint for given query, attempting to remove all variable components
	 *  @name    fingerprint
	 *  @type    method
	 *  @access  public
	 *  @param   string   query
	 *  @param   bool     hash output (default true)
	 *  @param   bool     strip escaped names (default false)
	 *  @returns string   fingerprint
	 */
	public function fingerprint($query, $hash=true, $stripNames=false, $replace=Array())
	{
		$replace = (object) array_merge($this->_fingerprintreplacement, $replace);
		$string  = $replace->string;
		$number  = $replace->number;
		$NULL    = $replace->NULL;
		$names   = $replace->names;

		$replace = Array(
			//  replace quoted variables
			'/([\"\'])(?:.*[^\\\\]+)*(?:(?:\\\\{2})*)+\1/xU' => $string,
			//  strip '--' comments
			'/--.*?[\r\n]+/' => '',
			//  strip '#' comments
			'/#.*?[\r\n]+/' => '',
			//  strip /* */ comments
			'/\/\*.*?\*\//' => '',
			//  strip whitespace left of specific chars
			'/\s*([\(\)=\+\/,-]+)/' => '\\1',
			//  strip whitespace right of specific chars
			'/([\(=\+\/,-]+)\s*/' => '\\1',
			//  replace numbers which appear to be values
			'/\b[0-9]*[\.]*[0-9]+\b/' => $number,
			//  replace NULL values
			'/\bNULL\b/i' => $NULL,
            //  replace (multiple) whitespace chars by space
			'/\s+/' => ' '
		);

		if ($stripNames)
			$replace['/`.*?`/'] = $names;

		$result = trim(preg_replace(array_keys($replace), array_values($replace), $query));

		//  now it has become easy to detect multiple queries, which we do not support, so we narrow down the query
		$result = explode(';', trim($result, ';'))[0];

		return $hash ? md5($result) : $result;
	}

	/**
	 *  get the ID of the last inserted record
	 *  @name    lastInsertID
	 *  @type    method
	 *  @access  public
	 *  @returns int id
	 */
	public function lastInsertID()
	{
		if ($this->isConnected())
			return mysqli_insert_id($this->_conn);

		return false;
	}

	/**
	 *  get the ID of the last inserted record
	 *  @name    lastId
	 *  @type    method
	 *  @access  public
	 *  @returns int id
	 *  @note    alias for lastInsertID
	 *  @see     lastInsertID
	 */
	public function lastId()
	{
		return $this->lastInsertID();
	}

	/**
	 *  Properly escape a string
	 *  @name    escape
	 *  @type    method
	 *  @access  public
	 *  @param   string input
	 *  @returns string escaped input
	 */
	public function escape($input)
	{
		if ($this->connect())
			return mysqli_real_escape_string($this->_conn, $input);

		$this->call('/Log/write', get_class($this) . '::escape, could not escape string: ' . $input);

		return false;
	}

	/**
	 *  Quote and escape a string
	 *  @name    quote
	 *  @type    method
	 *  @access  public
	 *  @param   string input
	 *  @returns string quoted escaped input
	 */
	public function quote($input)
	{
		return '\'' . $this->escape($input) . '\'';
	}

	/**
	 *  Start transaction
	 *  @name    startTransaction
	 *  @type    method
	 *  @access  public
	 *  @returns bool success
	 */
	public function startTransaction()
	{
		if ($this->connect() && !$this->_transaction)
			$this->_transaction = $this->_conn->autocommit(false);

		return $this->_transaction;
	}

	/**
	 *  End transaction by sending 'COMMIT' or 'ROLLBACK'
	 *  @name    startTransaction
	 *  @type    method
	 *  @access  public
	 *  @param   bool commit [optional, default true]
	 *  @returns bool success
	 *  @note    if argument 'commit' is true, 'COMMIT' is sent, 'ROLLBACK' otherwise
	 */
	public function endTransaction($success=true)
	{
		if ($this->_transaction)
			$this->_transaction = !($success ? $this->_conn->commit() : $this->_conn->rollback());

		return !$this->_transaction;
	}

	/**
	 *  Commit a transaction
	 *  @name    commitTransaction
	 *  @type    method
	 *  @access  public
	 *  @returns bool success
	 *  @note    same as endTransaction(true);
	 */
	public function commitTransaction()
	{
		return $this->endTransaction(true);
	}

	/**
	 *  Rollback a transaction
	 *  @name    rollbackTransaction
	 *  @type    method
	 *  @access  public
	 *  @returns bool success
	 *  @note    same as endTransaction(false);
	 */
	public function rollbackTransaction()
	{
		return $this->endTransaction(false);
	}



	//  As MySQLi has a lot more to offer than MySQL, we provide extra methods
	//  NOTE: be aware that by using these methods you will loose some flawless compatibility
	//        with the normal MySQL engine.


	/**
	 *  Returns the default character set for the database connection
	 *  @name    characterSetName
	 *  @type    method
	 *  @access  public
	 *  @returns string characterset
	 */
	public function characterSetName()
	{
		return $this->_conn->character_set_name();
	}

	/**
	 *  Returns the MySQLi client version
	 *  @name    clientVersion
	 *  @type    method
	 *  @access  public
	 *  @param   bool versionstring [optional, default false]
	 *  @returns int  version
	 */
	public function clientVersion($versionString=false)
	{
		if ($versionString)
			return $this->_versionToString($this->_conn->client_version);

		return $this->_conn->client_version;
	}

	/**
	 *  Returns the MySQLi client info
	 *  @name    clientInfo
	 *  @type    method
	 *  @access  public
	 *  @returns string info
	 *  @note    the client info may appear to be the version as string, but can contain
	 *           additional build information, use clientVersion(true) for fool-proof
	 *           string version comparing
	 */
	public function clientInfo()
	{
		return $this->_conn->client_info;
	}

	/**
	 *  Returns the MySQLi server version
	 *  @name    serverVersion
	 *  @type    method
	 *  @access  public
	 *  @param   bool versionstring [optional, default false]
	 *  @returns int  version (false if a connection could not be established)
	 */
	public function serverVersion($versionString=false)
	{
		if (!$this->connect())
			return false;

		if ($versionString)
			return $this->_versionToString($this->_conn->server_version);

		return $this->_conn->server_version;
	}

	/**
	 *  Returns the MySQLi server info
	 *  @name    serverInfo
	 *  @type    method
	 *  @access  public
	 *  @returns string info
	 *  @note    the server info may appear to be the version as string, but can contain
	 *           additional build information, use serverVersion(true) for fool-proof
	 *           string version comparing
	 */
	public function serverInfo()
	{
		return $this->_conn->server_info;
	}

	/**
	 *  Retrieves information about the most recently executed query
	 *  @name    info
	 *  @type    method
	 *  @access  public
	 *  @param   bool extendedinfo [optional, default false]
	 *  @returns object info
	 *  @note    by requesting extended info, the connection stats are added to the info object
	 */
	public function info($extendInfo=false, $appendInfo=null)
	{
		$result = $this->instance('Info');
		$result->process($this->_conn, $extendInfo, $appendInfo);

		return $result;
	}

	/**
	 *  Convert the given version integer back to its string representation
	 *  @name    _versionToString
	 *  @type    method
	 *  @access  protected
	 *  @param   int    version
	 *  @returns string version
	 */
	protected function _versionToString($version)
	{
		$main  = round($version / 10000);
		$minor = round(($version - ($main * 10000)) / 100);

		return $main . '.' . $minor . '.' . ($version - (($main * 10000) + ($minor * 100)));
	}

	/**
	 *  Determine whether a query should be cached (this applies only to 'SELECT' queries)
	 *  @name    _isCachableQuery
	 *  @type    method
	 *  @access  protected
	 *  @param   string query
	 *  @returns bool   success
	 */
	protected function _isCachableQuery($query)
	{
		return (bool) preg_match('/^\s*(?:SELECT|SHOW)\b/i', $query);
	}
}
