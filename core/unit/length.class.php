<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUnitLength
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Unit/Length
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */

	/**
	 *  Convert length measurement units
	 *  @name    CoreUnitLength
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 *  @author  Marco Balk <marco@uniqweb.nl>
	 */
	class CoreUnitLength extends Konsolidate
	{
		const STANDARD      = "m";
		const INCH          = 0.0254;
		const FOOT          = 0.3048;
		const YARD          = 0.9144;
		const MILE          = 1609.344;
		const NAUTICAL_MILE = 1852;
		const KLICK         = 1000;

		/**
		 *  Convert non-SI units to SI units (meters)
		 *  @name    _convert
		 *  @type    method
		 *  @access  protected
		 *  @param   number original value
		 *  @param   string original unit
		 *  @param   number conversion direction (from or to)
		 *  @return  number value
		 *  @syntax  bool   CoreUnitLength->_convert( number value, string unit [ number direction ] )
		 */
		protected function _convert( $nValue, $sUnit, $nDirection=1 )
		{
			switch( $sUnit )
			{
				case "in": case "inch":
					return $nValue * pow( self::INCH, $nDirection );
				case "ft": case "foot": case "feet":
					return $nValue * pow( self::FOOT, $nDirection );
				case "yd": case "yard":
					return $nValue * pow( self::YARD, $nDirection );
				case "mi": case "mile":
					return $nValue * pow( self::MILE, $nDirection );
				case "nm": case "nmi":
					return $nValue * pow( self::NAUTICAL_MILE, $nDirection );
				case "klick": case "click":
					return $nValue * pow( self::KLICK, $nDirection );
			}
			return null;
		}

		/**
		 *  Convert non-SI units to the base unit
		 *  @name    toBase
		 *  @type    method
		 *  @access  public
		 *  @param   string original value+unit
		 *  @param   bool   append suffix
		 *  @return  mixed  value
		 *  @syntax  bool   CoreUnitLength->toBase( string value [ bool appendsuffix ] )
		 */
		public function toBase( $sValue, $bOmitSuffix=false )
		{
			$sUnit  = preg_replace( "/[0-9\., -]*/", "", $sValue );
			$nValue = floatVal( $sValue );
			$nTemp  = $this->_convert( $nValue, $sUnit );

			if ( !is_null( $nTemp ) )
			{
				$sUnit  = "";
				$nValue = $nTemp;
			}
			else if ( substr( $sUnit, -1 ) == self::STANDARD )
			{
				$sUnit = substr( $sUnit, 0, -1 );
			}
			return $this->call( "../SI/prefixToBase", $nValue, $sUnit ) . ( $bOmitSuffix ? "" : self::STANDARD );
		}

		public function __call( $sUnit, $aSource )
		{
			$sUnit   = str_replace( "meter", "", $sUnit );
			$sSource = array_shift( $aSource );
			$nTemp   = $this->_convert( $this->toBase( $sSource, true ), $sUnit, -1 );
			if ( !is_null( $nTemp ) )
				return $nTemp;
			return $this->call( "../SI/baseToPrefix", $this->toBase( $sSource, true ), $sUnit );
		}
	}

?>