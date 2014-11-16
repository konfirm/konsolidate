<?php


/**
 *  MySQL specific Exception class
 *  @name    CoreDBMySQLException
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBMySQLException extends Exception
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
	 *  @return  object
	 *  @note    This object is constructed by CoreDBMySQL as 'status report'
	 */
	public function __construct($rConnection)
	{
		$this->error = is_resource($rConnection) ? mysql_error($rConnection) : mysql_error();
		$this->errno = is_resource($rConnection) ? mysql_errno($rConnection) : mysql_errno();
	}
}
