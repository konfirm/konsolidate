<?php

	if ( !defined( "RPC_CONFIG_PATH" ) )
		define( "RPC_CONFIG_PATH", realpath( $_SERVER[ "DOCUMENT_ROOT" ] . "/../conf/rpc.ini" ) );

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPC
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */
	class CoreRPC extends Konsolidate
	{
		protected $_request;

		/**
		 *  CoreRPC constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreRPC( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );

			$this->import( "control.if.php" );
			$this->_request = &$this->register( "/Request" );
		}

		public function process()
		{
			$sCommand = $this->_request->command;
			$aConfig  = $this->call( "/Config/ini/load", RPC_CONFIG_PATH );

			if ( array_key_exists( "expose", $aConfig ) && array_key_exists( $sCommand, $aConfig[ "expose" ] ) )
				return $this->call( "Control/process", $aConfig[ "expose" ][ $sCommand ] );
			return false;
		}
	}

?>