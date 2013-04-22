<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
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


	/**
	 *  Support class for easy RPC (e.g. Ajax) interfaces
	 *  @name    CoreRPC
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 *  @note    By design, all RPC calls will have to be exposed (activated) 'manually' in your project
	 */
	class CoreRPC extends Konsolidate
	{
		protected $_config;
		protected $_request;

		/**
		 *  CoreRPC constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreRPC( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );

			$this->import( "control.if.php" );
		}

		public function loadConfig( $sFile )
		{
			return $this->_config = $this->call( "/Config/ini/load", $sFile );
		}

		public function process( $sConfigFile=null )
		{
			if ( !is_null( $sConfigFile ) )
				$this->loadConfig( $sConfigFile );

			if ( is_array( $this->_config ) )
			{
				$sCommand = $this->get( "/Request/command" );

				if ( array_key_exists( "rpc", $this->_config ) && array_key_exists( $sCommand, $this->_config[ "rpc" ] ) )
					return $this->call( "Control/process", $this->_config[ "rpc" ][ $sCommand ] );
			}
			return false;
		}
	}

?>