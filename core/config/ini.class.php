<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
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


	/**
	 *  Read ini files and store ini-sections/variables for re-use in the Config Module
	 *  @name    CoreConfigINI
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreConfigINI extends Konsolidate
	{
		/**
		 *  Load and parse an inifile and store it's sections/variables in the Config Module
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @param   string  inifile
		 *  @return  array
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
					$this->set( "/Config/{$sPrefix}", $mValue );
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
		 *  @return  void
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