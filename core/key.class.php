<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreKey
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Key
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Create keys based on a simple pattern
	 *  @name    CoreKey
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreKey extends Konsolidate
	{
		const CHAR    = "abcdefghijklmnopqrstuvwxyz";
		const NUMERIC = "0123456789";

		/**
		 *  The salt used
		 *  @name    _salt
		 *  @type    string
		 *  @access  protected
		 */
		protected $_salt;

		/**
		 *  Character that shouldn't be in the output
		 *  @name    _exclude
		 *  @type    string
		 *  @access  protected
		 */
		protected $_exclude;

		/**
		 *  use lowercase characters
		 *  @name    _lowercase
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_lowercase;

		/**
		 *  use uppercase characters
		 *  @name    _uppercase
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_uppercase;

		/**
		 *  use numeric characters
		 *  @name    _numeric
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_numeric;

		/**
		 *  The default format to use
		 *  @name    _format
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_format;

		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreKey( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_exclude   = "oO0iI1lzZ2sS5uUvVwWnNmMRQq";
			$this->_lowercase = true;
			$this->_uppercase = true;
			$this->_numeric   = true;
			$this->_format    = "XXXX-XXXX";
			$this->_createSalt();
		}

		/**
		 *  Create a key based on provided/default format
		 *  @name    create
		 *  @type    method
		 *  @access  public
		 *  @param   string format (optional, default XXXX-XXXX)
		 *  @return  string generated key
		 *  @syntax  string CoreKey->create( string format )
		 *  @note    string format uses XXXX-XXXX-XXXX, where X is replaced with a key part
		 */
		public function create( $sFormat=null )
		{
			if ( is_null( $sFormat ) )
				$sFormat = $this->_format;
			return vsprintf( str_replace( Array( "%", "X" ), Array( "%%", "%s" ), $sFormat ), preg_split( "//", substr( str_shuffle( $this->_salt ), 0, substr_count( $sFormat, "X" ) ), -1, PREG_SPLIT_NO_EMPTY ) );
		}

		/**
		 *  Create the 'salt' (string of characters) to use in generated keys
		 *  @name    _createSalt
		 *  @type    method
		 *  @access  protected
		 *  @return  void
		 *  @syntax  void CoreKey->_createSalt()
		 */
		protected function _createSalt()
		{
			$this->_salt  = $this->_lowercase ? self::CHAR : "";
			$this->_salt .= $this->_uppercase ? strToUpper( self::CHAR ) : "";
			$this->_salt .= $this->_numeric ? self::NUMERIC : "";
			$this->_salt  = preg_replace( "/[{$this->_exclude}]/", "", $this->_salt );
			$this->_salt  = str_repeat( $this->_salt, strlen( $this->_format ) );
			$this->_salt  = str_shuffle( $this->_salt );
		}

		/**
		 *  magic __set, set the rules on which the 'salt' (string of characters) is based
		 *  @name    __set
		 *  @type    method
		 *  @access  public
		 *  @return  void
		 *  @syntax  void CoreKey->[string property] = mixed value
		 *  @note    reserved properties which actually change the 'salt' are: lowercase, uppercase, numeric, exclude and format and are treated as boolean values
		 *           these reserved properties behave exactly as expected, except that they additionally modify the 'salt' the moment one of them is set
		 */
		public function __set( $sProperty, $mValue )
		{
			switch( $sProperty )
			{
				case "lowercase":
				case "uppercase":
				case "numeric":
				case "exclude":
				case "format":
					$sProperty = "_{$sProperty}";
					$this->$sProperty = $mValue;
					$this->_createSalt();
				default:
					parent::__set( $sProperty, $mValue );
					break;
			}
		}
	}

?>
