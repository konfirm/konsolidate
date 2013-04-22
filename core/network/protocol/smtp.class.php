<?php

	/*
	 *            ________ ___
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /
	 *    \  /   /\   \  /    \       Class:  CoreNetworkProtocolSMTP
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Network/Protocol/SMTP
	 *       \___\/  \___\/  \/
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/
	 */


	/**
	 *  Basic implementatin of the SMTP protocol
	 *  @name    CoreNetworkProtocolSMTP
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreNetworkProtocolSMTP extends Konsolidate
	{
		/**
		 *  Normal SMTP headers
		 *  @name    _validheader
		 *  @type    array
		 *  @access  protected
		 *  @note    all dynamically set properties not contained in this array will be preceeded by "X-"
		 */
		protected $_validheader;

		/**
		 *  All properties that need to be taken care of differently from setting it as (custom) header
		 *  @name    _noautoheader
		 *  @type    array
		 *  @access  protected
		 */
		protected $_noautoheader;

		/**
		 *  The socket connection resource
		 *  @name    _socket
		 *  @type    resource
		 *  @access  protected
		 */
		protected $_socket;

		/**
		 *  Array containing all explicit headers
		 *  @name    _header
		 *  @type    array
		 *  @access  protected
		 */
		protected $_header;



		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreNetworkProtocolSMTP( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_validheader  = Array( "to", "cc", "bcc", "from", "date", "subject", "reply-to", "return-path", "message-id", "mime-version", "content-type", "charset" );
			$this->_noautoheader = Array( "to", "from", "server", "domain", "port", "sender", "recipient", "body", "status", "message" );

			$this->_header = Array();
			$this->server  = $this->get( "/Config/Mail/server", ini_get( "SMTP" ) );
			$this->domain  = $this->get( "/Config/Mail/domain", CoreTool::arrVal( $_SERVER, "HTTP_HOST", "localhost" ) );
			$this->port    = 25;
			$this->from    = "konsolidate@{$this->domain}";
			$this->subject = "No subject";
			$this->mailer  = "Konsolidate (" . get_class( $this ) . ")";
			$this->date    = gmdate( "r" );
		}

		/**
		 *  Add header data
		 *  @name    addHeader
		 *  @type    method
		 *  @access  public
		 *  @param   string headername
		 *  @param   string headervalue
		 *  @return  void
		 *  @syntax  void CoreNetworkProtocolSMTP->addHeader( string headername, string headervalue )
		 */
		public function addHeader( $sKey, $sValue )
		{
			$this->_header[ ( !in_array( strToLower( $sKey ), $this->_validheader ) ? "X-" : "" ) . ucfirst( $sKey ) ] = $sValue;
		}

		/**
		 *  Open connection to the SMTP server
		 *  @name    connect
		 *  @type    method
		 *  @access  public
		 *  @param   string hostname     [optional, default 'localhost']
		 *  @param   int    portnumber   [optional, default 25]
		 *  @param   int    timeout (ms) [optional, default 30]
		 *  @return  void
		 *  @syntax  void CoreNetworkProtocolSMTP->connect( [ string hostname [, int portnumber [, int timeout ] ] ] )
		 */
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

		/**
		 *  Read the SMTP server response, place the status code in $this->status (public) and the response message in $this->message (public)
		 *  @name    _getResponseStatus
		 *  @type    method
		 *  @access  protected
		 *  @return  int statuscode
		 *  @syntax  void CoreNetworkProtocolSMTP->_getReponseStatus()
		 */
		protected function _getResponseStatus()
		{
			$sResponse = trim( $this->_socket->read( 512 ) );
			list( $this->status, $this->message ) = explode( " ", $sResponse, 2 );
			return (int) $this->status;
		}

		/**
		 *  @name    _createRecipientList
		 *  @type    method
		 *  @access  protected
		 *  @return  string
		 */
		protected function _createRecipientList( $aCollection )
		{
			$sReturn = "";
			foreach( $aCollection as $sEmail=>$sName )
				$sReturn .= ( !empty( $sReturn ) ? "," : "" ) . ( !is_null( $sName ) ? "{$sName}<{$sEmail}>" : $sEmail );
			return $sReturn;
		}

		/**
		 *  Send a command string to the SMTP server
		 *  @name    _command
		 *  @type    method
		 *  @access  protected
		 *  @param   string command
		 *  @return  mixed      int statuscode or bool if failed
		 *  @syntax  void CoreNetworkProtocolSMTP->_command( string command )
		 */
		protected function _command( $sCommand )
		{
			if ( $this->_socket->write( "{$sCommand}\r\n" ) )
				return $this->_getResponseStatus();
			return false;
		}


//		  SMTP Command wrappers
//		  NOTE: RFC 821 only partially implemented!!

		/**
		 *  Send the 'AUTH LOGIN' command to the server, triggering the authentication flow
		 *  @name    authLogin
		 *  @type    method
		 *  @access  public
		 *  @param   string username
		 *  @param   string password
		 *  @returns bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->authLogin(string username, string password)
		 *  @note    use the status/message properties for reporting/checking/logging
		 */
		public function authLogin( $sUsername, $sPassword )
		{
			$nResponse = $this->_command("AUTH LOGIN");
			if ( $nResponse == 334 )
				$nResponse = $this->_command( base64_encode( $sUsername ) );

			if ( $nResponse == 334 )
				return $this->_command( base64_encode( $sPassword ) ) == 235;

			return false;
		}

		/**
		 *  Send 'HELO'/'EHLO' (handshake) command to the SMTP server
		 *  @name    helo
		 *  @type    method
		 *  @access  public
		 *  @param   string domain [optional, default $_SERVER[ 'SERVER_NAME' ] or $this->server]
		 *  @returns bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->helo( [ string domain, [ bool enfore EHLO ] ] )
		 *  @note    use the status/message properties for reporting/checking/logging
		 */
		public function helo( $sDomain=null, $bEHLO=false )
		{
			if ( empty( $sDomain ) )
				$sDomain = CoreTool::arrVal( "SERVER_NAME", $_SERVER, $this->server );
			return ( ( !$bEHLO && $this->_command( "HELO {$sDomain}" ) == 250 ) || $this->_command( "EHLO {$sDomain}" ) == 250 );
		}

		/**
		 *  Send 'MAIL FROM' (sender) command to the SMTP server
		 *  @name    mailFrom
		 *  @type    method
		 *  @access  public
		 *  @param   string senderemail
		 *  @param   string sendername [optional, omitted if empty]
		 *  @return  bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->mailFrom( string email [, string name ] )
		 *  @note    use the status/message properties for reporting/checking/logging
		 */
		public function mailFrom( $sEmail, $sName=null )
		{
			$this->addHeader( "From", ( !is_null( $sName ) ? "{$sName} <{$sEmail}>" : $sEmail ) );
			return $this->_command( "MAIL FROM: {$sEmail}" ) == 250;
		}

		/**
		 *  Send 'RCPT TO' (recipient) command to the SMTP server
		 *  @name    rcptTo
		 *  @type    method
		 *  @access  public
		 *  @param   string recipientemail
		 *  @param   string recipientname [optional, omitted if empty]
		 *  @return  bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->rcptTo( string email [, string name ] )
		 *  @note    use the status/message properties for reporting/checking/logging
		 */
		public function rcptTo( $aCollection, $sHeaderName="To" )
		{
			$this->addHeader( $sHeaderName, $this->_createRecipientList( $aCollection ) );
			$bReturn = true;
			foreach( $aCollection as $sEmail=>$sName )
				$bReturn &= $this->_command( "RCPT TO: {$sEmail}" ) == 250;
			return $bReturn;
		}

		/**
		 *  Send 'VRFY' (verify) command to the SMTP server
		 *  @name    vrfy
		 *  @type    method
		 *  @access  public
		 *  @param   string recipientemail
		 *  @return  bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->vrfy( string email )
		 *  @note    use the status/message properties for reporting/checking/logging
		 *           Don't rely on this method!
		 *           Most mailservers have disabled the VRFY command for it was used by spammers to build lists of valid addresses,
		 *           even if it is enabled, be prepared for it to accept everything you fire at it (catch-all).
		 */
		public function vrfy( $sEmail )
		{
			$nStatus = $this->_command( "VRFY {$sEmail}" );
			return $nStatus == 250 || $nStatus == 251;
		}

		/**
		 *  Send 'DATA' (message body) command to the SMTP server and send the data
		 *  @name    data
		 *  @type    method
		 *  @access  public
		 *  @param   string data
		 *  @return  bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->data( string data )
		 *  @note    use the status/message properties for reporting/checking/logging
		 */
		public function data( $sData )
		{
			if ( $this->_command( "DATA" ) == 354 )
			{
				uksort( $this->_header, Array( $this, "_headerSort" ) );

				foreach( $this->_header as $sKey=>$sValue )
					$this->_socket->write( "{$sKey}: {$sValue}\r\n" );

				//  The SMTP protocol removes any dot which is the first character on a line, this is resolved by simply adding a dot.
				$this->_socket->write( str_replace( "\n.", "\n..", "\r\n{$sData}\r\n" ) );

				return $this->_command( "." ) == 250;
			}
			return false;
		}

		/**
		 *  Send 'QUIT' (hangup) command to the SMTP server
		 *  @name    data
		 *  @type    method
		 *  @access  public
		 *  @return  bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->quit()
		 *  @note    use the status/message properties for reporting/checking/logging
		 */
		public function quit()
		{
			if ( $this->_command( "QUIT" ) == 221 )
				return true;
			return false;
		}

		/**
		 *  Verify the domain of an e-mail address for having a MX server present (basically validating the e-mail domain)
		 *  @name    verify
		 *  @type    method
		 *  @access  public
		 *  @param   string email
		 *  @param   bool   useVRFY [optional, default false]
		 *  @return  bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->verify( string email [, bool useVRFY ] )
		 *  @see     vrfy
		 */
		public function verify( $sAddress, $bVRFY=false )
		{
			$sServer = substr( $sAddress, strpos( $sAddress, "@" ) + 1 );
			dns_get_mx( $sServer, $aMX );

			if ( count( $aMX ) <= 0 )
				return false;

			if ( !$bVRFY )
				return true;

			$sServer = $aMX[ 0 ];
			if ( !is_object( $this->_socket ) && !$this->connect( $sServer, 25 ) )
				return false;
			return $this->vrfy( $sAddress );
		}

		/**
		 *  Send the prepared email
		 *  @name    send
		 *  @type    method
		 *  @access  public
		 *  @param   string username (optional)
		 *  @param   string password (optional)
		 *  @return  bool success
		 *  @syntax  void CoreNetworkProtocolSMTP->send()
		 *  @note    use the status/message properties for reporting/checking/logging
		 */
		public function send( $sUsername=null, $sPassword=null )
		{
			foreach( $this->_property as $sKey=>$mValue )
				if ( !in_array( strToLower( $sKey ), $this->_noautoheader ) )
				{
					if ( is_array( $mValue ) )
					{
						$sHeader = "";
						foreach( $mValue as $sValue )
							$sHeader .= ( !empty( $sHeader ) ? ", " : "" ) . $sValue;
						if ( !empty( $sHeader ) )
							$this->addHeader( $sKey, $sHeader );
					}
					elseif ( !empty( $mValue ) )
					{
						$this->addHeader( $sKey, $mValue );
					}
				}

			if ( !$this->connect( $this->server, $this->port ) )
				return false;

			if ( !$this->helo( $this->domain, $sUsername && $sPassword ) )
				return false;

			if ( $sUsername && $sPassword && !$this->authLogin( $sUsername, $sPassword ) )
				return false;

			if ( !$this->mailFrom( $this->from, $this->sender ) )
				return false;

			foreach( Array( "to", "cc", "bcc" ) as $sType )
			{
				$mValue = $this->$sType;
				if ( !empty( $mValue ) && !$this->rcptTo( $mValue, $sType ) )
					return false;
			}

			if ( !$this->data( $this->body ) )
				return false;

			return $this->quit();
		}

		/**
		 *  Order the headers for better/nicer output
		 *  @name    _headerSort
		 *  @type    method
		 *  @access  protected
		 *  @param   string A
		 *  @param   string B
		 *  @return  int  order
		 *  @syntax  void CoreNetworkProtocolSMTP->_headerSort( string A, string B )
		 *  @note    used as array sort function
		 */
		protected function _headerSort( $sA, $sB )
		{
			return array_search( strToLower( $sA ), $this->_validheader ) > array_search( strToLower( $sB ), $this->_validheader ) ? 1 : -1;
		}
	}

?>