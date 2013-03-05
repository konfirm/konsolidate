<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDBSQLite
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: DB/SQLite
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


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
		 *  @syntax  object = &new CoreDBQLite( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

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
		 *  @syntax  bool CoreDBSQLite->setConnection( string DSN [, bool newlink ] )
		 */
		public function setConnection( $sURI )
		{
			assert( is_string( $sURI ) );

			preg_match( "/([a-zA-Z]+):\/\/(.*)/", $sURI, $aParse );

			if ( is_array( $aParse ) && count( $aParse ) == 3 )
			{
				// 0 = $sURI
				// 1 = scheme
				// 2 = path (the remainder string)

				$sBasePath = $this->get( "/Config/SQLite/basepath" );
				$sDBPath   = realpath( substr( $aParse[ 2 ], 0, 1 ) == "/" ? dirname( $aParse[ 2 ] ) : ( !empty( $sBasePath ) ? $sBasePath : ( defined( "DOCUMENT_ROOT" ) ? DOCUMENT_ROOT : ( array_key_exists( "DOCUMENT_ROOT", $_SERVER ) ? $_SERVER[ "DOCUMENT_ROOT" ] : "" ) ) ) . "/" . dirname( $aParse[ 2 ] ) );
				$sDBFile   = basename( $aParse[ 2 ] );
				$this->_URI = Array(
					"scheme"=>$aParse[ 1 ],
					"path"=>( $sDBPath ? "{$sDBPath}/{$sDBFile}" : false )
				);
			}
			return true;
		}

		/**
		 *  Connect to the database
		 *  @name    connect
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreDBSQLite->connect()
		 *  @note    An explicit call to this method is not required, since the query method will create the connection if it isn't connected
		 */
		public function connect()
		{
			if ( !$this->isConnected() && $this->_URI[ "path" ] )
			{
				$this->_conn = @sqlite_open( $this->_URI[ "path" ], 0766, $sMessage );

				return $this->isConnected();
			}
			return true;
		}

		/**
		 *  Disconnect from the database
		 *  @name    disconnect
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreDBSQLite->disconnct()
		 */
		public function disconnect()
		{
			if ( $this->isConnected() )
				return sqlite_close( $this->_conn );
			return true;
		}

		/**
		 *  Check to see whether a connection is established
		 *  @name    isConnected
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreDBSQLite->isConnected()
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
		 *  @syntax  object CoreDBSQLite->query( string query [, bool usecache ] )
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
		 *  @syntax  int CoreDBSQLiteQuery->lastInsertID()
		 */
		public function lastInsertID()
		{
			if ( $this->isConnected() )
				return sqlite_last_insert_rowid( $this->_conn );
			return false;
		}

		/**
		 *  get the ID of the last inserted record
		 *  @name    lastId
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
		 *  Properly escape a string
		 *  @name    escape
		 *  @type    method
		 *  @access  public
		 *  @param   string input
		 *  @return  string escaped input
		 *  @syntax  string CoreDBSQLiteQuery->escape( string input )
		 */
		public function escape( $sString )
		{
			return sqlite_escape_string( $sString );
		}

		/**
		 *  Quote and escape a string
		 *  @name    quote
		 *  @type    method
		 *  @access  public
		 *  @param   string input
		 *  @return  string quoted escaped input
		 *  @syntax  string CoreDBSQLiteQuery->quote( string input )
		 */
		public function quote( $sString )
		{
			return "'" . $this->escape( $sString ) . "'";
		}

		/**
		 *  Determine whether a query should be cached (this applies only to 'SELECT' queries)
		 *  @name    _isCachableQuery
		 *  @type    method
		 *  @access  protected
		 *  @param   string query
		 *  @return  bool   success
		 *  @syntax  bool CoreDBSQLiteQuery->_isCachableQuery( string query )
		 */
		public function _isCachableQuery( $sQuery )
		{
			return (bool) preg_match( "/^\s*SELECT /i", $sQuery );
		}
	}

?>