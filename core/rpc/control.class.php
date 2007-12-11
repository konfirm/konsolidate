<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPCControl
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC/Control
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Standard processor for use with RPC-Controller Modules
	 *  @name    CoreRPCControl
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
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

			$this->_request = &$this->get( "/Request" );
			$this->_format  = $this->_request->_format;
		}

		/**
		 *  Send/assign feedback based on preferred format
		 *  @name    _feedback
		 *  @type    method
		 *  @access  private
		 *  @param   bool   error during processing (optional, default true, we assume the worst)
		 *  @param   string message to display (optional, default empty)
		 *  @param   mixed  content, either a string with additional message, or an array containing arrays, strings or numbers (optional, default empty)
		 *  @returns void
		 *  @syntax  void CoreRPCControl->_feedback()
		 */
		private function _feedback( $bError=true, $sMessage="", $mContent="" )
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

		/**
		 *  Process the RPC request
		 *  @name    process
		 *  @type    method
		 *  @access  public
		 *  @param   string command
		 *  @returns void
		 *  @syntax  void CoreRPCControl->process( string command )
		 */
		function process( $sCommand )
		{
			$nMethodStart = strrpos( $sCommand, $this->_objectseperator );
			$sModule      = substr( $sCommand, 0, $nMethodStart );
			$sMethod      = substr( $sCommand, $nMethodStart + 1 );

			$oProcessor = &$this->register( $sModule );
			if ( is_object( $oProcessor ) )
			{
				$oProcessor->$sMethod();
				return $this->_feedback( !$oProcessor->getStatus(), $oProcessor->getMessage(), $oProcessor->getContent() );
			}
			return $this->_feedback();
		}
	}

?>