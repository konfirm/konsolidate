<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSystemFileMIME
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: System/File/MIME
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */

	/**
	 *  MIME Detection based on available (compiled) capabilities
	 *  @name    CoreSystemFileMIME
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreSystemFileMIME extends Konsolidate
	{
		/**
		 *  The method to use to determine the MIME type
		 *  @name    _executeMethod
		 *  @type    bool
		 *  @access  protected
		 *  @note    the execution method is determined by verifying the host's capabilities
		 */
		protected $_executeMethod;

		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreSystemFileMIME( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			if ( function_exists( "finfo_open" ) )
				$this->_executeMethod = "_determineTypeByFileInfo";
			else if ( function_exists( "mime_content_type" ) )
				$this->_executeMethod = "_determineTypeByMimeContentType";
			else
				$this->_executeMethod = "_determineTypeByExtension";
		}

		/**
		 *  Try to determine the MIME type of a file
		 *  @name    getType
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @return  string MIME
		 *  @syntax  string CoreSystemFileMIME->getType( string filename )
		 */
		public function getType( $sFile )
		{
			return $this->{$this->_executeMethod}( $sFile );
		}

		/**
		 *  Try to determine the MIME type using 'mime_content_type'
		 *  @name    _determineTypeByMimeContentType
		 *  @type    method
		 *  @access  protected
		 *  @param   string filename
		 *  @return  string MIME
		 *  @syntax  string CoreSystemFileMIME->_determineTypeByMimeContentType( string filename )
		 */
		protected function _determineTypeByMimeContentType( $sFile )
		{
			return mime_content_type( $sFile );
		}

		/**
		 *  Try to determine the MIME type using the fileinfo extension
		 *  @name    _determineTypeByFileInfo
		 *  @type    method
		 *  @access  protected
		 *  @param   string filename
		 *  @return  string MIME
		 *  @syntax  string CoreSystemFileMIME->_determineTypeByFileInfo( string filename )
		 */
		protected function _determineTypeByFileInfo( $sFile )
		{
			$finfo    = finfo_open( FILEINFO_MIME, $this->get( "/Config/finfo_open/magic_file", null )  );
			$mimetype = finfo_file( $finfo, $sFile );
			finfo_close( $finfo );
			return $mimetype;
		}

		/**
		 *  Try to determine the MIME type using an (somewhat) educated guess based on the file extension
		 *  @name    _determineTypeByExtension
		 *  @type    method
		 *  @access  protected
		 *  @param   string filename
		 *  @return  string MIME
		 *  @syntax  string CoreSystemFileMIME->_determineTypeByExtension( string filename )
		 */
		protected function _determineTypeByExtension( $sFile )
		{
			$aFilePart  = explode( ".", $sFile );
		    $sExtension = array_pop( $aFilePart );
			switch( strToLower( $sExtension ) )
			{
				//  Common image types
				case "ai":    case "eps":
				case "ps":
					return "application/postscript";
				case "bmp":
					return "image/bmp";
				case "gif":
					return "image/gif";
				case "jpe":   case "jpg":
				case "jpeg":
					return "image/jpeg";

				//  Common audio types
				case "aifc":  case "aiff":
				case "aif":
					return "audio/aiff";
				case "mid":   case "midi":
					return "audio/midi";
				case "mod":
					return "audio/mod";
				case "mp2":
					return "audio/mpeg";
				case "mp3":
					return "audio/mpeg3";
				case "wav":
					return "audio/wav";

				//  Common video types
				case "avi":
					return "video/avi";
				case "mov":  case "qt":
					return "video/quicktime";
				case "mpe":  case "mpg":
				case "mpeg":
					return "video/mpeg";

				//  Common text types
				case "css":
					return "text/css";
				case "htm":   case "html":
				case "htmls": case "htx":
					return "text/html";
				case "conf":  case "log":
				case "text":  case "txt":
				case "php":
					return "text/plain";
				case "js":
					return "application/x-javascript";
				case "rtf":
					return "text/richtext";

				//  Other commonly used types
				case "dcr":
					return "application/x-director";
				case "doc":  case "dot":
				case "word":
					return "application/msword";
				case "gz":   case "gzip":
					return "application/x-gzip";
				case "latex":
					return "application/x-latex";
				case "pdf":
					return "application/pdf";
				case "pps":  case "ppt":
					return "application/mspowerpoint";
				case "swf":
					return "application/x-shockwave-flash";
				case "wp":   case "wp5":
				case "wp6":  case "wpd":
					return "application/wordperfect";
				case "xls":
					return "application/excel";
				case "zip":
					return "zip	application/zip";
				default:
					return "application/octet-stream";
			}
		}
	}

?>