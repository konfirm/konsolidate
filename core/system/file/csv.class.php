<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSystemFileCSV
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: System/File/CSV
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 9 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-08-18 11:33:08 +0200 (Sat, 18 Aug 2007) $
	 *               \___\/           
	 */


	/**
	 *  CSV (Character Seperated Values) file support
	 *  @name    CoreSystemFileCSV
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreSystemFileCSV extends Konsolidate
	{
		private $_filepointer;
		private $_fieldname;
		public $delimiter;
		public $enclosure;

		function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->_fieldname = false;
			$this->delimiter = ",";
			$this->enclosure = "\"";
		}

		public function open( $sFile, $sMode="r", $bFirstRowDefines=true )
		{
			if ( $this->call( "../open", $sFile, $sMode ) )
			{
				$this->_filepointer = $this->call( "../getFilePointer" );
				$this->_fieldname   = $bFirstRowDefines;
				return true;
			}
			return false;
		}

		public function get( $nLength=4096, $sDelimiter=null, $sEnclosure=null )
		{
			if ( empty( $sDelimiter ) )
				$sDelimiter = $this->delimiter;
			if ( empty( $sEnclosure ) )
				$sEnclosure = $this->enclosure;

			if ( $this->_filepointer !== false && !feof( $this->_filepointer ) )
				return fgetcsv( $this->_filepointer, $nLength, $sDelimiter, $sEnclosure );
			return false;
		}

		public function put( $mData, $sDelimiter=null, $sEnclosure=null )
		{
			if ( empty( $sDelimiter ) )
				$sDelimiter = $this->delimiter;
			if ( empty( $sEnclosure ) )
				$sEnclosure = $this->enclosure;

			if ( $this->_filepointer !== false )
				if ( is_array( $mData ) )
					return fputcsv( $this->_filepointer, $mData, $sDelimiter, $sEnclosure );
				else
					return fputcsv( $this->_filepointer, Array( $mData ), $sDelimiter, $sEnclosure );
			return false;
		}

		public function next( $nLength=4096, $sDelimiter=null, $sEncosure=null )
		{
			if ( empty( $sDelimiter ) )
				$sDelimiter = $this->delimiter;
			if ( empty( $sEnclosure ) )
				$sEnclosure = $this->enclosure;

			if ( $this->_fieldname === true )
				$this->_fieldname = $this->get( $nLength, $sDelimiter, $sEnclosure );

			$aResult = $this->get( $nLength, $sDelimiter, $sEnclosure );
			if ( $aResult !== false )
			{
				if ( is_array( $this->_fieldname ) )
				{
					$oReturn = (object) null;
					for ( $i = 0; $i < count( $this->_fieldname ); ++$i )
						$oReturn->{$this->_fieldname[ $i ]} = $aResult[ $i ];
					return $oReturn;
				}
				return $aResult;
			}
			return false;
		}

		public function close()
		{
			if ( $this->_filepointer !== false )
				return fclose( $this->_filepointer );
			return false;
		}
	}

?>