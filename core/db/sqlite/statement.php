<?php


/**
 *  SQLite prepared statement
 *  @name    CoreDBSQLiteStatement
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBSQLiteStatement extends Konsolidate
{
	protected $_conn;
	protected $_statement;


	/**
	 *  CoreDBSQLiteStatement constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   object   parent object
	 *  @param   SQLite3  connection object
	 *  @param   string   query
	 *  @return  object
	 */
	public function __construct(Konsolidate $parent, SQLite3 $connection=null, $query=null)
	{
		parent::__construct($parent);

		$this->query      = $query;
		$this->_conn      = $connection;
		$this->_statement = $this->_conn->prepare($query);
	}

	/**
	 *  Execute the statement
	 *  @name    execute
	 *  @type    method
	 *  @access  public
	 *  @return  [Core]DBSQLiteQuery result (bool false on failure)
	 */
	public function execute()
	{
		$result = $this->_statement->execute();
		if ($result)
			return $this->instance('../query', $this->_conn, $result);

		return $result;
	}

	public function __call($method, $args)
	{
		$callable = Array($this->_statement, $method);
		if (!is_callable($callable))
			return parent::__call($method, $args);

		return call_user_func_array($callable, $args);
	}
}