<?php

define('TINYINT_MIN', -128);                // for future reference
define('TINYINT_MAX',  127);                // for future reference
define('SMALLINT_MIN', -32768);             // for future reference
define('SMALLINT_MAX',  32767);             // for future reference
define('MEDIUMINT_MIN', -8388608);          // for future reference
define('MEDIUMINT_MAX',  8388607);          // for future reference
define('INT_MIN', -2147483648);
define('INT_MAX', 2147483647);
define('BIGINT_MIN', -9223372036854775808); // for future reference
define('BIGINT_MAX', 9223372036854775807);  // for future reference


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
	 */
	function isInteger($value, $unsigned=false)
	{
		$min = $unsigned ? 0 : INT_MIN;
		$max = $unsigned ? INT_MAX + (-INT_MIN) : INT_MAX;

		if (is_null($value) || !preg_match('/^[0-9]+$/', abs($value)) || $value < $min || $value > $max)
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
	 */
	function isPositiveInteger($value, $unsigned=false)
	{
		return $this->isInteger($value, $unsigned) && $value >= 0;
	}

	/**
	 *  is the value a negative integer
	 *  @name    isNegativeInteger
	 *  @type    method
	 *  @access  public
	 *  @param   mixed value
	 *  @return  bool
	 */
	function isNegativeInteger($value)
	{
		return $this->isInteger($value) && $value < 0;
	}

	/**
	 *  is the value a number
	 *  @name    isNumber
	 *  @type    method
	 *  @access  public
	 *  @param   mixed value
	 *  @return  bool
	 */
	function isNumber($value)
	{
		return is_numeric($value);
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
	 */
	function isBetween($value, $min=null, $max=null, $inclusive=true)
	{
		return (is_null($min) || ($inclusive ? $value >= $min : $value > $min)) && (is_null($max) || ($inclusive ? $value <= $max : $value < $max));
	}

	/**
	 *  does the variable contain a value
	 *  @name    isFilled
	 *  @type    method
	 *  @access  public
	 *  @param   mixed value
	 *  @return  bool
	 */
	function isFilled($value)
	{
		return !preg_match('/^$/', $value);
	}

	/**
	 *  does the value represent a possible e-mail address
	 *  @name    isEmail
	 *  @type    method
	 *  @access  public
	 *  @param   mixed value
	 *  @return  bool
	 *  @note    This method does NOT verify the actual existing of the e-mail address, it merely verifies that it
	 *           complies to common e-mail addresses
	 */
	function isEmail($value)
	{
		return (bool) preg_match('/^[_a-z0-9-]+([a-z0-9\.\+_-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}$/i', $value);
	}
}
