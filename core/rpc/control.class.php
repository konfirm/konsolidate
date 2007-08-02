<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPCControl
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC/Control
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 43 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-06-02 20:41:54 +0200 (Sat, 02 Jun 2007) $
	 *               \___\/           
	 */
	class CoreRPCControl extends Konsolidate
	{
		private $_request;
		private $_format;

		/**
		 *  CoreRPCControl constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreRPCControl( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );

			$this->_request = &$this->register( "/Request" );
			$this->_format  = $this->_request->_format;
		}

		private function feedback( $bError=true, $sMessage="", $mContent="" )
		{
			if ( $this->_format == "xml" )
			{
				$this->call( "/RPC/Status/send", $bError, $sMessage, $mContent );
			}
			else
			{
				echo "emulating normal POST/GET, dumping vars instead of assigning to template<br />";
				echo " - error: "; var_dump( $bError ); echo "<br />";
				echo " - message: "; var_dump( $sMessage ); echo "<br />";
				echo " - content: "; var_dump( $mContent ); echo "<br />";
			}
		}

		function process( $sCommand )
		{
			$nMethodStart = strrpos( $sCommand, $this->_objectseperator );
			$sModule      = substr( $sCommand, 0, $nMethodStart );
			$sMethod      = substr( $sCommand, $nMethodStart + 1 );

			$oProcessor = &$this->register( $sModule );
			if ( is_object( $oProcessor ) )
			{
				$oProcessor->$sMethod();
				return $this->feedback( !$oProcessor->getStatus(), $oProcessor->getMessage(), $oProcessor->getContent() );
			}
			return $this->feedback();
		}
	}

?>