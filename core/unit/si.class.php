<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUnitSI
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Unit/SI
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */

	/**
	 *  Convert SI measurement units
	 *  @name    CoreUnitSI
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 *  @author  Marco Balk <marco@uniqweb.nl>
	 */
	class CoreUnitSI extends Konsolidate
	{
		const YOTA     = 24; 			const DECI  = -1;
		const ZETTA    = 21; 			const CENTI = -2;
		const EXA      = 18; 			const MILLI = -3;
		const PETA     = 15; 			const MICRO = -6;
		const TERA     = 12; 			const NANO  = -9;
		const GIGA     = 9;  			const PICO  = -12;
		const MEGA     = 6;  			const FEMTO = -15;
		const KILO     = 3;  			const ATTO  = -18;
		const HECTO    = 2;  			const ZEPTO = -21;
		const DECA     = 1;  			const YOCTO = -24;
		const STANDARD = 0;

		protected $_unitMatrix = Array( "YOTA"=>"Y", "ZETTA"=>"Z", "EXA"=>"E", "PETA"=>"P", "TERA"=>"T", "GIGA"=>"G", "MEGA"=>"M", "KILO"=>"k", "HECTO"=>"h", "DECA"=>"da", "DECI"=>"d", "CENTI"=>"c", "MILLI"=>"m", "MICRO"=>"μ", "NANO"=>"n", "PICO"=>"p", "FEMTO"=>"f", "ATTO"=>"a", "ZEPTO"=>"z", "YOCTO"=>"y", "STANDARD"=>"" );


		/**
		 *  Convert SI units to non-SI units
		 *  @name    _convert
		 *  @type    method
		 *  @access  protected
		 *  @param   number original value
		 *  @param   string original unit
		 *  @param   number conversion direction (from or to)
		 *  @return  number value
		 *  @syntax  bool   CoreUnitSI->_convert( number value, string unit [ number direction ] )
		 */
		protected function _convert( $nBase, $sFix="", $nDirection=1 )
		{
			if ( !array_key_exists( strToUpper( $sFix ), $this->_unitMatrix ) )
				$sFix = array_search( $sFix, $this->_unitMatrix );
			else
				$sFix = strToUpper( $sFix );

			if ( $sFix === false )
				throw new Exception( "No or unknown pre-/suffix provided" );
			
			return $nBase / pow( 10, ( $nDirection * constant( "self::{$sFix}" ) ) );
		}
		
		/**
		 *  Convert base units to the non-SI unit
		 *  @name    baseToPrefix
		 *  @type    method
		 *  @access  public
		 *  @param   string original value+unit
		 *  @param   string original unit
		 *  @return  mixed  value
		 *  @syntax  bool   CoreUnitLength->toBase( string value [, string unit ] )
		 */
		public function baseToPrefix( $nBase, $sFix="" )
		{
			return $this->_convert( $nBase, $sFix );
		}
		
		/**
		 *  Convert non-SI units to the base unit
		 *  @name    prefixToBase
		 *  @type    method
		 *  @access  public
		 *  @param   string original value+unit
		 *  @param   string original unit
		 *  @return  mixed  value
		 *  @syntax  bool   CoreUnitSI->prefixToBase( string value [, string unit ] )
		 */
		public function prefixToBase( $nBase, $sFix="" )
		{
			return $this->_convert( $nBase, $sFix, -1 );
		}
	}

?>