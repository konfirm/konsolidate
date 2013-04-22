<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSystemFileCSV
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: System/File/CSV
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  CSV (Character Seperated Values) file support
	 *  @name    CoreSystemFileCSV
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreSystemFileCSV extends Konsolidate
	{
		/**
		 *  The filepointer resource for interactive get/put
		 *  @name    _filepointer
		 *  @type    resource
		 *  @access  protected
		 */
		protected $_filepointer;

		/**
		 *  The fieldname array, used to create proper and consistent objects when using the next method
		 *  @name    _fieldname
		 *  @type    array
		 *  @access  protected
		 */
		protected $_fieldname;


		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreSystemFileCSV( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->_fieldname = false;
			$this->delimiter = ",";
			$this->enclosure = "\"";
		}

		/**
		 *  Open a connection to a CSV file
		 *  @name    open
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @param   string mode [optional, default 'r']
		 *  @param   bool   use first row as field definition [optional, default true]
		 *  @return  bool  success
		 *  @syntax  string [object]->open( string filename [, string mode [, bool firstrowdefines ] ] );
		 */
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

		/**
		 *  Get CSV data from an opened file
		 *  @name    get
		 *  @type    method
		 *  @access  public
		 *  @param   mixed  int length [optional, default 4096 bytes], or string property
		 *  @return  mixed  data
		 *  @syntax  string [object]->get( [ int bytes ] );
		 *           mixed  [object]->get( string property );
		 *  @note    If a string property is provided, the property value is returned, otherwise the next line of the opened file is returned.
		 */
		public function get()
		{
			//  in order to achieve compatiblity with Konsolidates set method in strict mode, the params are read 'manually'
			$aArgument  = func_get_args();
			$mLength    = (bool) count( $aArgument ) ? array_shift( $aArgument ) : 4096;
			$mDelimiter = (bool) count( $aArgument ) ? array_shift( $aArgument ) : null;
			$sEnclosure = (bool) count( $aArgument ) ? array_shift( $aArgument ) : null;

			if ( is_integer( $mLength ) )
			{
				if ( empty( $mDelimiter ) )
					$mDelimiter = $this->delimiter;
				if ( empty( $sEnclosure ) )
					$sEnclosure = $this->enclosure;

				if ( $this->_filepointer !== false && !feof( $this->_filepointer ) )
					return fgetcsv( $this->_filepointer, $mLength, $mDelimiter, $sEnclosure );
				return false;
			}
			return parent::get( $mLength, $mDelimiter );
		}

		/**
		 *  Put a record into a CSV file
		 *  @name    put
		 *  @type    method
		 *  @access  public
		 *  @param   mixed  data
		 *  @param   string delimiter [optional, default class property 'delimiter' (default ',')]
		 *  @param   string enclosure [optional, default class property 'enclosure' (default '"')]
		 *  @param   bool   use first row as field definition [optional, default true]
		 *  @return  bool  success
		 *  @syntax  bool [object]->put( mixed data [, string delimiter [, string enclosure ] ] );
		 */
		public function put( $mData, $sDelimiter=null, $sEnclosure=null )
		{
			if ( empty( $sDelimiter ) )
				$sDelimiter = $this->delimiter;
			if ( empty( $sEnclosure ) )
				$sEnclosure = $this->enclosure;

			if ( $this->_filepointer !== false )
				return fputcsv( $this->_filepointer, is_array( $mData ) ? $mData : Array( $mData ), $sDelimiter, $sEnclosure );
			return false;
		}

		/**
		 *  Get the next record from the CSV file
		 *  @name    put
		 *  @type    method
		 *  @access  public
		 *  @param   int    length    [optional, default 4096]
		 *  @param   string delimiter [optional, default class property 'delimiter' (default ',')]
		 *  @param   string enclosure [optional, default class property 'enclosure' (default '"')]
		 *  @return  mixed  object (if fieldnames are known), array (if fieldnames are not known)
		 *  @syntax  mixed [object]->put( mixed data [, string delimiter [, string enclosure ] ] );
		 */
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

		/**
		 *  Close the connection to the CSV file
		 *  @name    close
		 *  @type    method
		 *  @access  public
		 *  @return  bool success
		 *  @syntax  bool [object]->close();
		 */
		public function close()
		{
			if ( $this->_filepointer !== false )
				return fclose( $this->_filepointer );
			return false;
		}
	}

?>