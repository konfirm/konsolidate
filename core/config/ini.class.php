<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreConfigINI
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Config/INI
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */
	class CoreConfigINI extends Konsolidate
	{
		/**
		 *  Load and parse an inifile
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @param   string  inifile
		 *  @returns array
		 *  @syntax  Object->load( string inifile )
		 */
		public function load( $sFile, $sSegment=null )
		{
			$aConfig = parse_ini_file( $sFile, true );
			$aReturn = Array();
			foreach( $aConfig as $sPrefix=>$mValue )
			{
				if ( is_array( $mValue ) )
				{
					$aReturn[ $sPrefix ] = array_key_exists( "default", $aReturn ) ? $aReturn[ "default" ] : Array();
					foreach( $mValue as $sKey=>$sValue )
					{
						$aReturn[ $sPrefix ][ $sKey ] = $sValue;
						$this->set( "/Config/{$sPrefix}/$sKey", $sValue );
					}
				}
				else
				{
					$aReturn[ $sPrefix ] = $mValue;
				}
			}

			if ( !is_null( $sSegment ) && array_key_exists( $sSegment, $aReturn ) )
				return $aReturn[ $sSegment ];

			return $aReturn;
		}

		/**
		 *  Load and parse an inifile and create defines
		 *  @name    loadAndDefine
		 *  @type    method
		 *  @access  public
		 *  @param   string  inifile
		 *  @returns void
		 *  @syntax  Object->loadAndDefine( string inifile )
		 *  @note    defines are formatted like [SECTION]_[KEY]=[VALUE]
		 */
		public function loadAndDefine( $sFile, $sSegment=null )
		{
			$aConfig = $this->load( $sFile, $sSegment );
			foreach( $aConfig as $sPrefix=>$aValue )
				foreach( $aValue as $sKey=>$sValue )
				{
					$sConstant = strToUpper( "{$sPrefix}_{$sKey}" );
					if ( !defined( $sConstant ) )
						define( $sConstant, $sValue );
				}
		}
	}

?>