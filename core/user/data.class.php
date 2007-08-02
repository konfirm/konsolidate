<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUserData
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: User/Data
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 39 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-05-21 00:46:54 +0200 (Mon, 21 May 2007) $
	 *               \___\/           
	 */
	class CoreUserData extends Konsolidate
	{
		public $_anticipation;

		/**
		 *  CoreUserData constructor
		 *  @name    CoreUserData
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreUserData( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->_anticipation = defined( "USERDATA_ANTICIPATION" ) && (bool) USERDATA_ANTICIPATION;
		}

		/**
		 *  Enable/disable anticipation of userdata
		 *  @name    useAnticipation
		 *  @type    method
		 *  @access  public
		 *  @returns array
		 *  @syntax  Object->useAnticipation( bool enable );
		 */
		public function useAnticipation( $bEnable )
		{
			$this->_anticipation = $bEnable;
		}

		public function __get( $sProperty )
		{
			if ( !array_key_exists( $sProperty, $this->_property ) && $this->_anticipation )
			{
				//  store the requested property in the database with the current scope
				$sQuery  = "INSERT INTO userdatascope ( usdproperty, udsscope, udscreatedts ) VALUES ( 
								" . $this->call( "/DB/quote", $sProperty ) . ", 
								" . $this->call( "/DB/quote", $this->_anticipationScope() ) . ",
								NOW() )
							ON DUPLICATE KEY UPDATE 
								udscount=udscount+1,
								udsmodifiedts=NOW()";
				$oResult = $this->call( "/DB/query", $sQuery );

				//  retrieve the requested variable
				$nID     = $this->call( "../get", "id" );
				$sQuery  = "SELECT usdproperty,
							       usdvalue
							  FROM userdata
							 WHERE usrid={$nID}
							   AND usdproperty=" . $this->call( "/DB/quote", $sProperty );
				$oResult = $this->call( "/DB/query", $sQuery );
				if ( is_object( $oResult ) && $oResult->errno <= 0 )
				{
					//  store internally
					while( $oRecord = $oResult->next() )
						$this->_property[ $oRecord->usdproperty ] = $oRecord->usdvalue;
				}
			}
			return parent::__get( $sProperty );
		}

		public function load( $nID=null )
		{
			if ( is_null( $nID ) )
				$nID = $this->get( "../id" );

			if ( $this->_anticipation )
			{
				$sQuery  = "SELECT usd.usdproperty,
							       usd.usdvalue
							  FROM userdatascope uds
							 INNER JOIN userdata usd ON usd.usdproperty=uds.usdproperty AND usd.usrid={$nID}
							 WHERE uds.udsscope=" . $this->call( "/DB/quote", $this->_anticipationScope() );
			}
			else
			{
				$sQuery  = "SELECT usdproperty,
							       usdvalue
							  FROM userdata
							 WHERE usrid={$nID}";
			}

			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 )
			{
				while( $oRecord = $oResult->next() )
				{
					var_dump( "CoreUserData::load(): {$oRecord->usdproperty} = {$oRecord->usdvalue}<br />" );
					$this->_property[ $oRecord->usdproperty ] = $oRecord->usdvalue;
				}
				return true;
			}
			return false;
		}

		/**
		 *  Cleanup Object (store data, using the id from the parent class ("User"))
		 *  @name    store
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  Object->__destruct();
		 */
		public function __destruct()
		{
			$nID       = $this->get( "../id" );
			$sProperty = "";

			foreach( $this->_property as $sKey=>$mValue )
				$sProperty .= ( !empty( $sProperty ) ? "," : "" ) . "( {$nID}, " . $this->call( "/DB/quote", $sKey ) . ", " . $this->call( "/DB/quote", $mValue ) . ", NOW() )";

			$sQuery  = "INSERT INTO userdata ( usrid, usdproperty, usdvalue, usdcreatedts )
						VALUES {$sProperty}
						    ON DUPLICATE KEY
						UPDATE usdvalue=VALUES( usdvalue ),
						       usdmodifiedts=NOW()";
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 )
			{
				var_dump( "CoreUserData stored it's properties properly" );
				return true;
			}
			var_dump( "CoreUserData couldn't store it's properties!!" );
			return false;
		}

		/**
		 *  Get an unique scope identifier, based on the script name and the variables with which it was requested
		 *  @name    _anticipationScope
		 *  @type    method
		 *  @access  private
		 *  @returns bool
		 *  @syntax  Object->_anticipationScope();
		 */
		private function _anticipationScope()
		{
			$aParam  = array_keys( $_REQUEST );
			sort( $aParam );
			array_unshift( $aParam, $_SERVER[ "SCRIPT_NAME" ] );
			return md5( implode( " ", $aParam ) );
		}
	}

?>