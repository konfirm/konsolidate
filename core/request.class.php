<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRequest
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Request
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Provide easy access to all request data
	 *  @name    CoreRequest
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreRequest extends Konsolidate
	{
		protected $_order;
		protected $_raw;
		protected $_xml;
		protected $_file;
		protected $_filereference;

		/**
		 *  magic __construct, CoreRequest constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreRequest( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		function __construct( &$oParent )
		{
			parent::__construct( $oParent );

			$this->_order         = $this->get( "/Config/Request/variableorder", "r" );
			$this->_raw           = null;
			$this->_xml           = null;
			$this->_file          = null;
			$this->_filereference = null;
			$this->_collect();
		}

		/**
		 *  is the request a POST
		 *  @name    isPosted
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreRequest->isPosted()
		 */
		public function isPosted()
		{
			return isset( $_SERVER[ "REQUEST_METHOD" ] ) && $_SERVER[ "REQUEST_METHOD" ] === "POST";
		}

		/**
		 *  retrieve the raw request data
		 *  @name    getRawRequest
		 *  @type    method
		 *  @access  public
		 *  @return  string (bool false, if no raw data is available)
		 *  @syntax  string CoreRequest->getRawRequest()
		 */
		public function getRawRequest()
		{
			return !is_null( $this->_raw ) ? $this->_raw : false;
		}

		/**
		 *  retrieve XML request data
		 *  @name    getXML
		 *  @type    method
		 *  @access  public
		 *  @return  SimpleXMLElement (bool false, if no xml data is available)
		 *  @syntax  SimpleXMLElement CoreRequest->getXML()
		 */
		public function getXML()
		{
			return !is_null( $this->_xml ) ? $this->_xml : false;
		}

		/**
		 *  retrieve variables from a raw data request
		 *  @name    _collectFromRaw
		 *  @type    method
		 *  @access  protected
		 *  @return  void
		 *  @syntax  void CoreRequest->_collectFromRaw()
		 */
		protected function _collectFromRaw()
		{
			$this->_raw = trim( file_get_contents( "php://input" ) );

			//  Try to determine what kind of request triggered this class
			switch( substr( $this->_raw, 0, 1 ) )
			{
				case "<": // XML
					// in-class for now
					$this->_xml = new SimpleXMLElement( $this->_raw );

					foreach( $this->_xml as $sParam=>$sValue )
						$this->$sParam = (string) $sValue;
					$this->call( "/Log/write", var_export( $this->_property, true ), 4 );
					break;
			}
		}

		/**
		 *  retrieve variables from a HTTP request (POST/GET only)
		 *  @name    _collectHTTP
		 *  @type    method
		 *  @access  protected
		 *  @return  void
		 *  @syntax  void CoreRequest->_collectHTTP()
		 */
		protected function _collectHTTP( $aCollection )
		{
			if ( is_array( $aCollection ) && (bool) count( $aCollection ) )
			{
				foreach( $aCollection as $sParam=>$mValue )
					$this->$sParam = $mValue;
				return true;
			}
			return false;
		}

		/**
		 *  retrieve variables and assign them to the Request module
		 *  @name    _collect
		 *  @type    method
		 *  @access  protected
		 *  @return  void
		 *  @syntax  void CoreRequest->_collect()
		 */
		protected function _collect()
		{
			//  gather variables and if request method is post and it failed to gather variables, try to collect data from raw input.
			if ( !$this->_collectHTTP( $this->_getCollection() ) && $this->isPosted() )
				$this->_collectFromRaw();

			// if the request method is post and the appear to be (one or more) files attached, prepare those aswel
			if ( $this->isPosted() && is_array( $_FILES ) && (bool) count( $_FILES ) )
				$this->_collectFiles();
		}


		/**
		 *  Determine the proper variable processing order
		 *  @name    _getCollection
		 *  @type    method
		 *  @access  protected
		 *  @return  array     if no order is specified, _GET or _POST global, merged result of the desired order otherwise
		 *  @syntax  array CoreRequest->_getCollection()
		 *  @note    By default _getCollection module will distinguish between GET and POST requests, they will not be processed both!
		 *           You can override this behaviour by setting the variable order (EGPCS, like the variables_order php.ini setting) to /Config/Request/variableorder
		 *           E.g. $this->set( "/Config/Request/variableorder", "GP" ); // combine GET and POST variables
		 */
		protected function _getCollection()
		{
			if ( !is_null( $this->_order ) )
			{
				$aReturn = Array();
				for( $i = 0; $i < strlen( $this->_order ); ++$i )
					switch( strToUpper( $this->_order{$i} ) )
					{
						case "G": $aReturn = array_merge( $aReturn, $_GET );    break;
						case "P": $aReturn = array_merge( $aReturn, $_POST );   break;
						case "C": $aReturn = array_merge( $aReturn, $_COOKIE ); break;
						case "R": $aReturn = array_merge( $aReturn, $_REQUEST ); break;
						case "E": $aReturn = array_merge( $aReturn, $_ENV );    break;
						case "S": $aReturn = array_merge( $aReturn, $_SERVER ); break;
					}
				return $aReturn;
			}
			return $this->isPosted() ? $_POST : $_GET;
		}

		/**
		 *  Gather file information attached to the (POST only) request
		 *  @name    _collectFiles
		 *  @type    method
		 *  @access  protected
		 *  @return  void
		 *  @syntax  void CoreRequest->_collectFiles()
		 */
		protected function _collectFiles()
		{
			$this->_file          = Array();
			$this->_filereference = Array();
			foreach( $_FILES as $sFieldName=>$aFile )
 				if ( isset( $aFile[ "error" ] ) ) // we have one or more file
 				{
 					if ( is_array( $aFile[ "error" ] ) ) // multiple files
 					{
 						$mFile = Array();
 						foreach( $aFile[ "error" ] as $sKey=>$mValue )
 						{
 							$oFile = $this->_createFileInstance( $aFile, $sFieldName, $sKey );
 							array_push( $this->_file, $oFile );
 							array_push( $mFile, $oFile );
 						}
 					}
 					else // single file
 					{
						$mFile = $this->_createFileInstance( $aFile, $sFieldName );
 						array_push( $this->_file, $mFile );
 					}
					$this->_filereference[ $sFieldName ] = $mFile;
 				}
		}

		/**
		 *  Create and populate an (unique) instance of the Request/File module
		 *  @name    _createFileInstance
		 *  @type    method
		 *  @access  protected
		 *  @param   array   _FILES record
		 *  @param   string  fieldname
		 *  @param   string  reference (only when multiple files are uploaded)
		 *  @return  void
		 *  @syntax  void CoreRequest->_collectFiles()
		 */
		protected function _createFileInstance( $aFile, $sVariable, $sReference=null )
		{
			$oTMP = $this->instance( "File" );
			$oTMP->variable = $sVariable;
			foreach( $aFile as $sProperty=>$mValue )
				$oTMP->{$sProperty} = is_null( $sReference ) ? $mValue : $mValue[ $sReference ];
			return $oTMP;
		}

		/**
		 *  Does the request have files attached?
		 *  @name    hasFiles
		 *  @type    method
		 *  @access  public
		 *  @return  void
		 *  @syntax  bool CoreRequest->hasFiles()
		 */
		public function hasFiles()
		{
			return is_array( $this->_file ) && (bool) count( $this->_file );
		}


		/**
		 *  Obtain the array of files
		 *  @name    getFiles
		 *  @type    method
		 *  @access  public
		 *  @param   bool  referenced array (non referenced array containing variable names)
		 *  @return  array files
		 *  @syntax  array CoreRequest->getFiles()
		 */
		public function getFiles( $bReference=false )
		{
			return $bReference ? $this->_filereference : $this->_file;
		}


		/**
		 *  Obtain a specific filereference (formfield)
		 *  @name    getFileByReference
		 *  @type    method
		 *  @access  public
		 *  @param   string reference name
		 *  @return  mixed  file object, array of file objects or false if reference does not exist
		 *  @syntax  array CoreRequest->getFileByReference( string reference )
		 */
		public function getFileByReference( $sReference )
		{
			return array_key_exists( $sReference, $this->_filereference ) ? $this->_filereference[ $sReference ] : false;
		}
	}

?>