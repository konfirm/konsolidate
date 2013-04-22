<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUserData
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: User/Data
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Data attached to a User/Visitor
	 *  @name    CoreUserData
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 *  @author  Rogier van Ravesteijn <rogier.vanravesteijn@advance.nl>
	 */
	class CoreUserData extends Konsolidate
	{
		protected $_anticipation;
		protected $_anticipated;
		protected $_change;

		/**
		 *  CoreUserData constructor
		 *  @name    CoreUserData
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = new CoreUserData( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->_anticipation = $this->get( "/Config/UserData/anticipation" ) == 1;
			$this->_anticipated  = null;
			$this->_change       = Array();
		}

		/**
		 *  Load all properties associated with the explicit or implicit provided User ID
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @param   int  userid, optional
		 *  @return  bool success
		 *  @syntax  bool CoreUserData->load( [ int userid ] );
		 */
		public function load( $nID=null )
		{
			$this->_property = Array();

			if ( is_null( $nID ) )
				$nID = $this->get( "/User/id" );

			$sQuery  = "SELECT usdproperty,
						       usdvalue
						  FROM " . $this->_determineDataTable( $nID ) . "
						 WHERE usrid={$nID}";
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 )
			{
				while( $oRecord = $oResult->next() )
					$this->_property[ $oRecord->usdproperty ] = $oRecord->usdvalue;
				return true;
			}
			return false;
		}

		/**
		 *  Enable/disable anticipation of userdata
		 *  @name    useAnticipation
		 *  @type    method
		 *  @access  public
		 *  @param   bool   enable [optional, default true]
		 *  @return  void
		 *  @syntax  void CoreuserData->useAnticipation( bool enable );
		 */
		public function useAnticipation( $bEnable=true )
		{
			$this->_anticipation = $bEnable;
		}

		/**
		 *  Determine the table to retrieve the userdata from
		 *  @name    _determineDataTable
		 *  @type    method
		 *  @access  protected
		 *  @param   int    userid
		 *  @return  string datatable
		 *  @syntax  string CoreuserData->_determineDataTable( [ int userid ] );
		 */
		protected function _determineDataTable( $nUserID=null )
		{
			return "userdata";
		}

		/**
		 *  Load all properties associated with the current 'scope'
		 *  @name    _anticipateProperties
		 *  @type    method
		 *  @access  protected
		 *  @return  void
		 *  @syntax  void CoreuserData->_anticipateProperties();
		 *  @note    Properties which are set prior to the anticipation will not be overwriten by the anticipation
		 */
		protected function _anticipateProperties()
		{
			$nID = $this->get( "/User/id" );
			$this->_anticipated = Array();
			$sQuery  = "SELECT usd.usdproperty,
							   usd.usdvalue
						  FROM userdatascope uds
						 INNER JOIN " . $this->_determineDataTable( $nID ) . " usd ON usd.usdproperty=uds.usdproperty AND usd.usrid={$nID}
						 WHERE uds.udsscope=" . $this->call( "/DB/quote", $this->_anticipationScope() );
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows > 0 )
				while( $oRecord = $oResult->next() )
					if ( !array_key_exists( $oRecord->usdproperty, $this->_property ) )
					{
						array_push( $this->_anticipated, $oRecord->usdproperty );
						$this->_property[ $oRecord->usdproperty ] = $oRecord->usdvalue;
					}
		}

		/**
		 *  Store all anticipation properties
		 *  @name    _storeAnticipationProperties
		 *  @type    method
		 *  @access  protected
		 *  @return  bool sucess
		 *  @syntax  bool CoreuserData->_storeAnticipationProperties();
		 */
		protected function _storeAnticipationProperties()
		{
			if ( $this->_anticipation && (bool) count( $this->_property ) && !is_null( $this->_anticipated ) )
			{
				$aAnticipate = array_diff( array_keys( $this->_property ), $this->_anticipated );

				if ( (bool) count( $aAnticipate ) )
				{
					$sAnticipate = "";
					$sScope      = $this->call( "/DB/quote", $this->_anticipationScope() );

					foreach( $this->_property as $sKey=>$mValue )
						if ( !in_array( $sKey, $this->_anticipated ) )
							$sAnticipate .= ( !empty( $sAnticipate ) ? "," : "" ) . "( " . $this->call( "/DB/quote", $sKey ) . ", {$sScope}, NOW() )";

					//  store the requested property in the database with the current scope
					$sQuery  = "INSERT IGNORE INTO userdatascope ( usdproperty, udsscope, udscreatedts ) 
								VALUES {$sAnticipate}";
					$oResult = $this->call( "/DB/query", $sQuery );
					return ( is_object( $oResult ) && $oResult->errno <= 0 );
				}
			}
			return false;
		}

		/**
		 *  Load a specific property
		 *  @name    _loadProperty
		 *  @type    method
		 *  @access  protected
		 *  @param   string property
		 *  @return  void
		 *  @syntax  void CoreuserData->_loadProperty( string property );
		 */
		protected function _loadProperty( $sProperty )
		{
			$nID = $this->get( "/User/id" );
			$sQuery  = "SELECT usdvalue
						  FROM " . $this->_determineDataTable( $nID ) . "
						 WHERE usrid={$nID}
						   AND usdproperty=" . $this->call( "/DB/quote", $sProperty );
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows > 0 )
			{
				$oRecord = $oResult->next();
				$this->_property[ $sProperty ] = $oRecord->usdvalue;
			}
		}

		/**
		 *  Store all changed properties
		 *  @name    _storeChangedProperties
		 *  @type    method
		 *  @access  protected
		 *  @return  bool sucess
		 *  @syntax  bool CoreuserData->_storeChangedProperties();
		 */
		protected function _storeChangedProperties()
		{
			$nID         = $this->get( "../id" );
			$sProperty   = "";
			if ( (bool) count( $this->_change ) )
			{
				foreach( $this->_change as $sKey=>$mValue )
					$sProperty .= ( !empty( $sProperty ) ? "," : "" ) . "( {$nID}, " . $this->call( "/DB/quote", $sKey ) . ", " . $this->call( "/DB/quote", $mValue ) . ", NOW() )";
				$sQuery  = "INSERT INTO " . $this->_determineDataTable( $nID ) . " ( usrid, usdproperty, usdvalue, usdcreatedts )
							VALUES {$sProperty}
							    ON DUPLICATE KEY
							UPDATE usdvalue=VALUES( usdvalue ),
							       usdmodifiedts=NOW()";
				$oResult = $this->call( "/DB/query", $sQuery );
				if ( is_object( $oResult ) && $oResult->errno <= 0 )
					return true;
			}
			return false;
		}

		/**
		 *  Get an unique scope identifier, based on the script name and the variables with which it was requested
		 *  @name    _anticipationScope
		 *  @type    method
		 *  @access  protected
		 *  @return  string md5 scope
		 *  @syntax  string CoreuserData->_anticipationScope();
		 */
		protected function _anticipationScope()
		{
			$aParam  = array_keys( $_REQUEST );
			sort( $aParam );
			array_unshift( $aParam, $_SERVER[ "SCRIPT_NAME" ] );
			return md5( implode( " ", $aParam ) );
		}

		public function __set( $sProperty, $mValue )
		{
			if ( !array_key_exists( $sProperty, $this->_property ) || $mValue !== $this->_property[ $sProperty ] )
				$this->_change[ $sProperty ] = $mValue;
			return parent::__set( $sProperty, $mValue );
		}

		public function __get( $sProperty )
		{
			if ( !array_key_exists( $sProperty, $this->_property ) )
			{
				if ( $this->_anticipation && is_null( $this->_anticipated ) )
					$this->_anticipateProperties();

				if ( !array_key_exists( $sProperty, $this->_property ) )
					$this->_loadProperty( $sProperty );
			}

			return parent::__get( $sProperty );
		}

		/**
		 *  Cleanup CoreuserData (store anticipation and data, if any)
		 *  @name    store
		 *  @type    method
		 *  @access  public
		 *  @return  void
		 *  @syntax  void CoreuserData->__destruct();
		 */
		public function __destruct()
		{
			if ( $this->_anticipation )
				$this->_storeAnticipationProperties();

			if ( (bool) count( $this->_change ) )
				$this->_storeChangedProperties();
		}
	}

?>