<?php


/**
 *  SQLite result set (this object is instanced and returned for every query)
 *  @name    CoreDBSQLiteQuery
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBSQLiteQuery extends Konsolidate
{
	/**
	 *  Internal auto-replacements, in order to gain common SQL functionality (e.g. 'NOW()')
	 *  @name    _replace
	 *  @type    array
	 *  @access  protected
	 */
	protected $_replace;

	/**
	 *  The error number
	 *  @name    errno
	 *  @type    int
	 *  @access  public
	 */
	public $errno;

	/**
	 *  The error message
	 *  @name    error
	 *  @type    string
	 *  @access  public
	 */
	public $error;



	/**
	 *  CoreDBSQLiteQuery constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   object        parent object
	 *  @param   SQLite3       connection object
	 *  @param   SQLite3Result result
	 *  @return  object
	 */
	public function __construct(Konsolidate $parent, SQLite3 $connection=null, SQLite3Result $result=null)
	{
		parent::__construct($parent);

		if ($connection)
			$this->_conn = $connection;
		if ($result)
			$this->_result = $result;

		if ($this->_conn && $this->_result)
			$this->_populate();
	}

	/**
	 *  execute query on connection
	 *  @name    execute
	 *  @type    method
	 *  @access  public
	 *  @param   string   query
	 *  @param   resource connection [optional, default null - use connection provided with construction]
	 *  @return  void
	 */
	public function execute($query, $connection=null)
	{
		$this->_replace = Array(
			'NOW()'=>microtime(true)
		);

		if ($connection)
			$this->_conn = $connection;

		$this->query   = str_replace(array_keys($this->_replace), array_values($this->_replace), $query);
		$this->_result = $this->_conn->query($this->query);

		$this->_populate();
	}

	/**
	 *  rewind the internal resultset
	 *  @name    rewind
	 *  @type    method
	 *  @access  public
	 *  @return  bool success
	 */
	public function rewind()
	{
		if ($this->_result instanceof SQLite3Result && $this->_result->numColumns() && $this->_result->columnType(0) !== SQLITE3_NULL)
			return $this->_result->reset();

		return false;
	}

	/**
	 *  get the next result from the internal resultset
	 *  @name    next
	 *  @type    method
	 *  @access  public
	 *  @return  object resultrow
	 */
	public function next()
	{
		if ($this->_result instanceof SQLite3Result)
		{
			$record = $this->_result->fetchArray(SQLITE3_ASSOC);
			if ($record)
				return (object) $record;
		}

		return false;
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
		return $this->isConnected() ? $this->_conn->lastInsertRowID() : false;
	}

	/**
	 *  get the ID of the last inserted record
	 *  @name    lastInsertID
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
	 *  Retrieve an array containing all resultrows as objects
	 *  @name    fetchAll
	 *  @type    method
	 *  @access  public
	 *  @return  array result
	 */
	public function fetchAll()
	{
		$return = Array();

		while (($record = $this->next()))
			array_push($return, $record);
		$this->rewind();

		return $return;
	}

	/**
	 *  Populate some defaults (rows, errno and error properties)
	 *  @name    _populate
	 *  @type    method
	 *  @access  protected
	 *  @return  void
	 */
	protected function _populate()
	{
		if ($this->_result)
			$this->rows = $this->_conn->changes();

		//  We want the exception object to tell us everything is going extremely well, don't throw it!
		$this->import('../exception.php');
		$this->exception = new CoreDBSQLiteException($this->_conn);
		$this->errno     = &$this->exception->errno;
		$this->error     = &$this->exception->error;
	}
}
