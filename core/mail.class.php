<?php

	/*
	 *            ________ ___
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /
	 *    \  /   /\   \  /    \       Class:  CoreMail
	 *     \/___/  \___\/      \      Tier:   Dev
	 *      \   \  /\   \  /\  /      Module: Mail
	 *       \___\/  \___\/  \/
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/
	 */

	/**
	 *  Mail functionality
	 *  @name    CoreMail
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreMail extends Konsolidate
	{
		const DEFAULT_ENCODING="8bit";
		const DEFAULT_RICH_ENCODING="quoted-printable";
		const DEFAULT_CHARSET="UTF-8";
		const MESSAGE_MIME="> This message was sent in MIME format. Your mail reader does not seem to support this feature.";

		/**
		 *  The SMTP server
		 *  @name    _server
		 *  @type    string
		 *  @access  protected
		 */
		protected $_server;

		/**
		 *  The SMTP port
		 *  @name    _port
		 *  @type    int
		 *  @access  protected
		 */
		protected $_port;

		/**
		 *  Is the content being loaded
		 *  @name    _loading
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_loading;

		/**
		 *  The job of which the content is loaded
		 *  @name    _job
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_job;

		/**
		 *  The character encoding used for the content (default UTF-8)
		 *  @name    _charset
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_charset;

		/**
		 *  The encoding used for plain content blocks (default 8bit)
		 *  @name    _encoding
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_encoding;

		/**
		 *  The encoding used for rich content blocks (default quoted-printable)
		 *  @name    _richencoding
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_richencoding;

		/**
		 *  The e-mail address to which the mail should be sent
		 *  @name    _to
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_to;

		/**
		 *  The e-mail address to which the mail should be CC'd
		 *  @name    _cc
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_cc;

		/**
		 *  The e-mail address to which the mail should be BCC'd
		 *  @name    _bcc
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_bcc;

		/**
		 *  The e-mail adres from on who's behalf the mail is sent
		 *  @name    _from
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_from;

		/**
		 *  The subject of the e-mail
		 *  @name    _subject
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_subject;

		/**
		 *  The plain text content of the e-mail
		 *  @name    _content
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_content;

		/**
		 *  The html content of the e-mail
		 *  @name    _richcontent
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_richcontent;

		/**
		 *  Files to be attached to the e-mail (not implemented!)
		 *  @name    _attachment
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_attachment;

		/**
		 *  Priority for the e-mail
		 *  @name    _priority
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_priority;

		/**
		 *  Replacement variables (key/value pair, where key is replaced by value )
		 *  @name    _replace
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_replace;

		/**
		 *  Plain server authentication
		 *  @name    _auth
		 *  @type    string
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_auth;

		/**
		 *  The content-boundary to be used to seperate content parts
		 *  @name    _boundary
		 *  @type    bool
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_boundary;


		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreMail( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$aServer = parse_url( $this->get( "/Config/Mail/server", "localhost" ) );

			if ( isset( $aServer[ "host" ] ) || isset( $aServer[ "path" ] ) )
			{
				$this->_server = isset( $aServer[ "host" ] ) ? $aServer[ "host" ] : $aServer[ "path" ];
				$this->_port   = isset( $aServer[ "port" ] ) ? $aServer[ "port" ] : 25;
				if ( isset( $aServer[ "user" ] ) )
					$this->_auth = trim( $aServer[ "user" ] . ":" . $aServer[ "pass" ], ":" );
			}

			$this->_boundary = $this->call( "/Key/create", "XXXX-XXX" ) . "-" . time();
			$this->reset();
		}

		/**
		 *  reset all relevant properties of the object
		 *  @name    reset
		 *  @type    method
		 *  @access  public
		 *  @return  void
		 *  @syntax  void CoreMail->reset()
		 */
		public function reset()
		{
			$this->_charset      = self::DEFAULT_CHARSET;
			$this->_encoding     = self::DEFAULT_ENCODING;
			$this->_richencoding = self::DEFAULT_RICH_ENCODING;
			$this->_loading      = false;
			$this->_to           = Array();
			$this->_cc           = Array();
			$this->_bcc          = Array();
			$this->_from         = "";
			$this->_subject      = "";
			$this->_content      = "";
			$this->_richcontent  = "";
			$this->_priority     = null;
			$this->_attachment   = Array();
			$this->_replace      = Array();
			$this->_property     = Array();
		}

		/**
		 *  send the prepared e-mail
		 *  @name    send
		 *  @type    method
		 *  @access  public
		 *  @param   bool allow empty content [optional, default true]
		 *  @param   bool allow empty subject [optional, default true]
		 *  @return  bool
		 *  @syntax  bool CoreMail->send( [ bool empty content [, bool empty subject ] ] )
		 */
		public function send( $bAllowEmptyContent=true, $bAllowEmptySubject=true )
		{
			$bRecipient = false;
			foreach( Array( "to", "cc", "bcc" ) as $sRecipient )
				if ( count( $this->{"_{$sRecipient}"} ) > 0 )
					$bRecipient = true;
			if ( !$bRecipient )
				return false;

			if ( !$bAllowEmptyContent && ( empty( $this->_content ) && empty( $this->_richcontent ) ) )
				return false;
			if ( !$bAllowEmptySubject && empty( $this->_subject ) )
				return false;

			if ($this->_auth)
			{
				$auth = explode(':', $this->_auth);
				return $this->_send($auth[0], $auth[1]);
			}

			return $this->_send();
		}

		/**
		 *  send the prepared e-mail
		 *  @name    _send
		 *  @type    method
		 *  @access  protected
		 *  @param   string auth username (optional)
		 *  @param   string auth password (optional)
		 *  @return  bool
		 *  @syntax  bool CoreMail->_send( [ string username, [ string password ] ] )
		 */
		protected function _send( $sUsername=null, $sPassword=null )
		{
			//  Create SMTP instance (unique for each call)
			$oMail            = $this->instance( "/Network/Protocol/SMTP" );
			$oMail->server    = $this->_server;
			$oMail->port      = $this->_port;
			$oMail->from      = $this->_from;
			$oMail->to        = $this->_to;
			$oMail->cc        = $this->_cc;
			$oMail->bcc       = $this->_bcc;
			$oMail->recipient = $this->recipient;
			$oMail->sender    = $this->sender;
			$oMail->subject   = $this->_substitute( $this->_subject );

			//  Set defaults
			if ( empty( $this->_from ) )
				$oMail->from = "no-reply@{$_SERVER[ "HTTP_HOST" ]}";

			$bBoundary      = $this->_requireBoundary();
			$bMultiBoundary = false;
			$sMailBody      = "";

			//  Set priority header if a priority was provided
			if ( !empty( $this->_priority ) )
				$oMail->addHeader( "Priority", $this->_priority );

			if ( !empty( $this->_richcontent ) )
			{
				$bBoundary = true;
				if ( empty( $this->_content ) )
					$this->_content = strip_tags( $this->_richcontent );
			}

			if ( $bBoundary )
			{
				//  Plain text content
				$sMailBody .= $this->_createDataSegment( $this->_substitute( $this->_content ), Array(
					"type"=>"text/plain",
					"charset"=>$this->_charset,
					"encoding"=>$this->_encoding
				), false );
				//  Rich (HTML) content
				$sMailBody .= $this->_createDataSegment( $this->_substitute( $this->_richcontent ), Array(
					"type"=>"text/html",
					"charset"=>$this->_charset,
					"encoding"=>$this->_richencoding
				), false );
			}
			else
			{
				//  Plain text content
				$sMailBody = $this->_substitute( $this->_content );
			}

			//  Attachments
			if ( is_array( $this->_attachment ) && (bool) count( $this->_attachment ) )
			{
				//  Attachments need to be in a separate multipart block if one already exists
				$bMultiBoundary = $bBoundary;

				//  The message was considered to be plain text only, having attachments forces use of a multipart message
				if ( !$bBoundary )
				{
					$bBoundary = true;
					$sMailBody = $this->_createDataSegment( $this->_substitute( $this->_content ), Array(
						"type"=>"text/plain",
						"charset"=>$this->_charset,
						"encoding"=>$this->_encoding
					), false );
				}
				else  //  already a multipart message, the created multiparted block becomes an embedded multipart itself
				{
					$sMailBody = $this->_createBoundary( false, true ) .
						$this->_createContentType( Array(
							"type"=>"multipart/alternative",
							"boundary"=>$this->getBoundary()
						) ) . "\r\n{$sMailBody}";
					$sMailBody .= $this->_createBoundary( true );
				}

				//  attach the actual file(s)
				foreach( $this->_attachment as $oFile )
				{
					$sMailBody .= $this->_createDataSegment( $oFile->data, Array(
						"encoding"=>"base64",
						"filename"=>baseName( $oFile->name ),
						"type"=>$oFile->type,
						"disposition"=>$oFile->disposition
					), $bMultiBoundary );
				}
			}

			//  if the mail has a boundary
			if ( $bBoundary )
			{
				$sMailBody = self::MESSAGE_MIME . "\r\n\r\n{$sMailBody}";
				//  create the (final) closing boundary
				$sMailBody .= $this->_createBoundary( true, $bMultiBoundary );
				//  set the MIME header to inform the mail reader about the boundary to use
				$this->_setMultiPartHeader( $oMail, $bMultiBoundary, (bool) count( $this->_attachment ) );
			}

			$oMail->body = $sMailBody;
			$bResult     = $oMail->send( $sUsername, $sPassword );

			$this->serverstatus  = $oMail->status;
			$this->servermessage = $oMail->message;
			unset( $oMail );

			if ( !$bResult )
				$this->call( "/Log/write", "Could not send mail to '{$this->_recipient}', status '{$this->serverstatus}', message '{$this->servermessage}'" );

			return $bResult;
		}

		/**
		 *  Attach a file to the mail
		 *  @name    attach
		 *  @type    method
		 *  @access  protected
		 *  @param   mixed  filepath    string filepath or an instance of 'Mail/Attachment'
		 *  @param   string mime        [optional, tried to be determined by default]
		 *  @param   string disposition [optional, value can be eithe 'attachment' or 'inline', default 'attachment']
		 *  @return  bool   success
		 *  @syntax  bool CoreMail->attach( string file [, string mimetype [, string disposition ] ] )
		 *  @note    In case data needs to be attached which is not available on the filesystem (e.g. some generated
		 *           text or an image), you can create an instance of 'Mail/Attachment' and set the name and data properties
		 */
		public function attach( $mFile, $sMime=null, $sDisposition="attachment" )
		{
			if ( is_string( $mFile ) && file_exists( $mFile ) )
			{
				$sFilename   = $mFile;
				$mFile       = $this->instance( "Attachment" );
				$mFile->name = $sFilename;
			}

			if ( is_object( $mFile ) && substr( get_class( $mFile ), -14 ) == "MailAttachment" )
			{
				if ( !empty( $sMime ) )
					$mFile->type = $sMime;
				if ( !empty( $sDisposition ) && ( $sDisposition == "attachment" || $sDisposition == "inline" ) )
					$mFile->disposition = $sDisposition;

				array_push( $this->_attachment, $mFile );
				return true;
			}

			return false;
		}

		/**
		 *  apply given encoding to a string
		 *  @name    _applyEncoding
		 *  @type    method
		 *  @access  protected
		 *  @param   string data
		 *  @param   string encoding
		 *  @param   int    linelength (default 75 for quoted-printable, 76 for base64)
		 *  @return  string
		 *  @syntax  string CoreMail->_applyEncoding( string data, string encoding [, int length ] )
		 */
		protected function _applyEncoding( $sData, $sEncoding, $nLength=null )
		{
			switch( strToLower( $sEncoding ) )
			{
				case "quoted-printable":
					$fp = fopen( "php://temp", "r+" );
					stream_filter_append( $fp, "convert.quoted-printable-encode", STREAM_FILTER_READ, Array( "line-break-chars"=>"\r\n", "line-length"=>( is_null( $nLength ) ? 75 : $nLength ) ) );
					fputs( $fp, $sData );
					rewind( $fp );
					$sData = stream_get_contents( $fp );
					fclose( $fp );
					break;
				case "base64":
					$sData = chunk_split( base64_encode( $sData ), ( is_null( $nLength ) ? 76 : $nLength ), "\r\n" );
					break;
				case "7bit":
				case "8bit":
				default: // do nothing
					break;
			}
			return $sData;
		}

		/**
		 *  Get the MIME boundary that is used
		 *  @name    getBoundary
		 *  @type    method
		 *  @access  public
		 *  @param   bool   multiboundary [optional, default false (outer boundary)]
		 *  @return  string boundary
		 *  @syntax  string CoreMail->getBoundary( [bool multiboundary] )
		 */
		public function getBoundary( $bMultiBoundary=false )
		{
			return "Konsolidate-Part" . ( ( (int) $bMultiBoundary ) + 1 ) . "-{$this->_boundary}";
		}

		/**
		 *  create a multipart head in the SMTP instance
		 *  @name    _setMultiPartHeader
		 *  @type    method
		 *  @access  protected
		 *  @param   object SMTP instance
		 *  @param   bool   use extra boundary
		 *  @return  bool
		 *  @syntax  bool CoreMail->send()
		 */
		protected function _setMultiPartHeader( $oMail, $bMultiBoundary=false, $bMixedContent=false )
		{
			$oMail->addHeader( "Message-ID", "<" . $this->call( "/Key/create", "XXX-XXX" ) . "-" . time() . "-" . md5( $this->_from ) . "-{$this->_from}>" );
			$oMail->addHeader( "MIME-Version", "1.0" );
			$oMail->addHeader( "Content-Type", "multipart/" . ( $bMixedContent ? "mixed" : "alternative" ) . "; \r\n\tboundary=\"" . $this->getBoundary( $bMultiBoundary ) . "\"" );
		}

		/**
		 *  apply given encoding to a string
		 *  @name    _createBoundary
		 *  @type    method
		 *  @access  protected
		 *  @param   bool  closing boundary
		 *  @param   bool  multiboundary
		 *  @return  string
		 *  @syntax  string CoreMail->_createBoundary( [ bool closing [, bool multi ] ] )
		 */
		protected function _createBoundary( $bClosingBoundary=false, $bMultiBoundary=false )
		{
			return "--" . $this->getBoundary( $bMultiBoundary ) . ( $bClosingBoundary ? "--\r\n" : "" ) . "\r\n";
		}

		/**
		 *  Create the Content-Type meta-data
		 *  @name    _createContentType
		 *  @type    method
		 *  @access  protected
		 *  @param   array  param
		 *  @return  string meta-data
		 *  @syntax  string CoreMail->_createContentType( array param )
		 *  @note    param is an associative array, consisting of the following keys:
		 *           type:        the mime-type                         [optional, default 'application/octect-stream'],
		 *           charset:     the character set being used          [optional, left out if omitted]
		 *           encoding:    the encoding type                     [optional, left out of ommited]
		 *           boundary:    the encapsulating boundary            [optional, left out of ommited]
		 *  @see     _createDataSegment
		 */
		protected function _createContentType( $aParam )
		{
			$sType     = CoreTool::arrVal( "type", $aParam, "application/octet-stream" );
			$sCharset  = CoreTool::arrVal( "charset", $aParam );
			$sName     = CoreTool::arrVal( "filename", $aParam );
			$sBoundary = trim( CoreTool::arrVal( "boundary", $aParam ) );

			return "Content-Type: {$sType}" .
					( !empty( $sCharset ) ? ";\r\n\tcharset=\"{$sCharset}\"" : "" ) .
					( !empty( $sName ) ? ";\r\n\tname=\"{$sName}\"" : "" ) .
					( !empty( $sBoundary ) ? ";\r\n\tboundary=\"{$sBoundary}\"" : "" ) . "\r\n";
		}

		/**
		 *  Create the Content-Transfer-Encoding meta-data
		 *  @name    _createContentTransferEncoding
		 *  @type    method
		 *  @access  protected
		 *  @param   array  param
		 *  @return  string meta-data
		 *  @syntax  string CoreMail->_createContentTransferEncoding( array param )
		 *  @note    param is an associative array, consisting of the following keys:
	 	 *           encoding:    the encoding type                     [optional, left out of ommited]
		 *  @see     _createDataSegment
		 */
		protected function _createContentTransferEncoding( $aParam )
		{
			$sEncoding = CoreTool::arrVal( "encoding", $aParam );
			return "Content-Transfer-Encoding: {$sEncoding}\r\n";
		}

		/**
		 *  Create the Content-Disposition meta-data
		 *  @name    _createContentDisposition
		 *  @type    method
		 *  @access  protected
		 *  @param   array  param
		 *  @return  string meta-data
		 *  @syntax  string CoreMail->_createContentDisposition( array param )
		 *  @note    param is an associative array, consisting of the following keys:
	 	 *           disposition: the content disposition               [optional, left out of ommited]
	 	 *           filename:    the name of the file being announced  [optional, left out of ommited]
		 *  @see     _createDataSegment
		 */
		protected function _createContentDisposition( $aParam )
		{
			$sDisposition = CoreTool::arrVal( "disposition", $aParam );
			$sFilename    = CoreTool::arrVal( "filename", $aParam );
			return !empty( $sDisposition ) ? "Content-Disposition: {$sDisposition}" .
					( !empty( $sFilename ) ? ";\r\n\tfilename={$sFilename}" : "" ) . "\r\n" : "";
		}

		/**
		 *  Create the Content-ID meta-data
		 *  @name    _createContentID
		 *  @type    method
		 *  @access  protected
		 *  @param   array  param
		 *  @return  string meta-data
		 *  @syntax  string CoreMail->_createContentID( array param )
		 *  @note    param is an associative array, consisting of the following keys:
		 *           filename:    the name of the file being announced  [optional, left out of ommited]
		 *           cid:         the content-id reference              [optional, left out of ommited]
		 *  @see     _createDataSegment
		 */
		protected function _createContentID( $aParam )
		{
			$sFilename  = CoreTool::arrVal( "filename", $aParam );
			$sContentID = CoreTool::arrVal( "cid", $aParam );
			return !empty( $sContentID ) ? "Content-ID: <{$sContentID}/{$sFilename}>\r\n" : "";
		}

		/**
		 *  Create a data segment
		 *  @name    _createDataSegment
		 *  @type    method
		 *  @access  protected
		 *  @param   string data
		 *  @param   array  param
		 *  @param   bool   use multiboundary
		 *  @return  string datablock
		 *  @syntax  string CoreMail->_createDataSegment( string data, array param [, bool multiboundary [, bool boundary ] ] )
		 *  @note    param is an associative array, consisting of the following keys:
		 *           type:        the mime-type                         [optional, default 'application/octect-stream'],
		 *           charset:     the character set being used          [optional, left out if omitted]
		 *           encoding:    the encoding type                     [optional, left out of ommited]
		 *           disposition: the content disposition               [optional, left out of ommited]
		 *           filename:    the name of the file being announced  [optional, left out of ommited]
		 *           cid:         the content-id reference              [optional, left out of ommited]
		 *           boundary:    the encapsulating boundary            [optional, left out of ommited]
		 */
		protected function _createDataSegment( $sData, $aParam, $bMultiBoundary=false )
		{
			$sEncoding = CoreTool::arrVal( "encoding", $aParam, $this->_encoding );
			$sReturn   = $this->_createBoundary( false, $bMultiBoundary ) .
			             $this->_createContentType( $aParam ) .
			             $this->_createContentTransferEncoding( $aParam ) .
		    	         $this->_createContentDisposition( $aParam ) .
		        	     $this->_createContentID( $aParam ) . "\r\n";
			$sReturn  .= $this->_applyEncoding( $sData, $sEncoding ) . "\r\n\r\n";
			return $sReturn;
		}

		/**
		 *  apply substitutes to a string
		 *  @name    _substitute
		 *  @type    method
		 *  @access  protected
		 *  @param   string data
		 *  @return  string
		 *  @syntax  string CoreMail->_substitute( string data )
		 */
		protected function _substitute( $sValue )
		{
			if ( is_array( $this->_replace ) && (bool) count( $this->_replace ) )
				foreach( $this->_replace as $sPattern=>$sReplacement )
					$sValue = str_replace( $sPattern, $sReplacement, $sValue );
			return $sValue;
		}

		/**
		 *  add a recipient for the mail
		 *  @name    addRecipient
		 *  @type    method
		 *  @access  public
		 *  @param   string email
		 *  @param   string name
		 *  @param   string type (one of: 'to', 'cc', 'bcc'. all others are silently discarded)
		 *  @return  string
		 *  @syntax  string CoreMail->addRecipient( string email [, string name [, string type ] ] )
		 */
		public function addRecipient( $sEmail, $sName=null, $sType="to" )
		{
			if ( in_array( $sType, Array( "to", "cc", "bcc" ) ) )
				$this->_addRecipient( Array( $sEmail=>$sName ), $sType );
		}

		/**
		 *  add a 'to' address for the mail
		 *  @name    addTo
		 *  @type    method
		 *  @access  public
		 *  @param   string email
		 *  @param   string name
		 *  @return  string
		 *  @syntax  string CoreMail->addTo( string email [, string name ] )
		 */
		public function addTo( $sEmail, $sName=null )
		{
			$this->_addRecipient( is_null( $sName ) ? Array( $sEmail ) : Array( $sEmail=>$sName ), "to" );
		}

		/**
		 *  add a 'cc' address for the mail
		 *  @name    addCC
		 *  @type    method
		 *  @access  public
		 *  @param   string email
		 *  @param   string name
		 *  @return  string
		 *  @syntax  string CoreMail->addCC( string email [, string name ] )
		 */
		public function addCC( $sEmail, $sName=null )
		{
			$this->_addRecipient( is_null( $sName ) ? Array( $sEmail ) : Array( $sEmail=>$sName ), "cc" );
		}

		/**
		 *  add a 'bcc' address for the mail
		 *  @name    addBCC
		 *  @type    method
		 *  @access  public
		 *  @param   string email
		 *  @param   string name
		 *  @return  string
		 *  @syntax  string CoreMail->addBCC( string email [, string name ] )
		 */
		public function addBCC( $sEmail, $sName=null )
		{
			$this->_addRecipient( is_null( $sName ) ? Array( $sEmail ) : Array( $sEmail=>$sName ), "bcc" );
		}

		/**
		 *  add a recipient to the queue
		 *  @name    _addRecipient
		 *  @type    method
		 *  @access  protected
		 *  @param   mixed  email (string "email", string "Pretty Name <email>", Array( "email" ), Array( "email"=>"Pretty Name" ) )
		 *  @param   string type (one of: 'to', 'cc', 'bcc'. all others are silently discarded)
		 *  @return  string
		 *  @syntax  string CoreMail->_addRecipient( mixed email, string type )
		 */
		protected function _addRecipient( $mValue, $sType )
		{
			if ( in_array( $sType, Array( "to", "cc", "bcc" ) ) )
			{
				$aRecipient = Array();
				if ( is_string( $mValue ) )
				{
					$aTMP = explode( ";", $mValue );
					foreach( $aTMP as $sField )
					{
						preg_match( "/([^<>]+)<?([^<>]*)>?/", $sField, $aMatch );
						if ( count( $aMatch ) >= 3 )
						{
							if ( !empty( $aMatch[ 2 ] ) )
								$aRecipient[ trim( $aMatch[ 2 ] ) ] = trim( $aMatch[ 1 ] );
							else
								$aRecipient[ trim( $aMatch[ 1 ] ) ] = null;
						}
					}
				}
				elseif ( is_array( $mValue ) )
				{
					foreach( $mValue as $sKey=>$sValue )
					{
						if ( is_numeric( $sKey ) )
							$aRecipient[ $sValue ] = null;
						else
							$aRecipient[ $sKey ] = $sValue;
					}
				}
				if ( (bool) count( $aRecipient ) )
					$this->{"_{$sType}"} = array_merge( $this->{"_{$sType}"}, $aRecipient );
			}
		}

		/**
		 *  remove all recipients of type $sType
		 *  @name    _flushRecipientType
		 *  @type    method
		 *  @access  protected
		 *  @param   string type (one of: 'to', 'cc', 'bcc'. all others are silently discarded)
		 *  @return  string
		 *  @syntax  string CoreMail->_flushRecipientType( string type )
		 */
		protected function _flushRecipientType( $sType )
		{
			if ( in_array( $sType, Array( "to", "cc", "bcc" ) ) )
				$this->{"_{$sType}"} = Array();
		}

		/**
		 *  Does the e-mail require a boundary (plain text body only, no other charset than UTF-8 and no encoding other than 8bit)
		 *  @name    _requireBoundary
		 *  @type    method
		 *  @access  protected
		 *  @return  bool
		 *  @syntax  bool CoreMail->_requireBoundary()
		 */
		protected function _requireBoundary()
		{
			return (
				!empty( $this->_richcontent ) ||               //  richcontent
				$this->_encoding != self::DEFAULT_ENCODING ||  //  non-standard encoding
				$this->_charset != self::DEFAULT_CHARSET       //  non-standard characterset
			);
		}

		/**
		 *  dynamically set properties and take special care of them
		 *  @name    __set
		 *  @type    method
		 *  @access  public
		 *  @param   string property
		 *  @param   mixed  value
		 *  @return  void
		 *  @syntax  void CoreMail->(string property) = mixed variable
		 */
		public function __set( $sProperty, $mValue )
		{
			if ( property_exists( $this, "_{$sProperty}" ) )
			{
				if ( !$this->_loading || ( $this->_loading && empty( $this->{"_{$sProperty}"} ) ) )
					switch( strToLower( $sProperty ) )
					{
						case "to":
						case "cc":
						case "bcc":
							//  setting a variable indicates existing values should be destroyed, flushing the current set
							$this->_flushRecipientType( strToLower( $sProperty ) );
							$this->_addRecipient( $mValue, strToLower( $sProperty ) );
							break;
						case "job":
							$this->_loading = true;
							$this->call( "Content/load", $mValue );
							$this->_loading = false;
							// no break, we still want the property to be set
						default:
							$this->{"_{$sProperty}"} = $mValue;
							break;
				}
			}
			else
			{
				parent::__set( $sProperty, $mValue );
			}
		}

		/**
		 *  get properties according to special defined rules
		 *  @name    __get
		 *  @type    method
		 *  @access  public
		 *  @param   string property
		 *  @return  mixed  value
		 *  @syntax  mixed = CoreMail->(string property);
		 */
		public function __get( $sProperty )
		{
			if ( property_exists( $this, "_{$sProperty}" ) )
				return $this->{"_{$sProperty}"};
			return parent::__get( $sProperty );
		}
	}

?>