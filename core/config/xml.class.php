<?php

	class CoreConfigXML extends Konsolidate
	{
		/**
		 *  Load and parse an xml file and store it's sections/variables in the Konsolidate tree (the XML root node being the offset module)
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @param   string  xml file
		 *  @returns bool
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
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @param   string  xml file
		 *  @returns bool
		 *  @syntax  Object->load( string xmlfile )
		 */
		protected function _traverseXML( $oNode, $sPath=null )
		{
			var_dump( "$sPath/" . $oNode->getName() . " :: {$oNode}" );
			if ( $oNode->children() )
				foreach( $oNode as $oChild )
					$this->_traverseXML( $oChild, "{$sPath}/" . $oNode->getName() );
			else
				$this->set( "{$sPath}/" . $oNode->getName(), (string) $oNode );
		}
	}

?>