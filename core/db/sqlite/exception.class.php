<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDBSQLiteException
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: DB/SQLite/Exception
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  MySQL specific Exception class
	 *  @name    CoreDBSQLiteException
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
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
		 *  @param   string error
		 *  @param   int    errornumber
		 *  @return  object
		 *  @syntax  object = &new CoreDBSQLiteException( string error, int errornumber )
		 *  @note    This object is constructed by CoreDBSQLite as 'status report'
		 */
		public function __construct( $nError )
		{
			$this->error = sqlite_error_string( $nError );
			$this->errno = $nError;
		}
	}

?>