<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	class CoreDBMySQL extends Konsolidate
	{
		private $_URI;
		private $_conn;
		private $_cache;
		private $_forceConnection;
		private $_transaction;
		public  $error;

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

		public function setConnection( $sURI, $bForceConnection=false )
		{
			assert( is_string( $sURI ) );
			assert( is_bool( $bForceConnection ) );

			$this->_URI             = parse_url( $sURI );
			$this->_forceConnection = $bForceConnection;
			return true;
		}

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

		public function disconnect()
		{
			if ( $this->isConnected() )
				return mysql_close( $this->_conn );
			return true;
		}

		public function isConnected()
		{
			return is_resource( $this->_conn );
		}

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

				if ( $this->_isCachableQuery( $sQuery ) )
					$this->_cache[ $sCacheKey ] = $oQuery; 

				return $oQuery;
			}
			return false;
		}

		public function lastInsertID()
		{
			if ( $this->isConnected() )
				return mysql_insert_id( $this->_conn );
			return false;
		}

		public function lastId()
		{
			return $this->lastInsertID();
		}

		public function escape( $sString )
		{
			return mysql_escape_string( $sString );
		}

		public function quote( $sString )
		{
			return "'" . $this->escape( $sString ) . "'";
		}

		public function startTransaction()
		{
			if ( !$this->_transaction )
			{
				$oResult = $this->query( "START TRANSACTION" );
				if ( is_object( $oResult ) && $oResult->error->errno <= 0 )
					$this->_transaction = true;
			}
			return $this->_transaction;
		}

		public function endTransaction( $bSuccess=true )
		{
			if ( $this->_transaction )
			{
				$oResult = $this->query( $bSuccess ? "COMMIT" : "ROLLBACK" );
				if ( is_object( $oResult ) && $oResult->error->errno <= 0 )
				{
					$this->_transaction = false;
					return true;
				}
			}
			return $this->_transaction;
		}

		public function commitTransaction()
		{
			return $this->endTransaction( true );
		}

		public function rollbackTransaction()
		{
			return $this->endTransaction( false );
		}

		public function _isCachableQuery( $sQuery )
		{
			return preg_match( "/^\s*SELECT /i", $sQuery );
		}
	}

?>