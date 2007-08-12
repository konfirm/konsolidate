<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreNetworkSocket
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Network/Socket
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */
	class CoreNetworkSocket extends Konsolidate
	{
		private $_conn;
		private $_timeout;

		public  $error;

		public function connect( $sHost, $nPort, $sTransport="tcp", $nTimeout=10 )
		{
			$this->_conn = @stream_socket_client( "{$sTransport}://{$sHost}:{$nPort}", $errno, $errstr, $nTimeout );
			if ( is_resource( $this->_conn ) )
			{
				$this->timeout( $nTimeout );
				return true;
			}
			return false;
		}

		public function disconnect()
		{
			if ( is_resource( $this->_conn ) )
				return fclose( $this->_conn );
			return false;
		}

		public function timeout( $nTimeout )
		{
			$this->_timeout = $nTimeout;
			if ( is_resource( $this->_conn ) )
				return stream_set_timeout( $this->_conn, $this->_timeout );
			return false;
		}

		public function write( $sData )
		{
			if ( is_resource( $this->_conn ) )
				return stream_socket_sendto( $this->_conn, $sData );
			return false;
		}

		public function read( $nLength=512 )
		{
			if ( is_resource( $this->_conn ) )
				return stream_socket_recvfrom( $this->_conn, $nLength );
			return false;
		}
	}

?>