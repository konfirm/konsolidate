<?php


/**
 *  MySQL specific Exception class
 *  @name    CoreDBSQLiteException
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBSQLiteException extends Exception
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
	 *  @param   SQLite  instance
	 *  @return  object
	 *  @note    This object is constructed by CoreDBSQLite as 'status report'
	 */
	public function __construct($sqlite)
	{
		$this->error = $sqlite->lastErrorMsg();
		$this->errno = $sqlite->lastErrorCode();
	}
}
