<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	 *  @author  Rogier Spieker <rogier@klof.net>
	 *  @todo    Make proper use of the CoreNetworkSocket class, cURL fallback (for ease and performance) and implement HTTPS support
	 */
	class CoreNetworkProtocolHTTP extends Konsolidate
	{
		/**
		 *  The class version
		 *  @var string $version
		 */
		public $version;
	
		/**
		 *  The array containing all prepared data
		 *  @var array $storage
		 */
		protected $storage;
	
		/**
		 *  The array containing all prepared files
		 *  @var array $filestorage
		 */
		protected $filestorage;
	
		/**
		 *  A boolean describing whether or not to use multipart/form-data
		 *  @var bool $multiform
		 */
		protected $multiform;
	
		/**
		 *  The useragent to use
		 *  @var string $useragent
		 */
		protected $useragent;
	
		/**
		 *  An array containing status handlers
		 *  @var array $statushandler
		 */
		protected $statushandler;
	
		/**
		 *  An array containing the result headers that were send back after a request
		 *  @var $requestheader
		 */
		protected $requestheader;
	
		/**
		 *  An array containing the result headers that are added to all requests
		 *  @var $headerdata
		 */
		protected $headerdata;
	
	
		/**
		 *  CoreNetHTTP constructor
		 *  @name    CoreNetHTTP
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreNetHTTP( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->version       = "1.0.6";
			$this->storage       = Array();
			$this->filestorage   = Array();
			$this->multiform     = false;
			$this->useragent     = "";
			$this->statushandler = Array();
			$this->headerdata    = Array();
		}

		/**
		 *  Assign variables to the upcoming request
		 *  @access public
		 *  @param  mixed  $mVariable    either an array containing key=>value pairs, which will be prepared as variables, or a string with the variable name
		 *  @param  mixed  $mValue       the value to set, note that $mValue will not be processed if you have provided an array as first variable
		 *  @return bool
		 */
		function prepareData( $mVariable, $mValue=false )
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
				$this->storage[ $mVariable ] = $mValue;
				return( $this->storage[ $mVariable ] == $mValue );
			}
			return false;
		}
	
		/**
		 *  Add files to the upcoming request
		 *  (NOTE: requires the request to be of type 'POST')
		 *  (NOTE: one additional variable will be added to the request. The variable is called 'http_filecount' and contains the number of files being POSTed)
		 *  @access public
		 *  @param  string $sFile    The filename (including path) of the file that ought to be uploaded
		 *  @param  string $sMime    The mime-type to use for the file [optional, defaults to 'application/octet-stream' which works for most files]
		 *  @return bool
		 */
		function prepareFile( $sFile, $sMime="" )
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
	
					$this->filestorage[] = Array(
						"name"=>$sFile,
						"data"=>$sData,
						"mime"=>$sMime
					);
					if ( strLen( $sData ) > 0 )
					{
						$this->multiform = true;
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
		 *  @access public
		 *  @param  number  $nStatus   The status code to respond on
		 *  @param  string  $sFunction The function to call if status equals $nStatus
		 *  @return void
		 */
		function setStatusHandler( $nStatus, $sFunction )
		{
			$this->statushandler[ $nStatus ] = $sFunction;
		}
	
		/**
		 *  Trigger a specific status handler (if it's defined)
		 *  @access private
		 *  @param  number $nStatus The status number
		 *  @return void
		 */
		function triggerStatusHandler( $nStatus )
		{
			if ( CoreTool::arrVal( $this->statushandler, $nStatus, false ) )
				$this->statushandler[ $nStatus ]( $nStatus, $this );
		}
	
		/**
		 *  Get the response line of the last request
		 *  @see getResponseStatus(), getResponseInfo(), getResponseProtocol()
		 *  @access public
		 *  @return string
		 */
		function getResponse()
		{
			return $this->getHeader( "response" );
		}
	
		/**
		 *  Get the response status of the last request
		 *  @see getResponse(), getResponseInfo(), getResponseProtocol()
		 *  @access public
		 *  @return string
		 */
		function getResponseStatus()
		{
			return $this->getHeader( "status" );
		}
	
		/**
		 *  Get the response info-text of the last requests status
		 *  @see getResponseStatus(), getResponse(), getResponseProtocol()
		 *  @access public
		 *  @return string
		 */
		function getResponseInfo()
		{
			return $this->getHeader( "statusinfo" );
		}
	
		/**
		 *  Get the response protocol of the last request
		 *  @see getResponseStatus(), getResponseInfo(), getResponse()
		 *  @access public
		 *  @return string
		 */
		function getResponseProtocol()
		{
			return $this->getHeader( "protocol" );
		}
	
		/**
		 *  Get a specific header from the last request
		 *  @access public
		 *  @param  string $sHeader  The header you wish to read [optional, returns all headers in an array if ommited)
		 *  @return string|array|bool
		 */
		function getHeader( $sHeader="" )
		{
			if ( empty( $sHeader ) )
				return $this->requestheader;
			else if ( array_key_exists( $sHeader, $this->requestheader ) )
				return $this->requestheader[ $sHeader ];
			return false;
		}
	
		/**
		 *  Set a header to add to all upcoming requests
		 *  @access public
		 *  @since  1.0.3
		 *  @param  mixed  $mHeader    either an array containing key=>value pairs, which will be prepared as headers, or a string with the header name
		 *  @param  mixed  $mValue     the value to set, note that $mValue will not be processed if you have provided an array as first variable
		 *                             if the value is ommited or empty (0/false/'') the header will not be send
		 *  @return void
		 */
		function setHeader( $mHeader, $mValue=false )
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
				$this->headerdata[ $mHeader ] = $mValue;
				return( $this->headerdata[ $mHeader ] == $mValue );
			}
			return false;
		}
	
		/**
		 *  store the headers seperatly
		 *  @access private
		 *  @param  array $aHeader The Array of headers
		 *  @return void
		 */
		function parseHeader( $aHeader )
		{
			for ( $i = 0; $i < count( $aHeader ); ++$i )
				if ( $i == 0 ) // the status reply (also starts a new array, which prevents mixing previous header info
				{
					$aHeaderPart = explode( " ", $aHeader[ $i ], 3 );
					$this->requestheader = Array(
						"response"=>$aHeader[ $i ],
						"protocol"=>$aHeaderPart[ 0 ],
						"status"=>$aHeaderPart[ 1 ],
						"statusinfo"=>$aHeaderPart[ 2 ]
					);
				}
				else // other headers
				{
					$aHeaderPart = explode( ":", $aHeader[ $i ], 2 );
					$this->requestheader[ $aHeaderPart[ 0 ] ] = trim( $aHeaderPart[ 1 ] );
				}
		}
	
		/**
		 *  get all required information from the path provided to a request
		 *  @access private
		 *  @param  string $sURL  The URL to parse
		 *  @return void
		 */
		function parseURL( $sURL )
		{
			if ( !strPos( $sURL, "://" ) )
				$sURL = "http://{$sURL}";
			$aURL         = parse_url( $sURL );
			$this->host   = CoreTool::arrVal( $aURL, "host", $_SERVER[ "HTTP_HOST" ] );
			$this->path   = CoreTool::arrVal( $aURL, "path", "/" );
			$this->scheme = CoreTool::arrVal( $aURL, "scheme", "http" );
			$this->port   = (int) CoreTool::arrVal( $aURL, "port", 80 );
		}
	
		/**
		 *  Build up the actual data transportation string
		 *  @access private
		 *  @param  string $sMethod   The request method to use
		 *  @param  string $sBoundary The boundary to use to seperate variables/files from eachother
		 *  @return string
		 */
		function buildDataString( $sMethod="GET", $sBoundary="++HTTPRequest++" )
		{
			//  If we are sending files, add a variable telling the receiving end how many files are being transmitted
			if ( strToUpper( $sMethod ) == "POST" && count( $this->filestorage ) > 0 )
				$aStorage = array_merge( $this->storage, Array( "http_filecount"=>count( $this->filestorage ) ) );
			else
				$aStorage = $this->storage;
	
			$sData  = "";
			foreach( $aStorage as $sKey=>$sValue )
			{
				if ( $this->multiform )
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
	
			if ( strToUpper( $sMethod ) == "POST" && count( $this->filestorage ) > 0 )
			{
				for ( $i = 0; $i < count( $this->filestorage ); ++$i )
				{
					$sKey   = $i + 1;
					$sData .= ( $i > 0 ? "\n" : "" ) . "--{$sBoundary}\n";
					$sData .= "Content-Disposition: form-data; name=\"file{$sKey}\"; filename=\"{$this->filestorage[$i]["name"]}\"\n";
					$sData .= "Content-Type: {$this->filestorage[$i]["mime"]}\n";
					$sData .= "\n";
					$sData .= "{$this->filestorage[$i]["data"]}";
				}
			}
			if ( $this->multiform )
				$sData .= "--{$sBoundary}--";
	
			return $sData;
		}
	
		/**
		 *  Build up the entire request
		 *  @access private
		 *  @param  string $sMethod   The request method to use
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		function buildRequestString( $sMethod="GET", $mReferer=false )
		{
			$sBoundary = str_pad( substr( md5( time() ), 0, 12 ), 40, "-", STR_PAD_LEFT );
			$sMethod   = strToUpper( $sMethod );
			$sData     = $this->buildDataString( $sMethod, $sBoundary );
	
			// Set or override headers
			$this->setHeader( 
				Array(
					"Host"=>$this->host,
					"User-Agent"=>( !empty( $this->useragent ) ? "{$this->useragent} " : "" ) . "HTTPRequest/{$this->version} (PHP Class; klof++ 2005)",
					"Referer"=>( !empty( $mReferer ) ? $mReferer : "http://{$_SERVER[ "HTTP_HOST" ]}{$_SERVER["REQUEST_URI"]}" ),
					"Connection"=>"close"
				)
			);
			$sRequest  = "{$sMethod} {$this->path}" . ( $sMethod == "GET" && !empty( $sData ) ? "?$sData" : "" ) . " HTTP/1.1\n";
	
			if ( count( $this->headerdata ) > 0 )
				foreach( $this->headerdata as $sKey=>$sValue )
					if ( !empty( $sValue ) )
						$sRequest .= "{$sKey}: {$sValue}\n";
	
			if ( $sMethod == "POST" )
			{
				$sRequest .= "Content-type: " . ( $this->multiform ? "multipart/form-data; boundary={$sBoundary}" : "application/x-www-form-urlencoded" ) . "\n";
				$sRequest .= "Content-length: " . strlen( $sData ) . "\r\n\r\n";
				$sRequest .= "{$sData}";
			}
			$sRequest .= "\r\n\r\n";
	
			return $sRequest;
		}
	
		/**
		 *  prepare and perform an actual request
		 *  @access private
		 *  @param  string $sMethod   The request method to use
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		function request( $sMethod, $sURL, $aData=Array(), $mReferer=false )
		{
			//  Files cannot be transmitted with a GET request, so even if files were added, we do not use multipart/form-data		
			if ( strToUpper( $sMethod ) == "GET" && $this->multiform )
				$this->multiform = false;
	
			//  Prepare all request URL requirements
			$this->parseURL( $sURL );
	
			//  Prepare all data (NOTE: variables set with PostRequest::prepare will be overwritten by variables provided in $aData if they carry the same name!)
			$this->prepareData( $aData );
	
			//  Prepare the actual request
			$sRequest = $this->buildRequestString( $sMethod, $mReferer );
	
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
						$this->parseHeader( $aResult[ "header" ] );
	
						if ( $this->getHeader( "status" ) != 200 )
						{
							return false;
						}
						else if ( $this->getHeader( "Transfer-Encoding" ) == "chunked" ) // if the content is delivered in chunks, we need to handle the content slightly different
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
				$this->triggerStatusHandler( $this->getResponseStatus() );

				return $aResult[ "content" ];
			}
			return false;
		}
	
		/**
		 *  Do a 'POST' request
		 *  (NOTE: if you are POSTing files, one additional variable is added to the request. The variable is called 'http_filecount' and contains the number of files being POSTed)
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		function post( $sURL, $aData=Array(), $mReferer=false )
		{
			return $this->request( "post", $sURL, $aData, $mReferer );
		}
	
		/**
		 *  Do a 'GET' request
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		function get( $sURL, $aData=Array(), $mReferer=false )
		{
			return $this->request( "get", $sURL, $aData, $mReferer );
		}
	
		/**
		 *  Do a 'HEAD' request
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @param  array  $aData     additional paramaters to send (use key=>value pairs)
		 *  @param  mixed  $mReferer  The referer to provide
		 *  @return string
		 */
		function head( $sURL, $aData=Array(), $mReferer=false )
		{
			return $this->request( "head", $sURL, $aData, $mReferer );
		}
	
		/**
		 *  Do a 'OPTIONS' request
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @return string
		 */
		function options( $sURL )
		{
			return $this->request( "options", $sURL );
		}
	
		/**
		 *  Do a 'TRACE' request
		 *  @access public
		 *  @param  string $sURL      The URL to request
		 *  @return string
		 */
		function trace( $sURL )
		{
			return $this->request( "trace", $sURL );
		}
	}

?>