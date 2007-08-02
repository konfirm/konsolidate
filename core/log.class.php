<?php

	if ( !defined( "KONSOLIDATE_LOG" ) )
		define( "KONSOLIDATE_LOG", "/tmp/konsolidate.log" );

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreLog
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Log
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 43 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-06-02 20:41:54 +0200 (Sat, 02 Jun 2007) $
	 *               \___\/           
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

		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->_logfile = $this->instance( "/System/File" );
			$this->_logfile->open( realPath( KONSOLIDATE_LOG ), "a" );

			$this->setVerbosity();
		}

		public function __destruct()
		{
			if ( $this->_logfile )
				$this->_logfile->close();
		}

		public function setVerbosity( $nLevel=3 )
		{
			$this->_verbositylevel = $nLevel;
		}

		public function write( $sMessage, $nVerbosity=3 )
		{
			if ( $nVerbosity <= $this->_verbositylevel )
			{
				if ( !$this->_logfile->put( "[" . date( "Y.m.d H:i" ) . " {$_SERVER[ "SCRIPT_NAME" ]} " . "]\t{$sMessage}\n" ) )
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