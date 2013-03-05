<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreNetworkProtocolHTTP
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Network/Protocol/HTTP
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Basic implementation of the HTTP protocol
	 *  @name    CoreNetworkProtocolHTTP
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 *  @todo    Make proper use of the CoreNetworkSocket class, cURL fallback (for ease and performance) and implement HTTPS support
	 */
	class CoreNetworkProtocolHTTP extends Konsolidate
	{
		/**
		 *  The class version
		 *  @name    version
		 *  @type    string
		 *  @access  public
		 */
		public $version;
	
		/**
		 *  The array containing all prepared data
		 *  @name    _storage
		 *  @type    string
		 *  @access  protected
		 */
		protected $_storage;
	
		/**
		 *  The array containing all prepared files
		 *  @name    _filestorage
		 *  @type    string
		 *  @access  protected
		 */
		protected $_filestorage;
	
		/**
		 *  A boolean describing whether or not to use multipart/form-data
		 *  @name    _multiform
		 *  @type    string
		 *  @access  protected
		 */
		protected $_multiform;
	
		/**
		 *  The useragent to use
		 *  @name    _useragent
		 *  @type    string
		 *  @access  protected
		 */
		protected $_useragent;
	
		/**
		 *  An array containing status handlers
		 *  @name    _statushandler
		 *  @type    string
		 *  @access  protected
		 */
		protected $_statushandler;
	
		/**
		 *  An array containing the result headers that were send back after a request
		 *  @name    _requestheader
		 *  @type    string
		 *  @access  protected
		 */
		protected $_requestheader;
	
		/**
		 *  An array containing the result headers that are added to all requests
		 *  @name    _headerdata
		 *  @type    string
		 *  @access  protected
		 */
		protected $_headerdata;
	
	
		/**
		 *  CoreNetworkProtocolHTTP constructor
		 *  @name    CoreNetworkProtocolHTTP
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreNetHTTP( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->version        = "1.0.7";
			$this->_storage       = Array();
			$this->_filestorage   = Array();
			$this->_multiform     = false;
			$this->_useragent     = "";
			$this->_statushandler = Array();
			$this->_headerdata    = Array();
		}

		/**
		 *  Assign variables to the upcoming request
		 *  @name   prepareData
		 *  @type   method
		 *  @access public
		 *  @param  mixed  $mVariable    either an array containing key=>value pairs, which will be prepared as variables, or a string with the variable name
		 *  @param  mixed  $mValue       the value to set, note that $mValue will not be processed if you have provided an array as first variable
		 *  @return bool
		 */
		public function prepareData( $mVariable, $mValue=false )
		{
			$bSuccess = true;
			if ( is_array( $mVariable ) )
			{
				foreach( $mVariable as $sKey=>$mValue )
					$bSuccess &= $this->prepareData( $sKey, $mValue );
				return $bSuccess;
			}
			elseif ( is_array( $mValue ) )
			{
				foreach( $mValue as $mKey=>$mSubValue )
					$bSuccess &= $this->prepareData( "{$mVariable}[" . ( is_integer( $mKey ) ? $mKey : "'{$mKey}'" ). "]", $mSubValue );
				return $bSuccess;
			}
			elseif ( is_string( $mVariable ) )
			{
				if ( is_object( $mValue ) )
					$mValue = serialize( $mValue );
				$this->_storage[ $mVariable ] = $mValue;
				return( $this->_storage[ $mVariable ] == $mValue );
			}
			return false;
		}
	
		/**
		 *  Add files to the upcoming request
		 *  @name   prepareFile
		 *  @type   method
		 *  @access public
		 *  @param  string $sFile    The filename (including path) of the file that ought to be uploaded
		 *  @param  string $sMime    The mime-type to use for the file [optional, defaults to 'application/octet-stream' which works for most files]
		 *  @return bool
		 *  @note   requires the request to be of type 'POST'), one additional variable will be added to the request. The variable is called 'http_filecount' and contains the number of files being POSTed)
		 */
		public function prepareFile( $sFile, $sMime="" )
		{
			if ( file_exists( $sFile ) )
			{
				if ( empty( $sMime ) )
					$sMime = "application/octet-stream";
	
				$fpFile = fopen( $sFile, "rb" );
				if ( $fpFile )
				{
					$sData = "";
					while ( !feof( $fpFile ) )
						$sData .= fgets( $fpFile, fileSize( $sFile ) );
					fclose( $fpFile );
	
					$this->_filestorage[] = Array(
						"name"=>$sFile,
						"data"=>$sData,
						"mime"=>$sMime
					);
					if ( strLen( $sData ) > 0 )
					{
						$this->_multiform = true;
						return true;
					}
				}
	
			}
			return false;
		}
	
		/**
		 *  bind a statushandler function to a status code
		 *  (NOTE: the function may receive up to two arguments, the first the status code (so you _can_ write a catchAll/catchMulti function),
		 *   the second being the HTTPRequest object itself, hint: make it a reference if you need it)
		 *  @name   setStatusHandler
		 *  @type   method
		 *  @access public
		 *  @param  number  $nStatus   The status code to respond on
		 *  @param  string  $sFunction The function to call if status equals $nStatus
		 *  @return void
		 */
		public function setStatusHandler( $nStatus, $sFunction )
		{
			$this->_statushandler[ $nStatus ] = $sFunction;
		}
	
		/**
		 *  Trigger a specific status handler (if it's defined)
		 *  @name   _triggerStatusHandler
		 *  @type   method
		 *  @access protected
		 *  @param  number $nStatus The status number
		 *  @return void
		 */
		protected function _triggerStatusHandler( $nStatus )
		{
			if ( CoreTool::arrVal( $this->_statushandler, $nStatus, false ) )
				$this->_statushandler[ $nStatus ]( $nStatus, $this );
		}
	
		/**
		 *  Get the response line of the last request
		 *  @name   getResponse
		 *  @type   method
		 *  @access public
		 *  @return string
		 */
		public function getResponse()
		{
			return $this->getHeader( "response" );
		}
	
		/**
		 *  Get the response status of the last request
		 *  @name   getResponseStatus
		 *  @type   method
		 *  @access public
		 *  @return string
		 */
		public function getResponseStatus()
		{
			return $this->getHeader( "status" );
		}
	
		/**
		 *  Get the response info-text of the last requests status
		 *  @name   getResponseInfo
		 *  @type   method
		 *  @access public
		 *  @return string
		 */
		public function getResponseInfo()
		{
			return $this->getHeader( "statusinfo" );
		}
	
		/**
		 *  Get the response protocol of the last request
		 *  @name   getResponseProtocol
		 *  @type   method
		 *  @access public
		 *  @return string
		 */
		public function getResponseProtocol()
		{
			return $this->getHeader( "protocol" );
		}
	
		/**
		 *  Get a specific header from the last request
		 *  @name   getHeader
		 *  @type   method
		 *  @access public
		 *  @param  string $sHeader  The header you wish to read [optional, returns all headers in an array if ommited)
		 *  @return string|array|bool
		 */
		public function getHeader( $sHeader="" )
		{
			if ( empty( $sHeader ) )
				return $this->_requestheader;
			else if ( is_array( $this->_requestheader ) && array_key_exists( $sHeader, $this->_requestheader ) )
				return $this->_requestheader[ $sHeader ];
			return false;
		}
	
		/**
		 *  Set a header to add to all upcoming requests
		 *  @name   setHeader
		 *  @type   method
		 *  @access public
		 *  @since  1.0.3
		 *  @param  mixed  $mHeader    either an array containing key=>value pairs, which will be prepared as headers, or a string with the header name
		 *  @param  mixed  $mValue     the value to set, note that $mValue will not be processed if you have provided an array as first variable
		 *                             if the value is ommited or empty (0/false/'') the header will not be send
		 *  @return void
		 */
		public function setHeader( $mHeader, $mValue=false )
		{
			$bSuccess = true;
			if ( is_array( $mHeader ) )
			{
				foreach( $mHeader as $sKey=>$mValue )
					$bSuccess &= $this->setHeader( $sKey, $mValue );
				return $bSuccess;
			}
			elseif ( is_string( $mHeader ) )
			{
				$this->_headerdata[ $mHeader ] = $mValue;
				return( $this->_headerdata[ $mHeader ] == $mValue );
			}
			return false;
		}
	
		/**
		 *  store the headers seperatly
		 *  @name   _parseHeader
		 *  @type   method
		 *  @access protected
		 *  @param  array $aHeader The Array of headers
		 *  @return void
		 */
		protected function _parseHeader( $aHeader )
		{
			for ( $i = 0; $i < count( $aHeader ); ++$i )
				if ( $i == 0 ) // the status reply (also starts a new array, which prevents mixing previous header info
				{
					$aHeaderPart = explode( " ", $aHeader[ $i ], 3 );
					$this->_requestheader = Array(
						"response"=>$aHeader[ $i ],
						"protocol"=>$aHeaderPart[ 0 ],
						"status"=>$aHeaderPart[ 1 ],
						"statusinfo"=>$aHeaderPart[ 2 ]
					);
				}
				else // other headers
				{
					$aHeaderPart = explode( ":", $aHeader[ $i ], 2 );
					$this->_requestheader[ $aHeaderPart[ 0 ] ] = trim( $aHeaderPart[ 1 ] );
				}
		}
	
		/**
		 *  get all required information from the path provided to a request
		 *  @name   _parseURL
		 *  @type   method
		 *  @access protected
		 *  @param  string $sURL  The URL to parse
		 *  @return void
		 */
		protected function _parseURL( $sURL )
		{
			if ( !strPos( $sURL, "://" ) )
				$sURL = "http://{$sURL}";
			$aURL          = parse_url( $sURL );
			$this->host   = CoreTool::arrVal( $aURL, "host", $_SERVER[ "HTTP_HOST" ] );
			$this->path   = CoreTool::arrVal( $aURL, "path", "/" );
			$this->scheme = CoreTool::arrVal( $aURL, "scheme", "http" );
			$this->port   = (int) CoreTool::arrVal( $aURL, "port", 80 );
		}
	
		/**
		 *  Build up the actual data transportation string
		 *  @name   _buildDataString
		 *  @type   method
		 *  @access protected
		 *  @param  string $sMethod   The request method to use
		 *  @param  string $sBoundary The boundary to use to seperate variables/files from eachother
		 *  @return string
		 */
		protected function _buildDataString( $sMethod="GET", $sBoundary="++HTTPRequest++" )
		{
			//  If we are sending files, add a variable telling the receiving end how many files are being transmitted
			if ( strToUpper( $sMethod ) == "POST" && count( $this->_filestorage ) > 0 )
				$aStorage = array_merge( $this->_storage, Array( "http_filecount"=>count( $this->_filestorage ) ) );
			else
				$aStorage = $this->_storage;
	
			$sData  = "";
			foreach( $aStorage as $sKey=>$sValue )
			{
				if ( $this->_multiform )
				{
					$sData .= "--{$sBoundary}\n";
					$sData .= "Content-Disposition: form-data; name=\"{$sKey}\"\n";
					$sData .= "\n";
					$sData .= "{$sValue}\n";
				}
				else
				{
					$sData .= ( empty( $sData ) ? "" : "&" ) . "{$sKey}=" . urlencode( $sValue );
				}
			}
	
			if ( strToUpper( $sMethod ) == "POST" && count( $this->_filestorage ) > 0 )
			{
				for ( $i = 0; $i < count( $this->_filestorage ); ++$i )
				{
					$sKey   = $i + 1;
					$sData .= ( $i > 0 ? "\n" : "" ) . "--{$sBoundary}\n";
					$sData .= "Content-Disposition: form-data; name=\"file{$sKey}\"; filename=\"{$this->_filestorage[$i]["name"]}\"\n";
					$sData .= "Content-Type: {$this->_filestorage[$i]["mime"]}\n";
					$sData .= "\n";
					$sData .= "{$this->_filestorage[$i]["data"]}";
				}
			}
			if ( $this->_multiform )
				$sData .= "--{$sBoundary}--";
	
			return $sData;
		}
	
		/**
		 *  Build up the entire request
		 *  @name   _buildRequestString
		 *  @type   method
		 *  @access protected
		 *  @param  string $sMethod   The request method to use
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		protected function _buildRequestString( $sMethod="GET", $mReferer=false )
		{
			$sBoundary = str_pad( substr( md5( time() ), 0, 12 ), 40, "-", STR_PAD_LEFT );
			$sMethod   = strToUpper( $sMethod );
			$sData     = $this->_buildDataString( $sMethod, $sBoundary );
	
			// Set or override headers
			$this->setHeader( 
				Array(
					"Host"=>$this->host,
					"User-Agent"=>( !empty( $this->_useragent ) ? "{$this->_useragent} " : "" ) . "HTTPRequest/{$this->version} (PHP Class; klof++ 2005)",
					"Referer"=>( !empty( $mReferer ) ? $mReferer : "http://{$_SERVER[ "HTTP_HOST" ]}{$_SERVER["REQUEST_URI"]}" ),
					"Connection"=>"close"
				)
			);
			$sRequest  = "{$sMethod} {$this->path}" . ( $sMethod == "GET" && !empty( $sData ) ? "?$sData" : "" ) . " HTTP/1.1\n";
	
			if ( count( $this->_headerdata ) > 0 )
				foreach( $this->_headerdata as $sKey=>$sValue )
					if ( !empty( $sValue ) )
						$sRequest .= "{$sKey}: {$sValue}\n";
	
			if ( $sMethod == "POST" )
			{
				$sRequest .= "Content-type: " . ( $this->_multiform ? "multipart/form-data; boundary={$sBoundary}" : "application/x-www-form-urlencoded" ) . "\n";
				$sRequest .= "Content-length: " . strlen( $sData ) . "\r\n\r\n";
				$sRequest .= "{$sData}";
			}
			$sRequest .= "\r\n\r\n";
	
			return $sRequest;
		}
	
		/**
		 *  prepare and perform an actual request
		 *  @name   request
		 *  @type   method
		 *  @access public
		 *  @param  string $sMethod   The request method to use
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		public function request( $sMethod, $sURL, $aData=Array(), $mReferer=false )
		{
			//  Files cannot be transmitted with a GET request, so even if files were added, we do not use multipart/form-data		
			if ( strToUpper( $sMethod ) == "GET" && $this->_multiform )
				$this->_multiform = false;
	
			//  Prepare all request URL requirements
			$this->_parseURL( $sURL );
	
			//  Prepare all data (NOTE: variables set with PostRequest::prepare will be overwritten by variables provided in $aData if they carry the same name!)
			$this->prepareData( $aData );
	
			//  Prepare the actual request
			$sRequest = $this->_buildRequestString( $sMethod, $mReferer );
	
			//  Open the connection, post the data and read the feedback
			$fpConn = @fsockopen( $this->host, $this->port );
			if ( $fpConn )
			{
				$aResult = Array( 
					"header"=>Array(),
					"content"=>""
				);
				$bHeader         = true;
				$bChunkedTranfer = false;
				$bBeginChunk     = false;
				$nReadBytes      = 1024;
	
				fputs( $fpConn, $sRequest, strLen( $sRequest ) );
				while( !feof( $fpConn ) )
				{
					$sResult = fgets( $fpConn, $nReadBytes );
					$sTrim   = trim( $sResult );

					if ( empty( $sTrim ) && $bHeader ) // determine wether or not the header has ended (this empty line is not added to either the header or the content)
					{
						$bHeader = false;
						$this->_parseHeader( $aResult[ "header" ] );

						if ( $this->getHeader( "Transfer-Encoding" ) == "chunked" ) // if the content is delivered in chunks, we need to handle the content slightly different
						{
							$bChunkedTranfer = true;
							$bBeginChunk     = true;
						}
					}
					elseif ( $bHeader ) // add the result to the header array
					{
						$aResult[ "header" ][] = $sTrim;
					}
					else // add the result to the content string
					{
						if ( $bChunkedTranfer ) // we should handle chunked data delivery
						{
							if ( $bBeginChunk ) // we are at the beginning of an era (chunk wise)
							{
								$bBeginChunk = false;
								$nReadBytes  = hexdec( $sTrim ); // chunk sizes are provided as HEX values
								if ( $nReadBytes == 0 )
									break;
								unset( $sResult ); // clear sResult
							}
							else if ( is_numeric( $sTrim ) && $sTrim == 0 ) // the end of the chunk has been reached
							{
								$nBeginChunk = true;
								$nReadBytes  = 1024;
								unset( $sResult ); // clear sResult
							}
						}
						if ( !empty( $sResult ) ) // do we have content?
							$aResult[ "content" ] .= $sResult;
					}
				}
	
				fclose( $fpConn );
				$this->_triggerStatusHandler( $this->getResponseStatus() );

				return $aResult[ "content" ];
			}
			return false;
		}
	
		/**
		 *  Do a 'POST' request
		 *  (NOTE: if you are POSTing files, one additional variable is added to the request. The variable is called 'http_filecount' and contains the number of files being POSTed)
		 *  @name   post
		 *  @type   method
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		public function post( $sURL, $aData=Array(), $mReferer=false )
		{
			return $this->request( "post", $sURL, $aData, $mReferer );
		}
	
		/**
		 *  Do a 'GET' request
		 *  @name   get
		 *  @type   method
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		public function get()
		{
			$aArgument = func_get_args();
			$sURL      = array_shift( $aArgument );
			$aData     = (bool) count( $aArgument ) ? array_shift( $aArgument ) : Array();
			$mReferer  = (bool) count( $aArgument ) ? array_shift( $aArgument ) : false;

			return $this->request( "get", $sURL, $aData, $mReferer );
		}
	
		/**
		 *  Do a 'HEAD' request
		 *  @name   head
		 *  @type   method
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		public function head( $sURL, $aData=Array(), $mReferer=false )
		{
			return $this->request( "head", $sURL, $aData, $mReferer );
		}
	
		/**
		 *  Do a 'OPTIONS' request
		 *  @name   options
		 *  @type   method
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @return string
		 */
		public function options( $sURL )
		{
			return $this->request( "options", $sURL );
		}
	
		/**
		 *  Do a 'TRACE' request
		 *  @name   trace
		 *  @type   method
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @return string
		 */
		public function trace( $sURL )
		{
			return $this->request( "trace", $sURL );
		}
	}

?>