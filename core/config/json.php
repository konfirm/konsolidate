<?php


/**
 *  Read and parse json files and store it's sections/variables for re-use in the Config Module
 *  @name    CoreConfigJSON
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreConfigJSON extends Konsolidate
{
	/**
	 *  Load and parse a JSON file and store it's sections/variables in the Konsolidate tree
	 *  @name    load
	 *  @type    method
	 *  @access  public
	 *  @param   string  xml file
	 *  @param   string  target module [optional, default null - '/Config']
	 *  @return  bool
	 */
	public function load($file, $section=null, $target='/Config')
	{
		$config = json_decode(file_get_contents($file));

		if (is_object($config))
		{
			$result = $this->_traverseJSON($config, $target);

			if (!is_null($section) && isset($result[$section]))
				return $result[$section];

			return $result;
		}

		return false;
	}

	/**
	 *  Traverse the JSON tree and set all values in it, using the node structure as path
	 *  @name    _traverseJSON
	 *  @type    method
	 *  @access  protected
	 *  @param   object  node
	 *  @param   string  target module
	 *  @return  array   configured values
	 */
	protected function _traverseJSON($node, $target=null)
	{
		$result = [];

		foreach ($node as $key=>$value)
		{
			if (is_scalar($value))
			{
				$result[$key] = $value;
				$this->set($target . '/' . $key, $result[$key]);
				continue;
			}

			$result[$key] = $this->_traverseJSON($value, $target . '/' . $key);
		}

		return $result;
	}
}