<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUserTracker
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: User/Tracker
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  User tracking
	 *  @name    CoreUserTracker
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreUserTracker extends Konsolidate
	{
		/**
		 *  Whether or not to use autologin
		 *  @name    _autologin
		 *  @type    boolean
		 *  @access  protected
		 */
		protected $_autologin;

		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->id           = null;
			$this->code         = null;
			$this->last         = null;
			$this->cookiename   = $this->get( "/Config/Cookie/name", "KONSOLIDATETRACKER" );
			$this->cookiedomain = $this->get( "/Config/Cookie/domain", $_SERVER[ "HTTP_HOST" ] );
			$this->_autologin   = $this->get( "/Config/Tracker/autologin", true );
		}

		/**
		 *  Load visitor base information
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @return  int  tracker id
		 *  @syntax  int CoreUserTracker->load();
		 *  @note    The visitor is loaded from the cookie data set by the create method, if there was no cookie found, a new visitor id/code will be created
		 *           Should the create call return false, a total of 5 attempts will be done to try to make sure a new visitor can be created
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
		 *  @return  bool
		 *  @syntax  bool CoreUserTracker->loadFromCookie();
		 */
		public function loadFromCookie()
		{
			$this->code = array_key_exists( $this->cookiename, $_COOKIE ) ? $_COOKIE[ $this->cookiename ] : false;
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
		 *  @return  bool
		 *  @syntax  bool CoreUserTracker->create();
		 */
		function create()
		{
			$this->code = $this->createCode();
			$sQuery     = "INSERT INTO usertracker ( ustcode, ustmodifiedts, ustcreatedts, ustlastvisitts ) VALUES ( '{$this->code}', NOW(), NOW(), NOW() )";
			$oResult    = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows == 1 )
			{
				$this->id = $this->call( "/DB/lastId" );
				return $this->storeCookie( true, $this->_autologin );
			}
			return false;
		}

		/**
		 *  Write the cookie to the user agent
		 *  @name    storeCookie
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreUserTracker->storeCookie();
		 */
		public function updateVisit()
		{
			$sQuery  = "UPDATE usertracker
						   SET ustlastvisitts=NOW()
						 WHERE ustid={$this->id}";
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 )
				return $this->storeCookie( false, $this->_autologin );
			return false;
		}

		/**
		 *  Write the cookie to the user agent
		 *  @name    storeCookie
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreUserTracker->storeCookie();
		 *  @note    Providing a value other than the default for 'autologin' only applies within 
		 *           the current script execution scope! When entering a new page (script execution) 
		 *           all behaviour is set to default.
		 */
		public function storeCookie( $bClearFirst=true, $bAutoLogin=null )
		{
			if ( !is_null( $bAutoLogin ) )
				$this->_autologin = (bool) $bAutoLogin;

			if ( !headers_sent() )
			{
				if ( $bClearFirst )
					$_COOKIE[ $this->cookiename  ] = $this->code;
				$mAutoLogin = $this->_autologin ? time() + ( 60 * 60 * 24 * 30 ) : null;
				return setCookie( $this->cookiename, $this->code, $mAutoLogin, "/", $this->cookiedomain );
			}
			return false;
		}

		/**
		 *  Create a unique code
		 *  @name    createCode
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreUserTracker->createCode();
		 */
		public function createCode()
		{
			return md5(
				$this->call( "/Tool/arrVal", $_SERVER, "HTTP_USER_AGENT", microtime( true ) ) . 
				$this->call( "/Tool/arrVal", $_SERVER, "REMOTE_ADDR", rand( 0, pow( 2, 32 ) ) ) .
				$this->call( "/Tool/arrVal", $_SERVER, "REMOTE_PORT", rand( 0, pow( 2, 16 ) ) ) . 
				time() . 
				rand( 0, 10000 )
			);
		}


		/**
		 *  Log in a user
		 *  @name    login
		 *  @type    method
		 *  @access  public
		 *  @param   string  code
		 *  @param   bool    autologin [optional, default true]
		 *  @return  bool
		 *  @syntax  bool CoreUserTracker->login( string code [, bool autologin ] );
		 *  @note    Providing a value other than the default for 'autologin' only applies within 
		 *           the current script execution scope! When entering a new page (script execution) 
		 *           all behaviour is set to default.
		 */
		public function login( $sCode=false, $bAutoLogin=null )
		{
			if ( !is_null( $bAutoLogin ) )
				$this->_autologin = (bool) $bAutoLogin;

			if ( $sCode !== false )
			{
				$this->code = $sCode;
				return $this->storeCookie( true, $this->_autologin );
			}
			return false;
		}

		/**
		 *  Remove visitors that did not subscribe after a period of time
		 *  @name    removeUnregisteredVisitors
		 *  @type    method
		 *  @access  public
		 *  @param   integer   timestamp before which the unused records will be removed [optional, defaults to a week before now]
		 *  @return  bool
		 *  @syntax  bool CoreUserTracker->removeUnregisteredVisitors( [ int createdbefore ]);
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