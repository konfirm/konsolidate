<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDB
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: DB
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  DB Layer for DB connectivity
	 *  @name    CoreDB
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreDB extends Konsolidate
	{
		/**
		 *  The database/connection pool
		 *  @name    _pool
		 *  @type    array
		 *  @access  protected
		 */
		protected $_pool;

		/**
		 *  The default connection (usually the first connection defined)
		 *  @name    _default
		 *  @type    string
		 *  @access  protected
		 */
		protected $_default;

		/**
		 *  CoreDB constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreDB( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );

			$this->_pool    = Array();
			$this->_default = false;
		}

		/**
		 *  Create a fully prepared database object
		 *  @name    setConnection
		 *  @type    method
		 *  @access  public
		 *  @param   string connection reference
		 *  @param   string connection URI
		 *  @return  bool
		 *  @note    the URI is formatted like: scheme://user:pass@host[:port]/database
		 *           providing an unique reference provides you to ability to use more than one connection
		 */
		public function setConnection( $sReference, $sURI )
		{
			$sReference = strToUpper( $sReference );
			$aURI       = parse_url( $sURI );

			if ( $this->_default === false )
				$this->_default = $sReference;

			$this->_pool[ $sReference ] = $this->instance( $aURI[ "scheme" ] );

			if ( is_object( $this->_pool[ $sReference ] ) )
				return $this->_pool[ $sReference ]->setConnection( $sURI, true );
			return false;
		}

		/**
		 *  Set the default DB connection, if it exists
		 *  @name    setDefaultConnection
		 *  @type    method
		 *  @access  public
		 *  @param   string connection reference
		 *  @return  string reference
		 *  @note    By default the first connection will be the default connection, a call to the setDefaultConnection 
		 *           is only required if you want to change this behaviour
		 */
		public function setDefaultConnection( $sReference )
		{
			$sReference = strToUpper( $sReference );
			if ( array_key_exists( $sReference, $this->_pool ) && is_object( $this->_pool[ $sReference ] ) )
				return $this->_default = $sReference;
			return false;
		}

		/**
		 *  Connect a database/[scheme] instance
		 *  @name    connect
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  connect();
		 */
		public function connect()
		{
			if ( array_key_exists( $this->_default, $this->_pool ) && is_object( $this->_pool[ $this->_default ] ) )
				return $this->_pool[ $this->_default ]->connect();
			return false;
		}

		/**
		 *  Verify whether a connection is established
		 *  @name    isConnected
		 *  @type    method
		 *  @access  public
		 *  @param   string reference
		 *  @return  bool
		 *  @syntax  isConnected( string reference );
		 */
		public function isConnected()
		{
			if ( array_key_exists( $this->_default, $this->_pool ) && is_object( $this->_pool[ $this->_default ] ) )
				return $this->_pool[ $this->_default ]->isConnected();
			return false;
		}

		/**
		 *  Close the connection to a (or all) database(s)
		 *  @name    disconnect
		 *  @type    method
		 *  @access  public
		 *  @param   string reference (optional, default only the connection marked as 'default')
		 *  @return  bool
		 *  @syntax  disconnect( [ string reference ] );
		 */
		public function disconnect( $sReference=false )
		{
			if ( $sReference === true )
			{
				$bReturn = true;
				if ( $this->_connected )
					foreach( $this->_pool as $sKey=>$oDB )
						$bReturn &= $oDB->disconnect();
				return $bReturn;
			}
			else if ( $sReference === false )
				$sReference = $this->_default;

			if ( array_key_exists( $this->_default, $this->_pool ) && is_object( $this->_pool[ $sReference ] ) )
				return $this->_pool[ $this->_default ]->disconnect();
			return false;
		}

		/**
		 *  Execute a query on a database
		 *  @name    query
		 *  @type    method
		 *  @access  public
		 *  @param   string SQL-query
		 *  @param   bool   use cache (optional, default true)
		 *  @return  ResultObject
		 *  @syntax  query( string SQL [, string reference [, bool cache ] ] );
		 *  @note    the optional cache is per pageview and in memory only, it merely prevents 
		 *           executing the exact same query over and over again
		 */
		public function query( $sQuery, $bUseCache=true )
		{
			if ( $this->_default && array_key_exists( $this->_default, $this->_pool ) && is_object( $this->_pool[ $this->_default ] ) )
				return $this->_pool[ $this->_default ]->query( $sQuery, $bUseCache );
			return false;
		}

		/**
		 *  Return a DB-Scheme instance by it's name as it was set with setConnection, if not found in the pool, step back to the 
		 *  default behaviour of returning (stub) objects
		 *  @name    register
		 *  @type    method
		 *  @access  public
		 *  @param   string module/connection
		 *  @return  Object
		 *  @note    this method is an override to Konsolidates default behaviour
		 */
		public function register( $sModule )
		{
			$sReference = strToUpper( $sModule );
			if ( is_array( $this->_pool ) && array_key_exists( $sReference, $this->_pool ) && is_object( $this->_pool[ $sReference ] ) )
				return $this->_pool[ $sReference ];
			return parent::register( $sModule );
		}

		/**
		 *  Magic destructor, disconnects all DB connections
		 *  @name    __destruct
		 *  @type    method
		 *  @access  public
		 */
		public function __destruct()
		{
			$this->disconnect( true );
		}

		/**
		 *  Magic __call, implicit method bridge to defined connections
		 *  @name    __call
		 *  @type    method
		 *  @access  public
		 *  @note    By default all calls which are not defined in this class are bridged to the default connection
		 *  @see     setDefaultConnection
		 */
		public function __call( $sCall, $aArgument )
		{
			//  Get the first argument, which could be a reference to a pool item
			$sReference = (string) array_shift( $aArgument );

			//  In case the first argument was not a pool item, put the first argument back in refer to the master
			if ( !array_key_exists( $sReference, $this->_pool ) )
			{
				array_unshift( $aArgument, $sReference );
				$sReference = $this->_default;
			}

			if ( method_exists( $this->_pool[ $sReference ], $sCall ) )
				return call_user_func_array( 
					Array( 
						&$this->_pool[ $sReference ], // the database object
						$sCall                        // the method
					),
					$aArgument                        // the arguments
				);
			return parent::__call( $sCall, $aArgument );
		}
	}

?>