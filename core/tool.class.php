<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	class CoreTool
	{
		public static function isPosted()
		{
			return array_key_exists( "REQUEST_METHOD", $_SERVER ) && $_SERVER[ "REQUEST_METHOD" ] === "POST";
		}

		/* Moved to Request object
		public static function param( $sKey, $mDefault=null, $sCollection=null )
		{
			if ( is_null( $sCollection ) )
				$sCollection = CoreTool::isPosted() ? "POST" : "GET";
			$sCollection = strToUpper( "_{$sCollection}" );
			if ( is_array( $$sCollection ) && array_key_exists( $sKey, $$sCollection ) )
				return $$sCollection[ $sKey ];
			return $mDefault;
		}
		*/

		public static function arrVal( $mKey, $mCollection, $mDefault=null )
		{
			if ( !is_array( $mCollection ) && is_array( $mKey ) )
				return CoreTool::arrVal( $mCollection, $mKey, $mDefault );
			return is_array( $mCollection ) && array_key_exists( $mKey, $mCollection ) ? $mCollection[ $mKey ] : $mDefault;
		}

		public static function arrayVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $mDefault );
		}
		
		public static function sesVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $_SESSION, $mDefault );
		}

		public static function sessionVal( $sKey, $mDefault=null )
		{
			return CoreTool::sesVal( $sKey, $mDefault );
		}

		public static function cookieVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $_COOKIE, $mDefault );
		}

		public static function serverVal( $sKey, $mDefault=null )
		{
			return CoreTool::arrVal( $sKey, $_SERVER, $mDefault );
		}

		public static function envVal( $sKey, $mDefault=null )
		{
			return getenv( $sKey );
		}

		public static function environmentVal( $sKey, $mDefault=null )
		{
			return CoreTool::envVal( $sKey, $mDefault );
		}



		public static function getIP( $mDefault="0.0.0.0" )
		{
			if ( $sReturn = CoreTool::envVal( "HTTP_CLIENT_IP" ) )
				return $sReturn;
			if ( $sReturn = CoreTool::envVal( "HTTP_X_FORWARDED_FOR" ) )
				return $sReturn;
			if ( $sReturn = CoreTool::envVal( "REMOTE_ADDR" ) )
				return $sReturn;
			return $mDefault;
		}



		public static function redirect( $sURL, $bDie=true )
		{
			if ( !headers_sent() )
				header( "Location: {$sURL}" );
			else
				print( "<meta http-equiv=\"refresh\" content=\"0;URL={$sURL}\"></meta>\n<script type=\"text/javascript\">location.href=\"{$sURL}\";</script>" );
			
			if ( $bDie )
				exit;
		}

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