<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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

	class CoreSession extends  Konsolidate
	{
		private $_id;
		private $_started;
		private $_sessionID;
		private $_duration;
		
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_id        = md5( $this->get( "/User/Tracker/id" ) . $this->get( "/User/Tracker/code" ) );
			$this->_started   = false;
			$this->_sessionID = "PHPSESSID";
			$this->_duration  = 1800; // 30 minutes
		}

		public function start( $sSessionID=null )
		{
			if ( !empty( $sSessionID ) )
				$this->_sessionID = $sSessionID;

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

			return false;
		}

		public function register()
		{
			if ( !isset( $this->_started ) || !$this->_started )
			{
				$this->start();
				$this->_started = true;
			}

			$aRegister = func_get_args();
			foreach( $aRegister as $mVariable )
				if ( is_array( $mVariable ) )
					call_user_func_array( Array( &$this, "register" ), $mVariable );
				else
					$this->$mVariable = $GLOBALS[ $mVariable ];
		}

		private function _setSessionCookie()
		{
			return setCookie( 
				$this->_sessionID,
				$this->_id,
				0, //time() + $this->_duration,
				"",
				""
			);
		}

		private function _getSessionCookie()
		{
			return CoreTool::cookieVal( $this->_sessionID, false );
		}

		public function __destruct()
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
		}

		/*
		cache_expire
		cache_limiter
		commit
		decode
		destroy
		encode
		get_cookie_params
		id
		is_registered
		module_name
		name
		regenerate_id
		*register
		save_path
		set_cookie_ params
		set_save_ handler
		*start
		unregister
		unset
		write_close
		*/
	}

?>