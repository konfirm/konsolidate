<?php

	if ( !defined( "FILTER_META_CHARACTERS" ) )
		define( "FILTER_META_CHARACTERS", true );

	/**
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
	class CoreRequest extends Konsolidate
	{
		protected $_raw;
		protected $_xml;

		/**
		 *  CoreRequest constructor
		 *  @name    CoreRequest
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

		public function isPosted()
		{
			return $_SERVER[ "REQUEST_METHOD" ] === "POST";
		}

		public function getRawRequest()
		{
			return !is_null( $this->_raw ) ? $this->_raw : false;
		}

		public function getXML()
		{
			return !is_null( $this->_xml ) ? $this->_xml : false;
		}

		private function _collectFromRaw()
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

		private function _collectHTTP( &$aCollection )
		{
			foreach( $aCollection as $sParam=>$mValue )
				$this->$sParam = $mValue;
		}

		private function _collect()
		{
			$this->_raw = trim( file_get_contents( "php://input" ) );

			if ( $this->isPosted() )
			{
				if ( !empty( $this->_raw ) )
					$this->_collectFromRaw();
				else
					$this->_collectHTTP( $_POST );
			}
			else
			{
				$this->_collectHTTP( $_GET );
			}
		}
	}

?>