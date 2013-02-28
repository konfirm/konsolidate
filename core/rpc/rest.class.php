<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPCREST
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC/REST
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Support for REST (by accident) protocol, POST/GET only
	 *  @name    CoreRPCREST
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreRPCREST extends CoreRPC
	{
		/**
		 *  Autonimously process a 'REST' request
		 *  @name    process
		 *  @type    method
		 *  @access  public
		 *  @param   string configfile (optional, default null)
		 *  @return  bool
		 *  @syntax  bool CoreRPCREST( string configfile )
		 */
		public function process( $sConfigFile=null )
		{
			if ( !is_null( $sConfigFile ) )
				$this->loadConfig( $sConfigFile );

			if ( is_array( $this->_config ) )
			{
				$aArgument = explode( "/", trim( str_replace( $_SERVER[ "SCRIPT_NAME" ], "", $_SERVER[ "PHP_SELF" ] ), "/" ) );
				$sCommand  = array_shift( $aArgument );

				if ( array_key_exists( "rest", $this->_config ) && array_key_exists( $sCommand, $this->_config[ "rest" ] ) )
				{
					$sCommand = "../Control/{$this->_config[ "rest" ][ $sCommand ]}";
					$sModule  = dirName( $sCommand );

					array_unshift( $aArgument, $sCommand );
					call_user_func_array( Array( $this, "call" ), $aArgument );

					$this->call( "/RPC/Status/send", 
						$this->call( "{$sModule}/getStatus" ), 
						$this->call( "{$sModule}/getMessage" ), 
						$this->call( "{$sModule}/getContent" )
					);

					return true;
				}
			}

			return false;
		}
	}

?>