<?php


/**
 *  Provide backward compatibility for Config/XML between the Konsolidate v1 and v2
 *  @name    OneConfigXML
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class OneConfigXML extends CoreConfigXML
{
	/**
	 *  Load and parse an xml file and store it's sections/variables in the Konsolidate tree (the XML root node being the offset module)
	 *  @name    load
	 *  @type    method
	 *  @access  public
	 *  @param   string  xml file
	 *  @param   string  target [optional, default null]
	 *  @return  bool
	 */
	public function load($file, $target=null)
	{
		return parent::load($file, $target);
	}
}
