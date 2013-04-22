<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSession
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Session
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Session support, aimed for use on WebFarms (Multiple webservers serving a single domain) by using the database as common resource
	 *  @name    CoreSession
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */

	class CoreSession extends  Konsolidate
	{
		/**
		 *  The session id
		 *  @name    _id
		 *  @type    string
		 *  @access  protected
		 */
		protected $_id;

		/**
		 *  Was the session started
		 *  @name    _started
		 *  @type    boolean
		 *  @access  protected
		 */
		protected $_started;

		/**
		 *  Session name
		 *  @name    _sessionname
		 *  @type    string
		 *  @access  protected
		 *  @note    value of /Config/Session/name or default 'KSESSION'
		 */
		protected $_sessionname;

		/**
		 *  Session duration (in seconds)
		 *  @name    _duration
		 *  @type    int
		 *  @access  protected
		 *  @note    value of /Config/Session/duration or default 1800 (30 minutes)
		 */
		protected $_duration;
		
		/**
		 *  Cookie domain
		 *  @name    _cookiedomain
		 *  @type    string
		 *  @access  protected
		 *  @note    value of /Config/Cookie/domain or default value of _SERVER[ "HTTP_HOST" ]
		 */
		protected $_cookiedomain;
		
		/**
		 *  Have one or more any of the properties changed
		 *  @name    _updated
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_updated;
		

		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreSession( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_id           = md5( $this->get( "/User/Tracker/id" ) . $this->get( "/User/Tracker/code" ) );
			$this->_started      = false;
			$this->_sessionname  = $this->get( "/Config/Session/name", "KSESSION" );
			$this->_duration     = $this->get( "/Config/Session/duration", 1800 ); // 30 minutes
			$this->_cookiedomain = $this->get( "/Config/Cookie/domain", $_SERVER[ "HTTP_HOST" ] );
			$this->_updated      = false;
		}

		/**
		 *  Start the session
		 *  @name    start
		 *  @type    method
		 *  @access  public
		 *  @param   string sessionname, optional defaults to protected _sessionname
		 *  @return  bool   success
		 *  @syntax  bool CoreSession->start( [ string sessionname ] );
		 */
		public function start( $sSessionName=null )
		{
			if ( !empty( $sSessionName ) && $sSessionName != $this->_sessionname )
			{
				$this->_sessionname = $sSessionName;
				$this->_started     = false;
			}

			if ( !$this->_started )
			{
				$sCookie        = $this->_getSessionCookie();
				$this->_started = $this->_setSessionCookie();

				if ( $sCookie === $this->_id )
				{
					$sQuery  = "SELECT sesdata 
								  FROM session 
								 WHERE ustid=" . $this->get( "/User/Tracker/id" ) . "
								   AND sescode=" . $this->call( "/DB/quote", $this->_id ) . "
								   AND UNIX_TIMESTAMP( NOW() ) - UNIX_TIMESTAMP( sesmodifiedts ) <= {$this->_duration}";
					$oResult = $this->call( "/DB/query", $sQuery );
					if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows == 1 )
					{
						$oRecord = $oResult->next();
						$this->_property = unserialize( $oRecord->sesdata );
						if ( !is_array( $this->_property ) )
						{
							$this->_property = Array();
							return false;
						}
						return true;
					}
				}
				else if ( $sCookie === false )
				{
					return true;
				}
			}
			else
			{
				return true;
			}

			return false;
		}

		/**
		 *  Register one or more session variables
		 *  @name    register
		 *  @type    method
		 *  @access  public
		 *  @param   string variable
		 *  @return  void
		 *  @syntax  void CoreSession->register( [ string variable [, string variable [, ... ] ] ] );
		 *  @note    Variables can also be assigned to a CoreSession directly using Konsolidate->set( "/Session/variable", "value" );
		 */
		public function register( $sModule )
		{
			$aRegister = func_get_args();
			if ( count( $aRegister ) == 1 && is_string( $sModule ) )
				if ( $this->checkModuleAvailability( $sModule ) || !array_key_exists( $sModule, $GLOBALS ) )
					return parent::register( $sModule );

			if ( $this->start() )
				foreach( $aRegister as $mVariable )
					if ( is_array( $mVariable ) )
						call_user_func_array( Array( &$this, "register" ), $mVariable );
					else
						$this->$mVariable = $GLOBALS[ $mVariable ];
		}

		/**
		 *  Remove one or more session variables from the session
		 *  @name    unregister
		 *  @type    method
		 *  @access  public
		 *  @param   string variable
		 *  @return  void
		 *  @syntax  void CoreSession->unregister( [ string variable [, string variable [, ... ] ] ] );
		 */
		public function unregister()
		{
			if ( $this->start() )
			{
				$aRegister = func_get_args();
				foreach( $aRegister as $mVariable )
					if ( is_array( $mVariable ) )
					{
						call_user_func_array( Array( &$this, "unregister" ), $mVariable );
					}
					else if ( $this->isRegistered( $mVariable ) )
					{
						$this->_updated = true;
						unset( $this->_property[ $mVariable ] );
					}
			}
		}

		/**
		 *  Create/update the session cookie
		 *  @name    _setSessionCookie
		 *  @type    method
		 *  @access  protected
		 *  @return  bool
		 *  @syntax  bool CoreSession->_setSessionCookie();
		 */
		protected function _setSessionCookie()
		{
			return setCookie( 
				$this->_sessionname,
				$this->_id,
				time() + $this->_duration,
				"/",
				$this->_cookiedomain
			);
		}

		/**
		 *  Get the session cookie
		 *  @name    _getSessionCookie
		 *  @type    method
		 *  @access  protected
		 *  @return  string
		 *  @syntax  string CoreSession->_getSessionCookie();
		 */
		protected function _getSessionCookie()
		{
			return $this->call( "/Tool/cookieVal", $this->_sessionname, false );
		}

		/**
		 *  Destroy the session variables or session entirely
		 *  @name    destroy
		 *  @type    method
		 *  @access  public
		 *  @param   bool   removecookie [optional, default false]
		 *  @return  void
		 *  @syntax  void CoreSession->destroy( [ bool removecookie ] );
		 *  @note    The cookie is kept by default
		 */
		public function destroy( $bRemoveCookie=false )
		{
			$this->_property = Array();
			$this->_updated  = true;
			if ( $bRemoveCookie )
				return setCookie( 
					$this->_sessionname,
					"",
					time() + $this->_duration,
					"/",
					$this->_cookiedomain
				);
		}

		/**
		 *  Is a variable registered
		 *  @name    isRegistered
		 *  @type    method
		 *  @access  public
		 *  @param   string variable
		 *  @return  bool
		 *  @syntax  bool CoreSession->isRegistered( string variablename );
		 */
		public function isRegistered( $sVariable )
		{
			if ( $this->start() )
				return array_key_exists( $sVariable, $this->_property );
			return false;
		}

		/**
		 *  Store the session data
		 *  @name    writeClose
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreSession->writeClose();
		 *  @note    unlike PHP's session_write_close function, CoreSession->writeClose does _NOT_ end the session, you can still add/change values which will be stored
		 */
		public function writeClose()
		{
			if ( $this->start() && $this->_updated )
			{
				$sData   = serialize( $this->_property );
				$sQuery  = "INSERT INTO session ( 
							       ustid, 
							       sescode, 
							       sesdata, 
							       sesmodifiedts, 
							       sescreatedts 
							)
							VALUES ( 
							       " . $this->get( "/User/Tracker/id" ) . ",
							       " . $this->call( "/DB/quote", $this->_id ) . ",
							       " . $this->call( "/DB/quote", $sData ) . ",
							       NOW(),
							       NOW()
							)
							ON DUPLICATE KEY
							UPDATE sesdata=VALUES( sesdata ),
							       sesmodifiedts=NOW()";
				$oResult = $this->call( "/DB/query", $sQuery );
				if ( is_object( $oResult ) && $oResult->errno <= 0 )
				{
					$this->_updated = false;
					return true;
				}
			}
			return false;
		}

		/**
		 *  Alias for writeClose
		 *  @name    commit
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreSession->commit();
		 *  @see     writeClose
		 */
		public function commit()
		{
			return $this->writeClose();
		}

		function __set( $sProperty, $mValue )
		{
			if ( ( !array_key_exists( $sProperty, $this->_property ) || ( array_key_exists( $sProperty, $this->_property ) && $this->$sProperty !== $mValue ) ) )
				$this->_updated = true;
			parent::__set( $sProperty, $mValue );
		}

		function __get( $sProperty )
		{
			$this->start();
			return parent::__get( $sProperty );
		}

		public function __destruct()
		{
			if ( $this->_updated )
				$this->writeClose();
		}
	}

?>