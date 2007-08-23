<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	class CoreSystemFile extends Konsolidate
	{
		private $_filepointer;

		public function __desctruct()
		{
			if ( !is_null( $this->_filepointer ) && $this->_filepointer !== false )
				$this->close();
		}

		

		public static function read( $sFile )
		{
			if ( file_exists( $sFile ) && is_readable( $sFile ) )
				return file_get_contents( $sFile );
			return false;
		}

		public static function write( $sFile, $sData )
		{
			return file_put_contents( $sFile, $sData );
		}

		public static function mode( $sFile, $nMode )
		{
			return chmod( $sFile, $nMode );
		}

		public static function unlink( $sFile )
		{
			return unlink( $sFile );
		}



		public function open( $sFile, $sMode="r" )
		{
			$this->_filepointer = fopen( $sFile, $sMode );
			return $this->_filepointer !== false;
		}

		public function get( $nLength=4096 )
		{
			if ( $this->_filepointer !== false && !feof( $this->_filepointer ) )
				return fgets( $this->_filepointer, $nLength );
			return false;
		}

		public function put( $sData )
		{
			if ( $this->_filepointer !== false )
				return fputs( $this->_filepointer, $sData, strlen( $sData ) );
			return false;
		}

		public function next()
		{
			return $this->get();
		}

		public function close()
		{
			if ( $this->_filepointer !== false )
				return fclose( $this->_filepointer );
			return false;
		}

		public function getFilePointer()
		{
			return $this->_filepointer;
		}
	}

?>