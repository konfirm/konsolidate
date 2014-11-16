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
	 *  Load and parse an XML file and store it's sections/variables in the Konsolidate tree
	 *  @name    load
	 *  @type    method
	 *  @access  public
	 *  @param   string  xml file
	 *  @param   string  target [optional, default '/Config']
	 *  @return  bool
	 */
	public function load($file, $section=null, $target='/Config')
	{
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;

		if ($dom->load($file))
		{
			$result = $this->_traverseXML($dom->documentElement, $target ?: '/' . $dom->documentElement->nodeName);

			if (!is_null($section) && isset($result[$section]))
				return $result[$section];

			return $result;
		}

		return false;
	}

	/**
	 *  Traverse the XML tree and set all values in it, using the node structure as path
	 *  @name    _traverseXML
	 *  @type    method
	 *  @access  protected
	 *  @param   object  node
	 *  @param   string  module path (optional, default null)
	 *  @return  array   configured values
	 */
	protected function _traverseXML($node, $target=null)
	{
		$result = [];

		foreach ($node->childNodes as $child)
			switch ($child->nodeType)
			{
				case 1:  //  DOMElement
					$traverse = $this->_traverseXML(
						$child,
						$target . ($node->parentNode->nodeType === 9 ? '' : '/' . $node->nodeName)
					);


					if ($node->parentNode->nodeType === 9)
						$result = array_merge($traverse, $result);
					else
						$result[$node->nodeName] = array_merge($traverse, isset($result[$node->nodeName]) ? $result[$node->nodeName] : []);
					break;

				case 3:  //  DOMText
				case 4:  //  DOMCDATASection
					$result[$node->nodeName] = $child->nodeValue;
					$this->set($target . '/' . $node->nodeName, $result[$node->nodeName]);
					break;
			}

		return $result;
	}
}
