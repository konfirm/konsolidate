<?php

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

			$this->_collect();
		}

		public function isPosted()
		{
			return $_SERVER[ "REQUEST_METHOD" ] === "POST";
		}

		private function _collectFromInput()
		{
			$sRequest = trim( file_get_contents( "php://input" ) );

			//  Try to determine what kind of request triggered this class
			switch( substr( $sRequest, 0, 1 ) )
			{
				case "<": // XML
					// in-class for now
					$oXML = new SimpleXMLElement( $sRequest );
					foreach( $oXML as $sParam=>$sValue )
						$this->$sParam = $sValue;
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
			if ( $this->isPosted() && !count( $_POST ) )
				$this->_collectFromInput();
			else if ( $this->isPosted() )
				$this->_collectHTTP( $_POST );
			else
				$this->_collectHTTP( $_GET );
		}
	}

?>