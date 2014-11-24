<?php


/**
 *  SQLite (v2) Connectivity
 *  @name    CoreSQLite
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBSQLite extends Konsolidate
{
	/**
	 *  The connection resource
	 *  @name    _conn
	 *  @type    resource
	 *  @access  protected
	 */
	protected $_conn;

	/**
	 *  Was the user (and it's data) loaded
	 *  @name    _loaded
	 *  @type    bool
	 *  @access  protected
	 */
	protected $_cache;


	/**
	 *  constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   object parent object
	 *  @return  object
	 *  @note    This object is constructed by one of Konsolidates modules
	 */
	public function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$this->_conn  = null;
		$this->_cache = Array();
	}

	/**
	 *  Assign the connection DSN
	 *  @name    setConnection
	 *  @type    method
	 *  @access  public
	 *  @param   string DSN URI
	 *  @param   bool   force new link [optional, default false]
	 *  @return  bool
	 */
	public function setConnection($dsn)
	{
		assert(is_string($dsn));

		if (preg_match('/([a-zA-Z]+):\/\/(.*)/', $dsn, $parse))
		{
			$scheme = $parse[1];
			$path = $parse[2];

			if (!in_array($path[0], Array('/', ':')))
			{
				$documentRoot = defined('DOCUMENT_ROOT') ? DOCUMENT_ROOT : (!empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '');
				$path = realpath($this->get('/Config/SQLite/basepath', $documentRoot)) . '/' . $path;
			}

			if ($path)
			{
				$this->_URI = Array(
					'scheme' => $scheme,
					'path'   => $path
				);

				return true;
			}
		}

		return false;
	}

	/**
	 *  Connect to the database
	 *  @name    connect
	 *  @type    method
	 *  @access  public
	 *  @return  bool
	 *  @note    An explicit call to this method is not required, since the query method will create the connection if
	 *           it isn't connected
	 */
	public function connect()
	{
		$connected = $this->isConnected();

		if (!$connected && $this->_URI['path'])
		{
			$this->_conn = new SQLite3($this->_URI['path']);

			return $this->isConnected();
		}

		return $connected;
	}

	/**
	 *  Disconnect from the database
	 *  @name    disconnect
	 *  @type    method
	 *  @access  public
	 *  @return  bool
	 */
	public function disconnect()
	{
		if ($this->isConnected())
			$this->_conn->close();

		return true;
	}

	/**
	 *  Check to see whether a connection is established
	 *  @name    isConnected
	 *  @type    method
	 *  @access  public
	 *  @return  bool
	 */
	public function isConnected()
	{
		return $this->_conn ? true : false;
	}

	/**
	 *  Query the database
	 *  @name    query
	 *  @type    method
	 *  @access  public
	 *  @param   string query
	 *  @paran   bool   usecache [optional, default true]
	 *  @return  object result
	 */
	public function query($query, $cache=true)
	{
		$cacheKey = md5($query);
		if ($cache && isset($this->_cache[$cacheKey]))
		{
			$this->_cache[$cacheKey]->rewind();

			return $this->_cache[$cacheKey];
		}

		if ($this->connect())
		{
			$instance = $this->instance('Query');
			$instance->execute($query, $this->_conn);

			if ($cache && $this->_isCachableQuery($query))
				$this->_cache[$cacheKey] = $instance;

			return $instance;
		}

		return false;
	}

	public function prepare($query)
	{
		return $this->instance('Statement', $this->_conn, $query);
	}

	/**
	 *  get the ID of the last inserted record
	 *  @name    lastInsertID
	 *  @type    method
	 *  @access  public
	 *  @return  int id
	 */
	public function lastInsertID()
	{
		if ($this->isConnected())
			return $this->_conn->lastInsertRowID();

		return false;
	}

	/**
	 *  get the ID of the last inserted record
	 *  @name    lastId
	 *  @type    method
	 *  @access  public
	 *  @return  int id
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
	 *  @return  string escaped input
	 */
	public function escape($string)
	{
		return sqlite_escape_string($string);
	}

	/**
	 *  Quote and escape a string
	 *  @name    quote
	 *  @type    method
	 *  @access  public
	 *  @param   string input
	 *  @return  string quoted escaped input
	 */
	public function quote($string)
	{
		return '\'' . $this->escape($string) . '\'';
	}

	/**
	 *  Determine whether a query should be cached (this applies only to 'SELECT' queries)
	 *  @name    _isCachableQuery
	 *  @type    method
	 *  @access  protected
	 *  @param   string query
	 *  @return  bool   success
	 */
	public function _isCachableQuery($query)
	{
		return (bool) preg_match('/^\s*(?:SELECT|SHOW) /i', $query);
	}
}
