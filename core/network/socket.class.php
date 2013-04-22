<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
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


	/**
	 *  Network socket connectivity
	 *  @name    CoreNetworkSocket
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreNetworkSocket extends Konsolidate
	{
		protected $_conn;
	 	protected $_timeout;

		public  $error;
		
		/**
		 *  @name    connect
		 *  @type    method
		 *  @access  public
		 */
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
		
		/**
		 *  @name    disconnect
		 *  @type    method
		 *  @access  public
		 */
		public function disconnect()
		{
			if ( is_resource( $this->_conn ) )
				return fclose( $this->_conn );
			return false;
		}
		
		/**
		 *  @name    timeout
		 *  @type    method
		 *  @access  public
		 */
		public function timeout( $nTimeout )
		{
			$this->_timeout = $nTimeout;
			if ( is_resource( $this->_conn ) )
				return stream_set_timeout( $this->_conn, $this->_timeout );
			return false;
		}
		
		/**
		 *  @name    write
		 *  @type    method
		 *  @access  public
		 */
		public function write( $sData )
		{
			if ( is_resource( $this->_conn ) )
				return stream_socket_sendto( $this->_conn, $sData );
			return false;
		}
		
		/**
		 *  @name    read
		 *  @type    method
		 *  @access  public
		 */
		public function read( $nLength=512 )
		{
			if ( is_resource( $this->_conn ) )
				return stream_socket_recvfrom( $this->_conn, $nLength );
			return false;
		}
	}

?>