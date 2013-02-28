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

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreValidate
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Validate
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Basic validation
	 *  @name    CoreValidate
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
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
		 *  @return  bool
		 *  @syntax  Object->isInteger( mixed value [, bool unsigned ] );
		 */
		function isInteger( $mValue, $bUnsigned=false )
		{
			$nMin = $bUnsigned ? 0 : INT_MIN;
			$nMax = $bUnsigned ? INT_MAX + ( -INT_MIN ) : INT_MAX; 
			if ( is_null( $mValue ) || ( !preg_match( "/^[0-9]+$/", abs( $mValue ) ) ) || $mValue < $nMin || $mValue > $nMax )
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
		 *  @return  bool
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
		 *  @return  bool
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
		 *  @return  bool
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
		 *  @return  bool
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
		 *  @return  bool
		 *  @syntax  Object->isFilled( mixed value );
		 */
		function isFilled( $mValue )
		{
			return ( !preg_match( "/^$/", $mValue ) );
		}

		/**
		 *  does the value represent a possible e-mail address
		 *  @name    isEmail
		 *  @type    method
		 *  @access  public
		 *  @param   mixed value
		 *  @return  bool
		 *  @syntax  Object->isEmail( mixed value );
		 *  @note    This method does NOT verify the actual existing of the e-mail address, it merely verifies that it complies to common e-mail addresses
		 */
		function isEmail( $mValue )
		{
			return preg_match( "/^[_a-z0-9-]+([a-z0-9\.\+_-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3}|.info)$/i", $mValue );
		}
	}

?>