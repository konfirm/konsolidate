<?php


/**
 *  Provide backward compatibility for Config/XML between the Konsolidate v1 and v2
 *  @name    OneConfigINI
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class OneConfigINI extends Konsolidate
{
	/**
	 *  Take a key/value array and assign the keys to the target module, if the value is an array, its values will be
	 *  added to a child module with the name of the key
	 *  @name    _assign
	 *  @type    method
	 *  @access  protected
	 *  @param   Array   configuration
	 *  @param   string  target module
	 *  @return  Array   configured values
	 */
	protected function _assign(Array $config, $target)
	{
		$result = [];

		foreach ($config as $key=>$value)
		{
			if (is_scalar($value))
			{
				$result[$key] = $value;
				$this->set($target . '/' . $key, $result[$key]);
				continue;
			}

			//  The only compatibility change lies in the following line, where we need to restore the inheritance from
			//  any 'default' section (if any)
			$result[$key] = array_merge($this->_assign($value, $target . '/' . $key), isset($result['default']) ? $result['default'] : []);
		}

		return $result;
	}
}
