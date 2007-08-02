<?php

	if ( !defined( "VISITOR_TRACKER_COOKIE" ) )
		define( "VISITOR_TRACKER_COOKIE", "KONSOLIDATETRACKER" );

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUserTracker
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: User/Tracker
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 43 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-06-02 20:41:54 +0200 (Sat, 02 Jun 2007) $
	 *               \___\/           
	 */
	class CoreUserTracker extends Konsolidate
	{
		/**
		 *  The visitor id
		 *  @name    id
		 *  @type    int
		 *  @access  public
		 */
		public $id;

		/**
		 *  The visitor code
		 *  @name    code
		 *  @type    string (32 characters)
		 *  @access  public
		 */
		public $code;

		/**
		 *  The unix timestamp of the last visit (pageview)
		 *  @name    last
		 *  @type    int (unix timestamp)
		 *  @access  public
		 */
		public $last;


		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->id         = null;
			$this->code       = null;
			$this->last       = null;
		}

		/**
		 *  Load visitor base information
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->load();
		 *  @note    The visitor is loaded from the cookie data set by the create method, if there was no cookie found, a new visitor id/code will be created
		 *           Should the create call return false, 4 retries will be done to make sure a new visitor can be created
		 */
		public function load()
		{
			if ( !$this->loadFromCookie() )
			{
				$nAttempt = 0;
				while( !$this->create() && $nAttempt < 5 )
					++$nAttempt;
			}
			else
			{
				//  Update the cookie, so we move the expire date a bit forward
				$this->updateVisit();
			}
			return $this->id;
		}

		/**
		 *  Load visitor from cookie
		 *  @name    loadFromCookie
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->loadFromCookie();
		 */
		public function loadFromCookie()
		{
			$this->code = array_key_exists( VISITOR_TRACKER_COOKIE, $_COOKIE ) ? $_COOKIE[ VISITOR_TRACKER_COOKIE ] : false;
			if ( $this->code === false )
				return false;
			$sQuery  = "SELECT ustid,
						       ustcode,
						       UNIX_TIMESTAMP( ustlastvisitts ) AS ustlastvisitts
						  FROM usertracker
						 WHERE ustcode='{$this->code}'";
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows == 1 )
			{
				$oData      = $oResult->next();
				if ( is_object( $oData ) )
				{
					$this->id   = (int) $oData->ustid;
					$this->code = $oData->ustcode;
					$this->last = $oData->ustlastvisitts;
				}
				return true;
			}
			return false;
		}

		/**
		 *  Create a new visitor (id/code)
		 *  @name    create
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->create();
		 */
		function create()
		{
			$this->code = $this->createCode();
			$sQuery     = "INSERT INTO usertracker ( ustcode, ustmodifiedts, ustcreatedts, ustlastvisitts ) VALUES ( '{$this->code}', NOW(), NOW(), NOW() )";
			$oResult    = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows == 1 )
			{
				$this->id = $this->call( "/DB/lastId" );
				return $this->storeCookie();
			}
			return false;
		}

		/**
		 *  Write the cookie to the user agent
		 *  @name    storeCookie
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->storeCookie();
		 */
		public function updateVisit()
		{
			$sQuery  = "UPDATE usertracker
						   SET ustlastvisitts=NOW()
						 WHERE ustid={$this->id}";
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 )
				return $this->storeCookie();
			return false;
		}

		/**
		 *  Write the cookie to the user agent
		 *  @name    storeCookie
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->storeCookie();
		 */
		public function storeCookie()
		{
			return setCookie( VISITOR_TRACKER_COOKIE, $this->code, time() + ( 60 * 60 * 24 * 30 ), "/" );
		}

		/**
		 *  Create a unique code
		 *  @name    createCode
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->createCode();
		 */
		public function createCode()
		{
			return md5(
				$_SERVER[ "HTTP_USER_AGENT" ] . 
				$_SERVER[ "REMOTE_ADDR" ] .
				$_SERVER[ "REMOTE_PORT" ] . 
				time() . 
				rand( 0, 10000 )
			);
		}


		public function login( $sCode=false )
		{
			if ( $sCode !== false )
			{
				$this->code = $sCode;
				return $this->storeCookie();
			}
			return false;
		}

		/**
		 *  Remove visitors that did not subscribe after a period of time
		 *  @name    removeUnregisteredVisitors
		 *  @type    method
		 *  @access  public
		 *  @param   integer   timestamp before which the unused records will be removed [optional] (defaults to a week before now)
		 *  @returns bool
		 *  @syntax  Object->removeUnregisteredVisitors( [ int createdbefore ]);
		 */
		public function removeUnregisteredTrackers( $nCreatedBeforeTS=false )
		{
			if ( $nCreatedBeforeTS === false )
				$nCreatedBeforeTS = time() - ( 60 * 60 * 24 * 7 ); // by default, remove unused entries older than one week

			if ( !empty( $nCreatedBeforeTS ) )
				$sWhere = "WHERE ustcreatedts < FROM_UNIXTIME( {$nCreatedBeforeTS} )";
			else
				$sWhere = "";

			$sQuery  = "DELETE FROM usertracker WHERE ustid NOT IN ( SELECT ustid FROM user {$sWhere})";
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 )
				return true;
			return false;
		}

	}

?>