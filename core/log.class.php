<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreLog
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Log
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Basic verbosity based logging
	 *  @name    CoreLog
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreLog extends Konsolidate
	{
		/**
		 *  The level of verbosity to apply to log messages
		 *  0	- Critical
		 *  1	- Severe
		 *  2   - Warning
		 *  3   - Info
		 *  4   - Debug (Should not occur in any of the stable Core tier modules!)
		 */
		protected $_verbositylevel;
		private   $_logfile;

		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreLog( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$sFilename = $this->get( "/Config/konsolidate/log", "/tmp/konsolidate.log" );

			$this->_logfile = $this->instance( "/System/File" );
			$this->_logfile->open( realPath( $sFilename ), "a" );

			$this->setVerbosity();
		}

		/**
		 *  magic __destruct, close connection/filepointer to the logfile
		 *  @name    __destruct
		 *  @type    method
		 *  @access  public
		 *  @returns void
		 *  @syntax  void CoreLog->__destruct()
		 */
		public function __destruct()
		{
			if ( $this->_logfile )
				$this->_logfile->close();
		}

		/**
		 *  set the verbosity level of Konsolidate
		 *  @name    setVerbosity
		 *  @type    method
		 *  @access  public
		 *  @param   int level
		 *  @returns void
		 *  @syntax  void CoreLog->setVerbosity( int level )
		 */
		public function setVerbosity( $nLevel=3 )
		{
			$this->_verbositylevel = $nLevel;
		}

		/**
		 *  write a (formatted) line to the log file
		 *  @name    write
		 *  @type    method
		 *  @access  public
		 *  @param   string message
		 *  @param   int    level
		 *  @returns bool
		 *  @syntax  bool CoreLog->write( string message [, int level ] )
		 *  @note    if there's any reason the message cannot be written to the logfile, the message is written into the default error.log
		 */
		public function write( $sMessage, $nVerbosity=3 )
		{
			if ( $nVerbosity <= $this->_verbositylevel )
			{
				if ( !$this->_logfile->put( "[" . date( "Y.m.d H:i" ) . " - {$nVerbosity} - {$_SERVER[ "SCRIPT_NAME" ]}]\t\t{$sMessage}\n" ) )
				{
					error_log( $sMessage );
					return false;
				}
				return true;
			}
			return false;
		}
	}

?>