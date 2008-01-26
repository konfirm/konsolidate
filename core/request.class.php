<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRequest
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Request
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Provide easy access to all request data
	 *  @name    CoreRequest
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreRequest extends Konsolidate
	{
		protected $_raw;
		protected $_xml;

		/**
		 *  magic __construct, CoreRequest constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreRequest( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->_raw = null;
			$this->_xml = null;
			$this->_collect();
		}

		/**
		 *  is the request a POST
		 *  @name    isPosted
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  bool CoreRequest->isPosted()
		 */
		public function isPosted()
		{
			return isset( $_SERVER[ "REQUEST_METHOD" ] ) && $_SERVER[ "REQUEST_METHOD" ] === "POST";
		}

		/**
		 *  retrieve the raw request data
		 *  @name    getRawRequest
		 *  @type    method
		 *  @access  public
		 *  @returns string (bool false, if no raw data is available)
		 *  @syntax  bool CoreRequest->getRawRequest()
		 */
		public function getRawRequest()
		{
			return !is_null( $this->_raw ) ? $this->_raw : false;
		}

		/**
		 *  retrieve XML request data
		 *  @name    getXML
		 *  @type    method
		 *  @access  public
		 *  @returns SimpleXMLElement (bool false, if no xml data is available)
		 *  @syntax  bool CoreRequest->getXML()
		 */
		public function getXML()
		{
			return !is_null( $this->_xml ) ? $this->_xml : false;
		}

		/**
		 *  retrieve variables from a raw data request
		 *  @name    _collectFromRaw
		 *  @type    method
		 *  @access  private
		 *  @returns void
		 *  @syntax  bool CoreRequest->_collectFromRaw()
		 */
		protected function _collectFromRaw()
		{
			//  Try to determine what kind of request triggered this class
			switch( substr( $this->_raw, 0, 1 ) )
			{
				case "<": // XML
					// in-class for now
					$this->_xml = new SimpleXMLElement( $this->_raw );

					foreach( $this->_xml as $sParam=>$sValue )
						$this->$sParam = (string) $sValue;
					$this->call( "/Log/write", var_export( $this->_property, true ) );
					break;
			}
		}

		/**
		 *  retrieve variables from a HTTP request (POST/GET only)
		 *  @name    _collectFromRaw
		 *  @type    method
		 *  @access  private
		 *  @returns void
		 *  @syntax  bool CoreRequest->_collectHTTP()
		 */
		protected function _collectHTTP( $aCollection )
		{
			if ( is_array( $aCollection ) && (bool) count( $aCollection ) )
			{
				foreach( $aCollection as $sParam=>$mValue )
					$this->$sParam = $mValue;
				return true;
			}
			return false;
		}

		/**
		 *  retrieve variables and assign them to the Request module
		 *  @name    _collect
		 *  @type    method
		 *  @access  private
		 *  @returns void
		 *  @syntax  bool CoreRequest->_collect()
		 */
		protected function _collect()
		{
			$this->_raw = trim( file_get_contents( "php://input" ) );
		
			if ( $this->isPosted() )
			{
				if ( !$this->_collectHTTP( $_POST ) )
					$this->_collectFromRaw();
			}
			else
			{
				$this->_collectHTTP( $_GET );
			}
		}
	}

?>