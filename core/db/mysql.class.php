<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDBMySQL
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: DB/MySQL
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  MySQL Connectivity
	 *  @name    CoreDBMySQL
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreDBMySQL extends Konsolidate
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
		 *  Whether or not to enforce a new connection to the same database
		 *  @name    _forceConnection
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_forceConnection;

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
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreDBMySQL( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->_URI             = null;
			$this->_conn            = null;
			$this->_cache           = Array();
			$this->error            = null;
			$this->_forceConnection = false;
			$this->_transaction     = false;
		}

		/**
		 *  Assign the connection DSN
		 *  @name    setConnection
		 *  @type    method
		 *  @access  public
		 *  @param   string DSN URI
		 *  @param   bool   force new link [optional, default false]
		 *  @return  bool
		 *  @syntax  bool CoreDBMySQL->setConnection( string DSN [, bool newlink ] )
		 */
		public function setConnection( $sURI, $bForceConnection=false )
		{
			assert( is_string( $sURI ) );
			assert( is_bool( $bForceConnection ) );

			$this->_URI             = parse_url( $sURI );
			$this->_forceConnection = $bForceConnection;
			return true;
		}

		/**
		 *  Connect to the database
		 *  @name    connect
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreDBMySQL->connect()
		 *  @note    An explicit call to this method is not required, since the query method will create the connection if it isn't connected
		 */
		public function connect()
		{
			if ( !$this->isConnected() )
			{
				$this->_conn = @mysql_connect( 
					"{$this->_URI[ "host" ]}:" . ( isset( $this->_URI[ "port" ] ) ? $this->_URI[ "port" ] : 3306 ), 
					$this->_URI[ "user" ],
					$this->_URI[ "pass" ],
					$this->_forceConnection 
				);

				if ( $this->_conn === false || !@mysql_select_db( trim( $this->_URI[ "path" ], "/" ) ) )
				{
					$this->import( "exception.class.php" );
					$this->error = new CoreDBMySQLException( $this->_conn );
					$this->_conn = null;
					return false;
				}
			}
			return true;
		}

		/**
		 *  Disconnect from the database
		 *  @name    disconnect
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreDBMySQL->disconnect()
		 */
		public function disconnect()
		{
			if ( $this->isConnected() )
				return mysql_close( $this->_conn );
			return true;
		}

		/**
		 *  Check to see whether a connection is established
		 *  @name    isConnected
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreDBMySQL->isConnected()
		 */
		public function isConnected()
		{
			return is_resource( $this->_conn );
		}

		/**
		 *  Query the database
		 *  @name    query
		 *  @type    method
		 *  @access  public
		 *  @param   string query
		 *  @paran   bool   usecache [optional, default true]
		 *  @return  object result
		 *  @syntax  object CoreDBMySQL->query( string query [, bool usecache ] )
		 */
		public function query( $sQuery, $bUseCache=true )
		{
			$sCacheKey = md5( $sQuery );
			if ( $bUseCache && array_key_exists( $sCacheKey, $this->_cache ) )
			{
				$this->_cache[ $sCacheKey ]->rewind();
				return $this->_cache[ $sCacheKey ];
			}

			if ( $this->connect() )
			{
				$oQuery = $this->instance( "Query" );
				$oQuery->execute( $sQuery, $this->_conn );

				if ( $bUseCache && $this->_isCachableQuery( $sQuery ) )
					$this->_cache[ $sCacheKey ] = $oQuery; 

				return $oQuery;
			}
			return false;
		}

		/**
		 *  get the ID of the last inserted record
		 *  @name    lastInsertID
		 *  @type    method
		 *  @access  public
		 *  @return  int id
		 *  @syntax  int CoreDBMySQLQuery->lastInsertID()
		 */
		public function lastInsertID()
		{
			if ( $this->isConnected() )
				return mysql_insert_id( $this->_conn );
			return false;
		}

		/**
		 *  get the ID of the last inserted record
		 *  @name    lastId
		 *  @type    method
		 *  @access  public
		 *  @return  int id
		 *  @syntax  int CoreDBMySQLQuery->lastId()
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
		 *  @syntax  string CoreDBMySQLQuery->escape( string input )
		 */
		public function escape( $sString )
		{
			if ( $this->connect() )
				return mysql_real_escape_string( $sString, $this->_conn );
			else if ( function_exists( "mysql_escape_string" ) )
				return mysql_escape_string( $sString );

			$this->call( "/Log/write", get_class( $this ) . "::escape, could not escape string" );
			return false;
		}

		/**
		 *  Quote and escape a string
		 *  @name    quote
		 *  @type    method
		 *  @access  public
		 *  @param   string input
		 *  @return  string quoted escaped input
		 *  @syntax  string CoreDBMySQLQuery->quote( string input )
		 */
		public function quote( $sString )
		{
			return "'" . $this->escape( $sString ) . "'";
		}

		/**
		 *  Start transaction
		 *  @name    startTransaction
		 *  @type    method
		 *  @access  public
		 *  @return  bool success
		 *  @syntax  bool CoreDBMySQLQuery->startTransaction()
		 */
		public function startTransaction()
		{
			if ( !$this->_transaction )
			{
				$oResult = $this->query( "START TRANSACTION" );
				if ( is_object( $oResult ) && $oResult->errno <= 0 )
					$this->_transaction = true;
			}
			return $this->_transaction;
		}

		/**
		 *  End transaction by sending 'COMMIT' or 'ROLLBACK'
		 *  @name    startTransaction
		 *  @type    method
		 *  @access  public
		 *  @param   bool commit [optional, default true]
		 *  @return  bool success
		 *  @syntax  bool CoreDBMySQLQuery->endTransaction( bool commit )
		 *  @note    if argument 'commit' is true, 'COMMIT' is sent, 'ROLLBACK' otherwise
		 */
		public function endTransaction( $bSuccess=true )
		{
			if ( $this->_transaction )
			{
				$oResult = $this->query( $bSuccess ? "COMMIT" : "ROLLBACK" );
				if ( is_object( $oResult ) && $oResult->errno <= 0 )
				{
					$this->_transaction = false;
					return true;
				}
			}
			return $this->_transaction;
		}

		/**
		 *  Commit a transaction
		 *  @name    commitTransaction
		 *  @type    method
		 *  @access  public
		 *  @return  bool success
		 *  @syntax  bool CoreDBMySQLQuery->commitTransaction()
		 *  @note    same as endTransaction( true );
		 */
		public function commitTransaction()
		{
			return $this->endTransaction( true );
		}

		/**
		 *  Rollback a transaction
		 *  @name    rollbackTransaction
		 *  @type    method
		 *  @access  public
		 *  @return  bool success
		 *  @syntax  bool CoreDBMySQLQuery->rollbackTransaction()
		 *  @note    same as endTransaction( false );
		 */
		public function rollbackTransaction()
		{
			return $this->endTransaction( false );
		}

		/**
		 *  Determine whether a query should be cached (this applies only to 'SELECT' queries)
		 *  @name    _isCachableQuery
		 *  @type    method
		 *  @access  protected
		 *  @param   string query
		 *  @return  bool   success
		 *  @syntax  bool CoreDBMySQLQuery->_isCachableQuery( string query )
		 */
		protected function _isCachableQuery( $sQuery )
		{
			return (bool) preg_match( "/^\s*SELECT /i", $sQuery );
		}
	}

?>