<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDocumentationBlock
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Documentation/Block
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Dynamically create documentation blocks
	 *  @name    CoreDocumentationBlock
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.net>
	 */
	class CoreDocumentationBlock extends Konsolidate
	{
		protected $_instruct;
		public $name;
		public $type;
		public $access;
		public $param;
		public $syntax;
		public $reference;
		public $package;
	 	public $author;
		public $returns;
		public $description;
		public $note;


		/**
		 *  CoreDocumentationBlock constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreDocumentationBlock( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_instruct   = "DESCRIPTION";
			$this->name        = "";
			$this->type        = "unknown";
			$this->access      = "public";
			$this->param       = Array();
			$this->syntax      = "";
			$this->reference   = Array();
			$this->package     = "";
			$this->author      = Array();
			$this->returns     = "";
			$this->description = "";
			$this->note        = "";
		}

		/**
		 *  Append a documentation line to the 'current' documentaton rule
		 *  @name    append
		 *  @type    method
		 *  @access  public
		 *  @param   string documentation line
		 *  @returns void
		 *  @syntax  void CoreDocumentationBlock->append( string text )
		 */
		public function append( $sPart )
		{
			$sPart = trim( $sPart );
			if ( empty( $sPart ) )
				return;

			$sInstruction   = substr( $sPart, 0, strPos( $sPart, " " ) );
			$sDocumentation = trim( substr( $sPart, strPos( $sPart, " " ) + 1 ) );
			switch( strToUpper( $sInstruction ) )
			{
				case "@NAME":
				case "@TYPE":
				case "@ACCESS":
				case "@SYNTAX":
				case "@REFERENCE":
				case "@PACKAGE":
				case "@RETURNS":
				case "@NOTE":
				case "@ALIAS":
				case "@SEE":
				case "@EXTENDS":
				case "@IMPLEMENTS":
					$sProperty        = strToLower( substr( $sInstruction, 1 ) );
					$this->$sProperty = $sDocumentation;
					$this->_instruct  = $sProperty;
					break;
				case "@PARAM":
				case "@AUTHOR":
					$sProperty          = strToLower( substr( $sInstruction, 1 ) );
					array_push( $this->$sProperty, $sDocumentation );
					unset( $this->_instruct );
					break;
				default:
					if ( isset( $this->_instruct ) )
					{
						$sProperty        = strToLower( $this->_instruct );
						$this->$sProperty = ( !empty( $this->$sProperty ) ? trim( $this->$sProperty ) . " " : "" ) . $sPart;
					}
					break;
			}
		}

		/**
		 *  Retrieve all documentation instructions
		 *  @name    fetch
		 *  @type    method
		 *  @access  public
		 *  @param   bool skip empty instructions
		 *  @returns array instructions
		 *  @syntax  array CoreDocumentationBlock->fetch( bool skipempty )
		 */
		public function fetch( $bOmitEmpty=false )
		{
			$aReturn = get_object_vars( $this );
			foreach( $aReturn as $sKey=>$sValue )
				if ( substr( $sKey, 0, 1 ) == "_" || ( $bOmitEmpty && empty( $aReturn[ $sKey ] ) ) )
					unset( $aReturn[ $sKey ] );
			return $aReturn;
		}
	}

?>