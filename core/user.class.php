<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUser
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: User
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Site User/Visitor
	 *  @name    CoreUser
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreUser extends Konsolidate
	{
		/**
		 *  Was the user (and it's data) loaded
		 *  @name    _loaded
		 *  @type    bool
		 *  @access  private
		 */
		private $_loaded;

		/**
		 *  Is the user object currently in the process of filling it's properties
		 *  @name    _init
		 *  @type    bool
		 *  @access  private
		 */
		private $_init;

		/**
		 *  Was the user (data) changed
		 *  @name    _updated
		 *  @type    bool
		 *  @access  private
		 */
		private $_updated;

		/**
		 *  Should the data (if any updates took place) be saved automatically?
		 *  @name    _autosave
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_autosave;


		/**
		 *  CoreUser constructor
		 *  @name    CoreUser
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreUser( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->_loaded   = false;
			$this->_updated  = false;
			$this->_autosave = true;
		}

		

		/**
		 *  Load user data
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->load();
		 */
		public function load()
		{
			$nID = $this->call( "Tracker/load" );
			if ( !empty( $nID ) )
			{
				$sQuery  = "SELECT usrid,
							       usremail,
							       usragree,
							       usroptin,
							       usrtrack,
							       usrlogincount,
							       UNIX_TIMESTAMP( usrlastlogints ) AS usrlastlogints,
							       UNIX_TIMESTAMP( usrmodifiedts ) AS usrmodifiedts,
							       UNIX_TIMESTAMP( usrcreatedts ) AS usrcreatedts
							  FROM user
							 WHERE ustid={$nID}";
				$oResult = $this->call( "/DB/query", $sQuery );
				if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows == 1 )
				{
					$oData            = $oResult->next();
					$this->_init      = true;
					$this->id         = (int)    $oData->usrid;
					$this->email      = (string) $oData->usremail;
					$this->agree      = (bool)   $oData->usragree;
					$this->optin      = (bool)   $oData->usroptin;
					$this->track      = (bool)   $oData->usrtrack;
					$this->logincount = (int)    $oData->usrlogincount;
					$this->lastlogin  = (int)    $oData->usrlastlogints;
					$this->modified   = (int)    $oData->usrmodifiedts;
					$this->created    = (int)    $oData->usrcreatedts;
					$this->_init      = false;
					$this->_loaded    = true;
					$this->_updated   = false;
					return true;
				}
			}
			return false;
		}

		/**
		 *  Create a user data record
		 *  @name    create
		 *  @type    method
		 *  @access  public
		 *  @param   integer   user id
		 *  @param   string    email address
		 *  @param   string    password [optional]
		 *  @param   bool      agree [optional]
		 *  @param   bool      opt in [optional]
		 *  @param   bool      track [optional]
		 *  @returns bool
		 *  @syntax  Object->create( integer userid, string email [, string password [, bool agree [, bool optin [, bool track ] ] ] ] );
		 */
		public function create( $sEmail, $sPassword=false, $bAgree=false, $bOptIn=false, $bTrack=true )
		{
			if ( $sPassword === false )
				$sPassword = "";

			$nID = $this->call( "Tracker/get", "id" );
			if ( is_numeric( $nID ) )
			{
				$sQuery  = "INSERT INTO user 
								   ( ustid, 
									 usremail, 
									 usrpassword, 
									 usragree, 
									 usroptin, 
									 usrtrack, 
									 usrcreatedts 
								   )
							VALUES ( {$nID},
									 " . $this->call( "/DB/quote", $sEmail ) . ",
									 " . $this->call( "/DB/quote", $sPassword ) . ",
									 " . ( (int) $bAgree ) . ",
									 " . ( (int) $bOptIn ) . ",
									 " . ( (int) $bTrack ) . ",
									 NOW()
								   )";

				$oResult = $this->call( "/DB/query", $sQuery );
				if ( is_object( $oResult ) )
				{
					if ( $oResult->errno <= 0 )
					{
						$this->id = $oResult->lastId();
						return true;
					}
					else if ( $oResult->errno == 1062 ) // Duplicate key email
					{
						return false;
					}
				}
			}
			return false;
		}

		/**
		 *  Save user data
		 *  @name    store
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->store();
		 *  @note    Calls to store expect a load to have taken place first
		 */
		public function store()
		{
			if ( $this->_loaded && is_integer( $this->id ) && $this->id > 0 )
			{
				$sQuery  = "UPDATE user
							   SET usremail=" . $this->call( "/DB/quote", $this->email ) . ",
							       usrpassword=" . $this->call( "/DB/quote", $this->password ) . ",
							       usragree=" . ( (int) $this->agree ) . ",
							       usroptin=" . ( (int) $this->optin ) . ",
							       usrtrack=" . ( (int) $this->track ) . ",
							       usrmodifiedts=NOW()
							 WHERE usrid={$this->id}";
				$oResult = $this->call( "/DB/query", $sQuery );
				if ( is_object( $oResult ) && $oResult->errno <= 0 )
					return true;
			}
			return false;
		}

		/**
		 *  Authenticate a user based on its credentials
		 *  @name    login
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->login();
		 */
		public function login( $sEmail, $sPassword )
		{
			$sQuery  = "SELECT ust.ustcode
						  FROM user u
						 INNER JOIN usertracker ust ON ust.ustid=u.ustid
						 WHERE u.usremail=" . $this->call( "/DB/quote", $sEmail ) . "
						   AND u.usrpassword=" . $this->call( "/DB/quote", $sPassword );
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows >= 1 )
			{
				$oRecord = $oResult->next();
				if ( !empty( $oRecord->ustcode ) && $this->call( "Tracker/login", $oRecord->ustcode ) )
				{
					$sQuery  = "UPDATE user
								   SET usrlastlogints=NOW(),
								       usrlogincount=usrlogincount+1
								 WHERE usremail=" . $this->call( "/DB/quote", $sEmail );
					$this->call( "/DB/query", $sQuery ); // we trust this one to operate just fine and therefor don't check the result
					return $oRecord->ustcode;
				}
			}
			return false;
		}

		/**
		 *  get given property
		 *  @name    get
		 *  @type    method
		 *  @access  public
		 *  @param   string   property name
		 *  @returns mixed
		 *  @syntax  &Object->get( string property );
		 */
		public function __get( $sProperty )
		{
			if ( !$this->_init && !$this->_loaded )
				$this->load();

			return parent::__get( $sProperty );
		}

		function __set( $sProperty, $mValue )
		{
			if ( $this->$sProperty !== $mValue )
			{
				parent::__set( $sProperty, $mValue );
				$this->_updated = true;
			}
		}

		function __destruct()
		{
			if ( $this->_autosave && $this->_updated )
				$this->store();
		}
	}

?>