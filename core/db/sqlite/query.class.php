<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDBSQLiteQuery
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: DB/SQLite/Query
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


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
		 *  execute given query on given connection
		 *  @name    execute
		 *  @type    method
		 *  @access  public
		 *  @param   string   query
		 *  @param   resource connection
		 *  @return  void
		 *  @syntax  void CoreDBSQLiteQuery->execute( string query, resource connection )
		 */
		public function execute( $sQuery, &$rConnection )
		{
			$this->_replace = Array(
				"NOW()"=>microtime( true )
			);
			$this->query   = str_replace( array_keys( $this->_replace ), array_values( $this->_replace ), $sQuery );
			$this->_conn   = $rConnection;
			$this->_result = @sqlite_query( $this->query, $this->_conn, SQLITE_BOTH, $sError );

			if ( is_resource( $this->_result ) )
				$this->rows = sqlite_num_rows( $this->_result );

			//  We want the exception object to tell us everything is going extremely well, don't throw it!
			$this->import( "../exception.class.php" );
			$this->exception = new CoreDBSQLiteException( sqlite_last_error( $this->_conn ) );
			$this->errno     = &$this->exception->errno;
			$this->error     = &$this->exception->error;
		}

		/**
		 *  rewind the internal resultset
		 *  @name    rewind
		 *  @type    method
		 *  @access  public
		 *  @return  bool success
		 *  @syntax  bool CoreDBSQLiteQuery->rewind()
		 */
		public function rewind()
		{
			if ( is_resource( $this->_result ) && sqlite_num_rows( $this->_result ) > 0 )
				return sqlite_rewind( $this->_result );
			return false;
		}

		/**
		 *  get the next result from the internal resultset
		 *  @name    next
		 *  @type    method
		 *  @access  public
		 *  @return  object resultrow
		 *  @syntax  object CoreDBSQLiteQuery->next()
		 */
		public function next()
		{
			if ( is_resource( $this->_result ) )
				return sqlite_fetch_object( $this->_result );
			return false;
		}

		/**
		 *  get the ID of the last inserted record
		 *  @name    lastInsertID
		 *  @type    method
		 *  @access  public
		 *  @return  int id
		 *  @syntax  int CoreDBSQLiteQuery->lastInsertID()
		 */
		public function lastInsertID()
		{
			return sqlite_last_insert_rowid( $this->_conn );
		}

		/**
		 *  get the ID of the last inserted record
		 *  @name    lastInsertID
		 *  @type    method
		 *  @access  public
		 *  @return  int id
		 *  @syntax  int CoreDBSQLiteQuery->lastId()
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
		 *  @syntax  array CoreDBSQLiteQuery->fetchAll()
		 */
		public function fetchAll()
		{
			$aReturn = Array();
			while( $oRecord = $this->next() )
				array_push( $aReturn, $oRecord );
			$this->rewind();
			return $aReturn;
		}
	}

?>