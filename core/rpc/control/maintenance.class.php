<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPCControlMaintenance
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC/Control/Maintenance
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Basic maintenance control for use through an RPC interface
	 *  @name    CoreRPCControlMaintenance
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.net>
	 *  @note    By design, all RPC calls will have to be exposed (activated) 'manually' in your project
	 */
	class CoreRPCControlMaintenance extends Konsolidate implements CoreRPCControlInterface
	{
		protected $_request;
		protected $_message;
		protected $_content;
		protected $_status;

		/*  Interface requirements  */

		/**
		 *  retrieve the message string
		 *  @name    getMessage
		 *  @type    method
		 *  @access  public
		 *  @returns string
		 *  @syntax  string CoreRPCControlMaintenance->getMessage()
		 *  @note    This method is required by the interface
		 */
		public function getMessage()
		{
			return isset( $this->_message ) ? $this->_message : null;
		}

		/**
		 *  retrieve the content string/array
		 *  @name    getContent
		 *  @type    method
		 *  @access  public
		 *  @returns mixed
		 *  @syntax  mixed CoreRPCControlMaintenance->getContent()
		 *  @note    This method is required by the interface
		 */
		public function getContent()
		{
			return isset( $this->_content ) ? $this->_content : null;
		}

		/**
		 *  retrieve the request status
		 *  @name    getStatus
		 *  @type    method
		 *  @access  public
		 *  @returns bool
		 *  @syntax  bool CoreRPCControlMaintenance->getStatus()
		 *  @note    This method is required by the interface
		 */
		public function getStatus()
		{
			return isset( $this->_status ) ? (bool) $this->_status : false;
		}


		/*  Controls  */

		/**
		 *  load Request object
		 *  @name    _loadRequest
		 *  @type    method
		 *  @access  protected
		 *  @returns void
		 *  @syntax  void CoreRPCControlMaintenance->_loadRequest()
		 */
		protected function loadRequest()
		{
			if ( !isset( $this->_request ) )
				$this->_request = &$this->register( "/Request" );
		}

		/**
		 *  unlink all files/folders from provided location
		 *  @name    _recursiveUnlink
		 *  @type    method
		 *  @access  protected
		 *  @param   string path
		 *  @returns bool
		 *  @syntax  bool CoreRPCControlMaintenance->_recursiveUnlink( string path )
		 */
		protected function _recursiveUnlink( $sPath )
		{
			if ( is_dir( $sPath ) )
			{
				$bReturn = true;
				$oDir    = new DirectoryIterator( $sPath );
				foreach( $oDir as $oDirItem )
					if ( $bReturn && !$oDirItem->isDot() )
					{
						if ( $oDirItem->isFile() )
						{
							$bReturn &= $this->call( "/System/File/unlink", "{$sPath}/" . $oDirItem->getFileName() );
						}
						else if ( $oDirItem->isDir() )
						{
							$bReturn &= $this->_recursiveUnlink( "{$sPath}/" . $oDirItem->getFileName() );
							$bReturn &= rmdir( "{$sPath}/" . $oDirItem->getFileName() );
						}
					}
				return $bReturn;
			}
		}

		/**
		 *  clear all cached template compilations
		 *  @name    clearCache
		 *  @type    method
		 *  @access  public
		 *  @returns void
		 *  @syntax  void CoreRPCControlMaintenance->clearCache()
		 */
		public function clearCache()
		{
			$this->_recursiveUnlink( COMPILE_PATH );
		}
	}

?>