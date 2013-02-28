<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDocumentation
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Documentation
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Create run-time documentation based on the comments in the file
	 *  @name    CoreDocumentation
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreDocumentation extends Konsolidate
	{
		protected $_collect;

		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreDocumentation( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->_collect = Array();
		}
		
		/**
		 *  Collect all comment blocks from the provided file
		 *  @name    collect
		 *  @type    method
		 *  @access  public
		 *  @param   string filename
		 *  @return  array of Documentation/Block instances
		 *  @syntax  array CoreDocumentation->collect( string filename )
		 *  @note    the return array contains a single Documentation/Block instance per comment block in the 'collected' file
		 */
		public function collect( $sFile )
		{
			$sBody = file_get_contents( $sFile );

			preg_match_all( "#/\*\*(.*?)\*/#s", $sBody, $aMatch );
			if ( count( $aMatch ) > 1 )
			{
				foreach( $aMatch[ 1 ] as $sComment )
				{
					$nIndex                    = count( $this->_collect );
					$this->_collect[ $nIndex ] = $this->instance( "Block" );

					$sComment = preg_replace( "/([\r\t ]+)\*([\r\t ]+)/s", "", $sComment );
					$aComment = explode( "\n", $sComment );

					foreach( $aComment as $sCommentLine )
						$this->_collect[ $nIndex ]->append( $sCommentLine );
				}
			}
			return $this->_collect;
		}

		/**
		 *  Retrieve all collected Documentation/Block instances
		 *  @name    fetch
		 *  @type    method
		 *  @access  public
		 *  @param   bool   skip empty documentation instructions
		 *  @syntax  array CoreDocumentation->fetch( bool skipempty )
		 *  @return  array of Documentation/Block instances
		 */
		public function fetch( $bOmitEmpty=false )
		{
			$aReturn = Array();
			foreach( $this->_collect as $oDocBlock )
				array_push( $aReturn, $oDocBlock->fetch( $bOmitEmpty ) );
			return $aReturn;
		}
	}

?>