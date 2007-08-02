<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreNetworkProtocolSMTP
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Network/Protocol/SMTP
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 44 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-06-02 20:48:00 +0200 (Sat, 02 Jun 2007) $
	 *               \___\/           
	 */
	class CoreNetworkProtocolSMTP extends Konsolidate
	{
		private $_validheader  = Array( "to", "cc", "bcc", "from", "date", "subject" );
		private $_noautoheader = Array( "to", "from", "server", "domain", "port", "sender", "recipient", "body" );
		private $_socket;
		private $_error;
		private $_status;
		private $_message;
		private $_header;

		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_header = Array();
			$this->server  = $_SERVER[ "SERVER_NAME" ];
			$this->port    = 25;
			$this->from    = "konsolidate@{$_SERVER["SERVER_NAME"]}";
			$this->subject = "No subject";
			$this->mailer  = "Konsolidate (" . get_class( $this ) . ")";
			$this->date    = gmdate( "r" );
		}

		public function addHeader( $sKey, $sValue )
		{
			$this->_header[ ( !in_array( strToLower( $sKey ), $this->_validheader ) ? "X-" : "" ) . ucfirst( $sKey ) ] = $sValue;
		}

		public function connect( $sHost="localhost", $nPort=25, $nTimeout=30 )
		{
			$this->_socket = $this->instance( "/Network/Socket" );
			if ( $this->_socket->connect( $sHost, $nPort, "tcp", $nTimeout ) )
			{
				$nStatus = $this->_getResponseStatus();
				return empty( $nStatus ) || $nStatus == 220;
			}
			return false;
		}

		private function _getResponseStatus()
		{
			$sResponse = trim( $this->_socket->read( 512 ) );
			var_dump( "<< {$sResponse}" );
			list( $this->_status, $this->_message ) = explode( " ", $sResponse, 2 );
			return (int) $this->_status;
		}

		private function _command( $sCommand )
		{
			var_dump( ">> {$sCommand}" );
			if ( $this->_socket->write( "{$sCommand}\r\n" ) )
				return $this->_getResponseStatus();
			return false;
		}

		/**
		 *  SMTP Command wrappers
		 *  NOTE: RFC 821 only partially implemented!!
		 */
		public function helo( $sDomain=null )
		{
			if ( empty( $sDomain ) )
				$sDomain = $$_SERVER[ "SERVER_NAME" ];
			return ( $this->_command( "HELO {$sDomain}" ) == 250 || $this->_command( "EHLO {$sDomain}" ) == 250 );
		}

		public function mailFrom( $sEmail, $sName=null )
		{
			$this->addHeader( "From", ( !is_null( $sName ) ? "{$sName} <{$sEmail}>" : $sEmail ) );
			return $this->_command( "MAIL FROM: {$sEmail}" ) == 250;
		}

		public function rcptTo( $sEmail, $sName=null )
		{
			$this->addHeader( "To", ( !is_null( $sName ) ? "{$sName} <{$sEmail}>" : $sEmail ) );
			return $this->_command( "RCPT TO: {$sEmail}" ) == 250;
		} 

		/**
		 *  Don't rely on this method!
		 *  Most mailservers have disable the VRFY command for it was used by spammers to build lists of valid addresses, 
		 *  even if it is enabled, be prepared for it to accept everything you fire at it (catch-all).
		 */
		public function vrfy( $sEmail )
		{
			$nStatus = $this->_command( "VRFY {$sEmail}" );
			return $nStatus == 250 || $nStatus == 251;
		}

		/**
		 *  TODO:  - wrap content to be max 998 chars 'wide' (excluding CR/LF)
		 *         - wrap content in such way that it leaves HTML intact
		 */
		public function data( $sData )
		{
			if ( $this->_command( "DATA" ) == 354 )
			{
				foreach( $this->_header as $sKey=>$sValue )
					$this->_socket->write( "{$sKey}: {$sValue}\r\n" );
				$this->_socket->write( "\r\n{$sData}\r\n" );
				return $this->_command( "." ) == 250;
			}
			return false;
		}

		public function quit()
		{
			if ( $this->_command( "QUIT" ) == 221 )
				return true;
			return false;
		}

		public function verify( $sAddress )
		{
			$sServer = substr( $sAddress, strpos( $sAddress, "@" ) + 1 );
			dns_get_mx( $sServer, $aMX );

			if ( count( $aMX ) <= 0 )
				return false;
			$sServer = $aMX[ 0 ];

			if ( !is_object( $this->_socket ) && !$this->connect( $sServer, 25 ) )
				return false;
			return $this->vrfy( $sAddress );
		}

		public function send()
		{
			foreach( $this->_property as $sKey=>$mValue )
				if ( !in_array( strToLower( $sKey ), $this->_noautoheader ) )
				{
					if ( is_array( $mValue ) )
					{
						$sHeader = "";
						foreach( $mValue as $sValue )
							$sHeader .= ( !empty( $sHeader ) ? ", " : "" ) . $sValue;
						$this->addHeader( $sKey, $sHeader );
					}
					else
					{
						$this->addHeader( $sKey, $mValue );
					}
				}

			if ( !$this->connect( $this->server, $this->port ) )
				return false;

			if ( !$this->helo( $this->domain ) )
				return false;

			if ( !$this->mailFrom( $this->from, $this->sender ) )
				return false;

			if ( !$this->rcptTo( $this->to, $this->recipient ) )
				return false;

			if ( !$this->data( $this->body ) )
				return false;

			return $this->quit();
		}
	}

?>