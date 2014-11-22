<?php


/**
 *  MySQLi specific Exception class
 *  @name    CoreDBMySQLiException
 *  @type    class
 *  @package Core
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBMySQLiException extends Exception
{
	/**
	 *  The error message
	 *  @name    error
	 *  @type    string
	 *  @access  public
	 */
	public $error;

	/**
	 *  The error number
	 *  @name    error
	 *  @type    int
	 *  @access  public
	 */
	public $errno;

	/**
	 *  constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   resource connection
	 *  @returns object
	 *  @syntax  object = &new CoreDBMySQLiException( resource connection )
	 *  @note    This object is constructed by CoreDBMySQLi as 'status report'
	 */
	public function __construct()
	{
		$args = func_get_args();

		if (count($args) == 2)
		{
			$this->error = $args[0];
			$this->errno = $args[1];
		}
		else
		{
			$connection = array_shift($args);
			$this->error = $connection->error;
			$this->errno = $connection->errno;
		}
	}
}