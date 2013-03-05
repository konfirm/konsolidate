<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRequestFile
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Request/File
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Provide easy access to uploaded files
	 *  @name    CoreRequestFile
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreRequestFile extends Konsolidate
	{
		/**
		 *  Move the uploaded file to target destination
		 *  @name    move
		 *  @type    method
		 *  @access  public
		 *  @param   string  destination
		 *  @param   bool    safe name [optional, default true]
		 *  @return  void
		 *  @syntax  bool CoreRequestFile->move( string destination [, bool safename ] )
		 */
		public function move( $sDestination, $bSafeName=true )
		{
			if ( is_uploaded_file( $this->tmp_name ) )
			{
				if ( is_dir( realpath( $sDestination ) ) ) //  only directory provided, appending filename to it
				{
					$sDestination = realpath( $sDestination ) . "/" . ( $bSafeName ? $this->sanitizedname : $this->name );
				}
				else if ( !strstr( basename( $sDestination ), "." ) ) //  assuming a dot in every filename... possible weird side effects?
				{
					mkdir( $sDestination, 0777, true );
					$sDestination = realpath( $sDestination ) . "/" . ( $bSafeName ? $this->sanitizedname : $this->name );
				}

				if ( move_uploaded_file( $this->tmp_name, $sDestination ) )
				{
					unset( $this->_property[ "tmp_name" ] );
					$this->location = $sDestination;
					return true;
				}
			}
			return false;
		}

		/**
		 *  Implicit set of properties
		 *  @name    __set
		 *  @type    method
		 *  @access  public
		 *  @param   string  property
		 *  @param   mixed   value
		 *  @return  void
		 *  @syntax  bool CoreRequestFile->{string property} = mixed value;
		 *  @note    some additional properties are automaticalaly added when certain properties are set.
		 *           - 'error' also sets 'message', a string containing a more helpful error message.
		 *           - 'name' also set 'sanitizedname', a cleaned up (suggested) name for the file.
		 *           - 'tmp_name' also sets 'md5', the MD5 checksum of the file.
		 *           - 'size' also sets 'filesize', a human readable representation of the file size.
		 */
		public function __set( $sProperty, $mValue )
		{
			if ( !empty( $mValue ) || $sProperty == "error" )
			{
				parent::__set( $sProperty, $mValue );
				switch( $sProperty )
				{
					case "error":
						$this->_property[ "message" ] = $this->_getErrorMessage( $mValue );
						$this->_property[ "success" ] = $mValue == UPLOAD_ERR_OK;
						break;
					case "name":
						$this->_property[ "sanitizedname" ] = preg_replace( "/[^a-zA-Z0-9\._-]+/", "_", $mValue );
						break;
					case "tmp_name":
						$this->_property[ "md5" ] = md5_file( $mValue );
						break;
					case "size":
						$this->_property[ "filesize" ] = $this->_bytesToLargestUnit( $mValue );
						break;
				}
			}
		}


		/**
		 *  Convert byte size into something more readable for humans
		 *  @name    _bytesToLargestUnit
		 *  @type    method
		 *  @access  protected
		 *  @param   number  bytes
		 *  @return  string  readable unit
		 *  @syntax  string CoreRequestFile->_bytesToLargestUnit( number bytes );
		 */
		protected function _bytesToLargestUnit( $nValue )
		{
			$sValue = "{$nValue} bytes";
			foreach ( Array( "KB", "MB", "GB", "TB", "PB" ) as $sUnit )
				if ( $nValue >= 1024 )
				{
					$nValue /= 1024;
					$sValue  = ( round( $nValue * 10 ) / 10 ) . $sUnit;
				}
			return $sValue;
		}

		/**
		 *  Resolve the UPLOAD_ERR_XX constant into its text representation (clarifying meaning)
		 *  @name    _getErrorMessage
		 *  @type    method
		 *  @access  protected
		 *  @param   int     error number
		 *  @return  string  error message
		 *  @syntax  string CoreRequestFile->_getErrorMessage( int error );
		 */
		protected function _getErrorMessage( $nError )
		{
			switch( (int) $nError )
			{
				case UPLOAD_ERR_OK:         return "No error";
				case UPLOAD_ERR_INI_SIZE:   return "The file exceeds PHP maximum file size";
				case UPLOAD_ERR_FORM_SIZE:  return "The file exceeds Form maximum file size";
				case UPLOAD_ERR_PARTIAL:    return "The file was only partially uploaded";
				case UPLOAD_ERR_NO_FILE:    return "No file was uploaded";
				case UPLOAD_ERR_NO_TMP_DIR: return "Missing temporary upload location";
				case UPLOAD_ERR_CANT_WRITE: return "Failed to write file to disk";
				case UPLOAD_ERR_EXTENSION:  return "File upload stopped by extension";
				default:                    return "Unknown error";
			};
		}
	}

?>