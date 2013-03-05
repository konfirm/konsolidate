<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPCStatus
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC/Status
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Status reply (automated RPC replies)
	 *  @name    CoreRPCStatus
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreRPCStatus extends Konsolidate
	{
		protected $_version = "1.0.8";

		/**
		 *  Encapsulate a value in a CDATA string
		 *  @name    _cdata
		 *  @type    method
		 *  @access  protected
		 *  @param   string value
		 *  @return  string
		 *  @syntax  Object->_cdata( string value );
		 */
		protected function _cdata( $sValue )
		{
			if ( !empty( $sValue ) )
				return "<![CDATA[{$sValue}]]>";
			return "";
		}

		/**
		 *  Write out an array into a multi-node XML string
		 *  @name    _flattenArray
		 *  @type    method
		 *  @access  protected
		 *  @param   array source
		 *  @return  string
		 *  @syntax  Object->_flattenArray( array source );
		 */
		protected function _flattenArray( $aSource, $sNumericKey="item" )
		{
			$sReturn = "";
			foreach( $aSource as $sKey=>$mValue )
			{
				if ( is_numeric( $sKey ) )
					$sKey = $sNumericKey;
				$sReturn .= "<{$sKey}>" . ( is_array( $mValue ) ? $this->_flattenArray( $mValue, $sKey ) : $this->_cdata( $mValue ) ) . "</{$sKey}>";
			}
			return "{$sReturn}";
		}
	
		/**
		 *  fetch the status reply XML
		 *  @name    fetch
		 *  @type    method
		 *  @access  public
		 *  @param   bool   error (true if reply should indicate an error)
		 *  @param   string message [optional]
		 *  @param   mixed  content (string or array containing additional info to send) [optional]
		 *  @return  string
		 *  @syntax  Object->fetch( bool error [, string message [, mixed content ] ] );
		 */
		public function fetch( $bError, $sMessage="", $mContent="" )
		{
			if ( !headers_sent() )
				header( "X-Status: " . get_class( $this ) . "/{$this->_version}" );
			$sContent = is_array( $mContent ) ? $this->_flattenArray( $mContent ) : $this->_cdata( $mContent );

			return "<reply status=\"" . ( $bError ? 'ERROR' : 'OK' ) . "\">\n" .
				   ( !empty( $sMessage ) ? "\t<message>" . $this->_cdata( $sMessage ) . "</message>\n" : "" ) .
				   ( !empty( $sContent ) ? "\t<content>{$sContent}</content>\n" : "" ) .
				   "</reply>";
		}

		/**
		 *  send (display) the status reply XML
		 *  @name    send
		 *  @type    method
		 *  @access  public
		 *  @param   bool   error (true if reply should indicate an error)
		 *  @param   string message [optional]
		 *  @param   mixed  content (string or array containing additional info to send) [optional]
		 *  @return  bool
		 *  @syntax  Object->send( bool error [, string message [, mixed content ] ] );
		 */
		public function send( $bError=true, $sMessage="", $mContent="" )
		{
			if ( !headers_sent() )
				header( "Content-type: text/xml" );
			return print( $this->fetch( $bError, $sMessage, $mContent ) );
		}
	}

?>