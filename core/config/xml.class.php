<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreConfigXML
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Config/XML
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


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
		 *  @return  bool
		 *  @syntax  Object->load( string xmlfile )
		 */
		public function load( $sFile )
		{
			$oConfig = simplexml_load_file( $sFile );
			if ( is_object( $oConfig ) )
				return $this->_traverseXML( $oConfig );
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
		 *  @syntax  Object->_traverseXML( object node [, string path ] )
		 */
		protected function _traverseXML( $oNode, $sPath=null )
		{
			if ( $oNode->children() )
				foreach( $oNode as $oChild )
					$this->_traverseXML( $oChild, "{$sPath}/" . $oNode->getName() );
			else
				$this->set( "{$sPath}/" . $oNode->getName(), (string) $oNode );
		}
	}

?>