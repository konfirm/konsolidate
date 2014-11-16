<?php


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
	 *  @param   string  section to return
	 *  @param   string  module to receive confirguration [optional, default '/Config]
	 *  @return  Array   configured values
	 */
	public function load($file, $section=null, $target='/Config')
	{
		$result = $this->_assign(parse_ini_file($file, true), $target);

		if (!is_null($section) && isset($result[$section]))
			return $result[$section];

		return $result;
	}

	/**
	 *  Load and parse an inifile and create defines
	 *  @name    loadAndDefine
	 *  @type    method
	 *  @access  public
	 *  @param   string  inifile
	 *  @return  void
	 *  @note    defines are formatted like [SECTION]_[KEY]=[VALUE]
	 */
	public function loadAndDefine($file, $segment=null)
	{
		$config = $this->load($file, $segment);

		foreach ($config as $prefix=>$options)
			foreach ($options as $key=>$value)
			{
				$constant = strToUpper($prefix . '_' . $key);

				if (!defined($constant))
					define($constant, $value);
			}
	}

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

			$result[$key] = $this->_assign($value, $target . '/' . $key);
		}

		return $result;
	}
}
