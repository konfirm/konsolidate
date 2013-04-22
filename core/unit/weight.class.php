<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreUnitWeight
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Unit/Weight
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */

	/**
	 *  Convert weight measurement units
	 *  @name    CoreUnitLength
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 *  @author  Marco Balk <marco@uniqweb.nl>
	 */
	class CoreUnitWeight extends Konsolidate
	{
		const STANDARD      = "g";
		const GRAIN         = 0.06479891;
		const DRAM          = 1.7718451953125;
		const OUNCE         = 28.349523125;
		const POUND         = 453.59237;
		const HUNDREDWEIGHT = 45359.237;
		const SHORTTON      = 907184.74;
		const PENNYWEIGHT   = 1.55517384;
		const TROYOUNCE     = 31.1034768;
		const TROYPOUND     = 373.2417216;

		/**
		 *  Convert non-SI units to SI units (grams)
		 *  @name    load
		 *  @type    method
		 *  @access  protected
		 *  @param   number original value
		 *  @param   string original unit
		 *  @param   number conversion direction (from or to)
		 *  @return  number value
		 *  @syntax  bool   CoreUnitWeight->load( number value, string unit [ number direction ] )
		 */
		protected function _convert( $nValue, $sUnit, $nDirection=1 )
		{
			switch( $sUnit )
			{
				case "grain": case "gr":
					return $nValue * pow( self::GRAIN, $nDirection );
				case "dram": case "dr":
					return $nValue * pow( self::DRAM, $nDirection );
				case "ounce": case "oz":
					return $nValue * pow( self::OUNCE, $nDirection );
				case "pound": case "lb":
					return $nValue * pow( self::POUND, $nDirection );
				case "hundredweight": case "cwt":
					return $nValue * pow( self::HUNDREDWEIGHT, $nDirection );
				case "ton": case "shortton":
					return $nValue * pow( self::SHORTTON, $nDirection );
				case "pennyweight": case "dwt":
					return $nValue * pow( self::PENNYWEIGHT, $nDirection );
				case "troyounce": case "ozt": case "oz t":
					return $nValue * pow( self::TROYOUNCE, $nDirection );
				case "troypound": case "lbt": case "lb t":
					return $nValue * pow( self::TROYPOUND, $nDirection );
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
		 *  @syntax  bool   CoreUnitWeight->toBase( string value [ bool appendsuffix ] )
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
			$sUnit   = str_replace( "gram", "", $sUnit );
			$sSource = array_shift( $aSource );
			$nTemp   = $this->_convert( $this->toBase( $sSource, true ), $sUnit, -1 );
			if ( !is_null( $nTemp ) )
				return $nTemp;
			return $this->call( "../SI/baseToPrefix", $this->toBase( $sSource, true ), $sUnit );
		}
	}

?>