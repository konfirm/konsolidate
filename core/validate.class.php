<?php

	define( "TINYINT_MIN", -128 );                // for future reference
	define( "TINYINT_MAX",  127 );                // for future reference
	define( "SMALLINT_MIN", -32768 );             // for future reference
	define( "SMALLINT_MAX",  32767 );             // for future reference
	define( "MEDIUMINT_MIN", -8388608 );          // for future reference
	define( "MEDIUMINT_MAX",  8388607 );          // for future reference
	define( "INT_MIN", -2147483648 );
	define( "INT_MAX", 2147483647 );
	define( "BIGINT_MIN", -9223372036854775808 ); // for future reference
	define( "BIGINT_MAX", 9223372036854775807 );  // for future reference

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreValidate
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Validate
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 39 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-05-21 00:46:54 +0200 (Mon, 21 May 2007) $
	 *               \___\/           
	 */
	class CoreValidate extends Konsolidate
	{
		/**
		 *  is the value an integer
		 *  @name    isInteger
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @param   bool  unsigned [optional]
		 *  @returns bool
		 *  @syntax  Object->isInteger( mixed value [, bool unsigned ] );
		 */
		function isInteger( $mValue, $bUnsigned=false )
		{
			$nMin = $bUnsigned ? 0 : INT_MIN;
			$nMax = $bUnsigned ? INT_MAX + ( -INT_MIN ) : INT_MAX; 
			if ( is_null( $mValue ) || ( !ereg( "^[0-9]+$", $mValue ) ) || $mValue < $nMin || $mValue > $nMax )
				return false;
			return true;
		}

		/**
		 *  is the value a positive integer
		 *  @name    isPositiveInteger
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @param   bool  unsigned [optional]
		 *  @returns bool
		 *  @syntax  Object->isPositiveInteger( mixed value [, bool unsigned ] );
		 */
		function isPositiveInteger( $mValue, $bUnsigned=false )
		{
			return ( $this->isInteger( $mValue, $bUnsigned ) && $mValue >= 0 );
		}

		/**
		 *  is the value a negative integer
		 *  @name    isNegativeInteger
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @returns bool
		 *  @syntax  Object->isNegativeInteger( mixed value );
		 */
		function isNegativeInteger( $mValue )
		{
			return ( $this->isInteger( $mValue ) && $mValue < 0 );
		}

		/**
		 *  is the value a number
		 *  @name    isNumber
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @returns bool
		 *  @syntax  Object->isNumber( mixed value );
		 */
		function isNumber( $mValue )
		{
			return ( is_numeric( $mValue ) );
		}

		/**
		 *  is the value between (or equal to) two values
		 *  @name    isBetween
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @param   int   minimum [optional]
		 *  @param   int   maximum [optional]
		 *  @param   bool  include min/max values [optional]
		 *  @returns bool
		 *  @syntax  Object->isBetween( mixed value [, int min [, int max [, bool unsigned ] ] ] );
		 */
		function isBetween( $mValue, $iMin=null, $iMax=null, $bIncludeValues=true)
		{
			if ( $bIncludeValues )
			{
				if ( !is_null( $iMin ) )
					$iMin -= 1;
				if ( !is_null( $iMax ) )
					$iMax += 1;
			}

			if ( !is_null( $iMin ) && !is_null( $iMax ) )
				return ( $mValue > $iMin && $mValue < $iMax );
			else if ( !is_null( $iMin ) )
				return ( $mValue > $iMin );
			else if ( !is_null( $iMax ) )
				return ( $mValue < $iMax );
		}

		/**
		 *  does the variable contain a value
		 *  @name    isFilled
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @returns bool
		 *  @syntax  Object->isFilled( mixed value );
		 */
		function isFilled( $mValue )
		{
			return ( !ereg( "^$", $mValue ) );
		}

		/**
		 *  does the value represent a possible e-mail address
		 *  @name    isEmail
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @returns bool
		 *  @syntax  Object->isEmail( mixed value );
		 *  @note    This method does NOT verify the actual existing of the e-mail address, it merely verifies that it complies to common e-mail addresses
		 */
		function isEmail( $mValue )
		{
			return eregi( "^[_a-z0-9-]+([a-z0-9\.\+_-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3}|.info)$", $mValue );
		}
	}

?>