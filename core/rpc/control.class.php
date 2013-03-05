<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPCControl
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC/Control
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Standard processor for use with RPC-Controller Modules
	 *  @name    CoreRPCControl
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreRPCControl extends Konsolidate
	{
		/**
		 *  Send/assign feedback based on preferred format
		 *  @name    _feedback
		 *  @type    method
		 *  @access  protected
		 *  @param   bool   error during processing (optional, default true, we assume the worst)
		 *  @param   string message to display (optional, default empty)
		 *  @param   mixed  content, either a string with additional message, or an array containing arrays, strings or numbers (optional, default empty)
		 *  @return  void
		 *  @syntax  void CoreRPCControl->_feedback()
		 */
		protected function _feedback( $bError=true, $sMessage="", $mContent="" )
		{
			if ( $this->get( "/Request/_format" ) == "xml" )
			{
				if ( $this->call( "/RPC/Status/send", $bError, $sMessage, $mContent ) )
					exit;
			}
			else
			{
				echo "emulating normal POST/GET, dumping vars instead of assigning to template<br />";
				echo " - error: "; var_dump( $bError ); echo "<br />";
				echo " - message: "; var_dump( $sMessage ); echo "<br />";
				echo " - content: "; var_dump( $mContent ); echo "<br />";
			}
		}

		/**
		 *  Process the RPC request
		 *  @name    process
		 *  @type    method
		 *  @access  public
		 *  @param   string command
		 *  @return  void
		 *  @syntax  void CoreRPCControl->process( string command )
		 */
		function process( $sCommand )
		{
			$nMethodStart = strrpos( $sCommand, $this->_objectseparator );
			$sModule      = substr( $sCommand, 0, $nMethodStart );
			$sMethod      = substr( $sCommand, $nMethodStart + 1 );

			$oProcessor = $this->get( $sModule );
			if ( is_object( $oProcessor ) && method_exists( $oProcessor, $sMethod ) )
			{
				$oProcessor->$sMethod();
				return $this->_feedback( !$oProcessor->getStatus(), $oProcessor->getMessage(), $oProcessor->getContent() );
			}
			return $this->_feedback();
		}
	}

?>
