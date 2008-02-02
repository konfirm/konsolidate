<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSystemFile
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: System/File
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  File IO, either use an instance to put/get or read/write an entire file at once
	 *  @name    CoreSystemFile
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.net>
	 */
	class CoreSystemFile extends Konsolidate
	{
		private $_filepointer;

		/**
		 *  Read an entire file and return the contents
		 *  @name    read
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @returns string file contents (bool false on error)
		 *  @syntax  bool [object]->read( string filename )
		 */
		public static function read( $sFile )
		{
			if ( file_exists( $sFile ) && is_readable( $sFile ) )
				return file_get_contents( $sFile );
			return false;
		}

		/**
		 *  Write data to a file
		 *  @name    write
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @param   string data
		 *  @returns bool success
		 *  @syntax  bool [object]->write( string filename, string data )
		 */
		public static function write( $sFile, $sData )
		{
			return file_put_contents( $sFile, $sData );
		}

		/**
		 *  Set the mode of a file
		 *  @name    mode
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @param   number mode
		 *  @returns bool success
		 *  @syntax  bool [object]->mode( string file, number mode )
		 *  @note    mode needs be an octal number, eg 0777
		 */
		public static function mode( $sFile, $nMode )
		{
			return chmod( $sFile, $nMode );
		}

		/**
		 *  unlink (delete) a file
		 *  @name    unlink
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @returns bool success
		 *  @syntax  bool [object]->unlink( string filename )
		 */
		public static function unlink( $sFile )
		{
			return unlink( $sFile );
		}

		/**
		 *  delete a file
		 *  @name    delete
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @returns bool success
		 *  @syntax  bool [object]->delete( string filename )
		 *  @see     unlink
		 *  @note    an alias method for unlink
		 */
		public static function delete( $sFile )
		{
			return $this->unlink( $sFile );
		}

		/**
		 *  rename a file
		 *  @name    rename
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @param   string newfilename
		 *  @param   bool   force (optional, default false)
		 *  @returns bool success
		 *  @syntax  bool [object]->rename( string filename, string newfilename [, bool force ] )
		 */
		public static function rename( $sFile, $sNewFile, $bForce=false )
		{
			if ( file_exists( $sFile ) && ( $bForce || ( !$bForce && !file_exists( $sNewFile ) ) ) )
				return rename( $sFile, $sNewFile );
			return false;
		}





		/**
		 *  Open a file for interaction
		 *  @name    open
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @param   string mode (optional, default "r" (read access))
		 *  @returns bool success
		 *  @syntax  bool [object]->open( string filename [, string mode ] );
		 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
		 *           you can best create a unique instance to use this method, obtained through: [KonsolidateObject]->instance( "/System/File" );
		 */
		public function open( $sFile, $sMode="r" )
		{
			$this->_filepointer = fopen( $sFile, $sMode );
			return $this->_filepointer !== false;
		}

		/**
		 *  Get data from an opened file
		 *  @name    get
		 *  @type    method
		 *  @access  public
		 *  @param   mixed  int length [optional, default 4096 bytes], or string property
		 *  @returns mixed  data
		 *  @syntax  string [object]->get( [ int bytes ] );
		 *           mixed  [object]->get( string property );
		 *  @note    If a string property is provided, the property value is returned, otherwise the next line of the opened file is returned.
		 *           Warning: Since you cannot know if your code is the only code currently accessing any file
		 *           you can best create a unique instance to use this method, obtained through: [KonsolidateObject]->instance( "/System/File" );
		 */
		public function get()
		{
			//  in order to achieve compatiblity with Konsolidates set method in strict mode, the params are read 'manually'
			$aArgument  = func_get_args();
			$mLength    = (bool) count( $aArgument ) ? array_shift( $aArgument ) : 4096;
			$mDefault   = (bool) count( $aArgument ) ? array_shift( $aArgument ) : null;

			if ( is_integer( $mLength ) )
			{
				if ( $this->_filepointer !== false && !feof( $this->_filepointer ) )
					return fgets( $this->_filepointer, $mLength );
				return false;
			}
			return parent::get( $mLength, $mDefault );
		}

		/**
		 *  Put data into an opened file
		 *  @name    put
		 *  @type    method
		 *  @access  public
		 *  @param   string data
		 *  @returns bool success
		 *  @syntax  bool [object]->put( string data );
		 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
		 *           you can best create a unique instance to use this method, obtained through: [KonsolidateObject]->instance( "/System/File" );
		 */
		public function put( $sData )
		{
			if ( $this->_filepointer !== false )
				return fputs( $this->_filepointer, $sData, strlen( $sData ) );
			return false;
		}

		/**
		 *  Get data from an opened file
		 *  @name    next
		 *  @type    method
		 *  @access  public
		 *  @returns string data
		 *  @syntax  string [object]->next();
		 *  @see     get
		 *  @note    Alias of get, relying on the default amount of bytes
		 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
		 *           you can best create a unique instance to use this method, obtained through: [KonsolidateObject]->instance( "/System/File" );
		 */
		public function next()
		{
			return $this->get();
		}

		/**
		 *  Close the opened file
		 *  @name    close
		 *  @type    method
		 *  @access  public
		 *  @returns bool success
		 *  @syntax  bool [object]->close
		 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
		 *           you can best create a unique instance to use this method, obtained through: [KonsolidateObject]->instance( "/System/File" );
		 */
		public function close()
		{
			if ( $this->_filepointer !== false )
				return fclose( $this->_filepointer );
			return false;
		}

		/**
		 *  Get the filepointer of the opened file
		 *  @name    getFilePointer
		 *  @type    method
		 *  @access  public
		 *  @returns resource filepointer
		 *  @syntax  resource [object]->getFilePointer()
		 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
		 *           you can best create a unique instance to use this method, obtained through: [KonsolidateObject]->instance( "/System/File" );
		 */
		public function getFilePointer()
		{
			return $this->_filepointer;
		}



		/**
		 *  Magic __destruct, closes open filepointers
		 *  @name    __destruct
		 *  @type    method
		 *  @access  public
		 *  @returns void
		 */
		public function __desctruct()
		{
			if ( !is_null( $this->_filepointer ) && $this->_filepointer !== false )
				$this->close();
		}
	}

?>