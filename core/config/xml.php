<?php


/**
 *  Read and parse xml files and store it's sections/variables for re-use in the Config Module
 *  @name    CoreConfigXML
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreConfigXML extends Konsolidate
{
	/**
	 *  Load and parse an xml file and store it's sections/variables in the Konsolidate tree (the XML root node being the offset module)
	 *  @name    load
	 *  @type    method
	 *  @access  public
	 *  @param   string  xml file
	 *  @param   string  target [optional, default '/Config']
	 *  @return  bool
	 */
	public function load($file, $target='/Config')
	{
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;

		if ($dom->load($file))
			return $this->_traverseXML($dom->documentElement, $target ?: '/' . $dom->documentElement->nodeName);

		return false;
	}

	/**
	 *  Traverse the XML tree and set all values in it, using the node structure as path
	 *  @name    _traverseXML
	 *  @type    method
	 *  @access  protected
	 *  @param   object  node
	 *  @param   string  xml file (optional, default null)
	 *  @return  bool
	 */
	protected function _traverseXML($node, $target=null)
	{
		foreach ($node->childNodes as $child)
			switch ($child->nodeType)
			{
				case 1:  //  DOMElement
					$this->_traverseXML($child, $target . ($node->parentNode->nodeType === 9 ? '' : '/' . $node->nodeName));
					break;

				case 3:  //  DOMText
				case 4:  //  DOMCDATASection
					$this->set($target . '/' . $node->nodeName, $child->nodeValue);
					break;
			}
	}
}
