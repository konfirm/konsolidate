<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
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
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreLog extends Konsolidate
	{
		/**
		 *  The verbositylevel you wish to log
		 *  0 (Critical), 1 (Severe), 2 (Warning), 3 (Info), 4 (Debug)
		 *  @name    _verbositylevel
		 *  @type    int
		 *  @access  protected
		 */
		protected $_verbositylevel;

		/**
		 *  The logfile to which to write the log data
		 *  @name    _logfile
		 *  @type    string
		 *  @access  protected
		 */
		protected $_logfile;


		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreLog( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_logfile = $this->get( "/Config/konsolidate/log", ini_get( "error_log" ) );
			$this->setVerbosity( $this->get( "/Config/konsolidate/loglevel" ) );
		}

		/**
		 *  set the verbosity level of Konsolidate
		 *  @name    setVerbosity
		 *  @type    method
		 *  @access  public
		 *  @param   int level (default matching error_reporting ini directive)
		 *  @return  void
		 *  @syntax  void CoreLog->setVerbosity( [int level] )
		 */
		public function setVerbosity( $nLevel=null )
		{
			if ( $nLevel === null )
				$nLevel = $this->_determineVerbosity();
			$this->_verbositylevel = $nLevel;
		}

		/**
		 *  Output messages according to preferences in the php.ini (override in Konsolidate Config)
		 *  @name    message
		 *  @type    method
		 *  @access  public
		 *  @param   string message
		 *  @param   int    level
		 *  @return  bool
		 *  @syntax  bool CoreLog->message( string message [, int level ] )
		 *  @note    Configuration options: display_errors (Config/Log/displayerrors), log_errors (Config/Log/logerrors)
		 */
		public function message( $sMessage, $nVerbosity=3 )
		{
			if ( $nVerbosity <= $this->_verbositylevel )
			{
				if ( (bool) ini_get( "display_errors" ) )
				{
					if ( (bool) ini_get( "html_errors" ) )
						print $this->_formatMessage( $sMessage, $nVerbosity, true );
					else
						print $this->_formatMessage( $sMessage, $nVerbosity ) . "\n";
				}
				if ( (bool) ini_get( "log_errors" ) )
				{
					$this->write( $sMessage, $nVerbosity );
				}
			}
		}

		/**
		 *  write a (formatted) line to the log file
		 *  @name    write
		 *  @type    method
		 *  @access  public
		 *  @param   string message
		 *  @param   int    level
		 *  @return  bool
		 *  @syntax  bool CoreLog->write( string message [, int level ] )
		 *  @note    if there's any reason the message cannot be written to the logfile, the message is written into the default error.log
		 */
		public function write( $sMessage, $nVerbosity=3 )
		{
			if ( $nVerbosity <= $this->_verbositylevel )
			{
				if ( !error_log( $this->_formatMessage( $sMessage, $nVerbosity ) . "\n", 0, $this->_logfile ) )
				{
					error_log( $sMessage );
					return false;
				}
				return true;
			}
			return false;
		}

		/**
		 *  Translate the verbosity level int to a more readable string
		 *  @name    _translate
		 *  @type    method
		 *  @access  protected
		 *  @param   int    level
		 *  @param   bool   uppercase (default true)
		 *  @return  bool
		 *  @syntax  bool CoreLog->_translate( int level [, bool uppercase ] )
		 */
		protected function _translate( $nVerbosity, $bUpperCase=true )
		{
			switch( (int) $nVerbosity )
			{
		 		case 0:  $sReturn = "Critical"; break;
		 		case 1:  $sReturn = "Severe";   break;
		 		case 2:  $sReturn = "Warning";  break;
		 		case 3:  $sReturn = "Info";     break;
		 		case 4:  $sReturn = "Debug";    break;
		 		default: $sReturn = "Unknown";
			}
			return $bUpperCase ? strtoupper( $sReturn ) : $sReturn;
		}

		/**
		 *  Determine the verbosity level based on the error_reporting directive
		 *  @name    _determineVerbosity
		 *  @type    method
		 *  @access  protected
		 *  @return  int  level
		 *  @syntax  bool CoreLog->_determineVerbosity()
		 */
		protected function _determineVerbosity()
		{
			$nReporting = ini_get( "error_reporting" );
			if ( E_STRICT & $nReporting )
				return 4; //  Debug - Very strict, al lot of information
			else if ( E_ALL & $nReporting )
				return 3; //  All - A lot of information
			else if ( E_NOTICE & $nReporting )
				return 2; //  Info - Much information
			else if ( E_WARNING & $nReporting )
				return 1; // Only warnings - less informative
			else if ( E_ERROR & $nReporting )
				return 0; // Only critial information - least informative

			return 3;
		}

		/**
		 *  Format the log message
		 *  @name    _formatMessage
		 *  @type    method
		 *  @access  protected
		 *  @param   string message
		 *  @param   int    level
		 *  @param   bool   html
		 *  @return  int  level
		 *  @syntax  bool CoreLog->_formatMessage( string message, int level [, bool html ] )
		 */
		protected function _formatMessage( $sMessage, $nVerbosity, $bHTML=false )
		{
			if ( $bHTML )
				return "<div class='konsolidate_error konsolidate_" . strToLower( $this->_translate( $nVerbosity ) ) . "'><span class='konsolidate_time'>" . date( "r" ) . "</span> <span class='konsolidate_level'>" . $this->_translate( $nVerbosity ) . "</span> <span class='konsolidate_script'>{$_SERVER[ "SCRIPT_NAME" ]}</span> <span class='konsolidate_message'>{$sMessage}</span></div>";
			return "[" . $this->_translate( $nVerbosity ) . "] - {$_SERVER[ "SCRIPT_NAME" ]}]: {$sMessage}";
		}
	}

?>