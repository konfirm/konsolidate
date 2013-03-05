<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreTool
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Tool (use static, always available when Konsolidate is)
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Abstracted functionality often used in- and outside Konsolidate
	 *  @name    CoreTool
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 *  @note    This class is always available as soon as Konsolidate (and its extends) is instanced (and the Core tier is available)
	 */
	class CoreTool extends Konsolidate
	{
		/**
		 *  Determine whether the script is access from a POST request
		 *  @name    isPosted
		 *  @type    method
		 *  @access  public
		 *  @return  bool
		 *  @syntax  bool CoreTool::isPosted();
		 */
		public static function isPosted()
		{
			return array_key_exists( "REQUEST_METHOD", $_SERVER ) && $_SERVER[ "REQUEST_METHOD" ] === "POST";
		}

		/**
		 *  Get a value from an array
		 *  @name    arrVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   array  collection
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::arrVal( string key, array collection [, mixed default ] );
		 *  @note    you can swap key and collection (Haystack - Needle)
		 */
		public static function arrVal( $mKey, $mCollection, $mDefault=null )
		{
			if ( !is_array( $mCollection ) && is_array( $mKey ) )
				return CoreTool::arrVal( $mCollection, $mKey, $mDefault );
			return is_array( $mCollection ) && array_key_exists( $mKey, $mCollection ) ? $mCollection[ $mKey ] : $mDefault;
		}

		/**
		 *  Alias for arrVal
		 *  @name    arrayVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   array  collection
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::arrayVal( string key, array collection [, mixed default ] );
		 *  @see     arrVal
		 */
		public static function arrayVal( $sKey, $aCollection, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $aCollection, $mDefault );
		}
		
		/**
		 *  Get a value from a PHP Session (not a CoreSession!)
		 *  @name    sesVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::sesVal( string key [, mixed default ] );
		 *  @note    This method works with PHP's built in sessions (_SESSION global), not CoreSession!
		 */
		public static function sesVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $_SESSION, $mDefault );
		}

		/**
		 *  Alias for sesVal
		 *  @name    sessionVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::sessionVal( string key [, mixed default ] );
		 *  @see     sesVal
		 */
		public static function sessionVal( $sKey, $mDefault=null )
		{
			return CoreTool::sesVal( $sKey, $mDefault );
		}

		/**
		 *  Get a value from a PHP Cookie
		 *  @name    cookieVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::cookieVal( string key [, mixed default ] );
		 */
		public static function cookieVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $_COOKIE, $mDefault );
		}

		/**
		 *  Get a value from the _SERVER global
		 *  @name    serverVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::serverVal( string key [, mixed default ] );
		 */
		public static function serverVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $_SERVER, $mDefault );
		}

		/**
		 *  Get a value from the environment
		 *  @name    envVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::envVal( string key [, mixed default ] );
		 */
		public static function envVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $_ENV, $mDefault );
		}

		/**
		 *  Alias for envVal
		 *  @name    environmentVal
		 *  @type    method
		 *  @access  public
		 *  @param   string key
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::environmentVal( string key [, mixed default ] );
		 *  @see     envVal
		 */
		public static function environmentVal( $sKey, $mDefault=null )
		{
			return CoreTool::envVal( $sKey, $mDefault );
		}



		/**
		 *  Get the User/Visitor IP address
		 *  @name    getIP
		 *  @type    method
		 *  @access  public
		 *  @param   mixed  default [optional, default null]
		 *  @return  mixed
		 *  @syntax  mixed CoreTool::getIP( [ mixed default ] );
		 */
		public static function getIP( $mDefault="0.0.0.0" )
		{
			if ( $sReturn = CoreTool::serverVal( "HTTP_CLIENT_IP" ) )
				return $sReturn;
			if ( $sReturn = CoreTool::serverVal( "HTTP_X_FORWARDED_FOR" ) )
				return $sReturn;
			if ( $sReturn = CoreTool::serverVal( "REMOTE_ADDR" ) )
				return $sReturn;
			return $mDefault;
		}



		/**
		 *  Redirect the browser to another location
		 *  @name    redirect
		 *  @type    method
		 *  @access  public
		 *  @param   string URL
		 *  @param   bool   stopscript
		 *  @return  void
		 *  @syntax  void CoreTool::redirect( string URL [, bool stopscript ] );
		 *  @note    this method sends out both a META header and a JavaScript in case headers were already sent
		 */
		public static function redirect( $sURL, $bDie=true )
		{
			if ( !headers_sent() )
				header( "Location: {$sURL}" );
			else
				print( "<meta http-equiv=\"refresh\" content=\"0;URL={$sURL}\"></meta>\n<script type=\"text/javascript\">location.href=\"{$sURL}\";</script>" );
			
			if ( $bDie )
				exit;
		}

		/**
		 *  Expire page by sending out a variaty of expiration headers
		 *  @name    expirePage
		 *  @type    method
		 *  @access  public
		 *  @param   int    timestamp [optional, default 946702800]
		 *  @return  bool
		 *  @syntax  void CoreTool::expirePage( [ int timestamp ] );
		 */
		public static function expirePage( $nTimestamp=null )
		{
			if ( !headers_sent() )
			{
				if ( is_null( $nTimestamp ) )
					$nTimestamp = 946702800;
				header( "Cache-Control: no-cache, must-revalidate, private" );
				header( "Pragma: no-cache" );
				header( "Expires: " . gmdate( "D, d M Y H:i:s", $nTimestamp ) . " GMT" );
				header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
				return true;
			}
			return false;
		}
	}

?>
