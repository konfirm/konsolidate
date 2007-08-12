<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	class CoreRPCControlMaintenance extends Konsolidate implements CoreRPCControlInterface
	{
		private $_request;
		private $_message;
		private $_content;
		private $_status;

		/*  Interface requirements  */
		public function getMessage()
		{
			return isset( $this->_message ) ? $this->_message : null;
		}

		public function getContent()
		{
			return isset( $this->_content ) ? $this->_content : null;
		}

		public function getStatus()
		{
			return isset( $this->_status ) ? (bool) $this->_status : false;
		}


		/*  Controls  */
		private function loadRequest()
		{
			if ( !isset( $this->_request ) )
				$this->_request = &$this->register( "/Request" );
		}

		private function recursiveUnlink( $sPath )
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
							$bReturn &= $this->recursiveUnlink( "{$sPath}/" . $oDirItem->getFileName() );
							$bReturn &= rmdir( "{$sPath}/" . $oDirItem->getFileName() );
						}
					}
				return $bReturn;
			}
		}

		public function clearCache()
		{
			$this->recursiveUnlink( COMPILE_PATH );
		}
	}

?>