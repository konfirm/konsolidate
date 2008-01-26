<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreLanguage
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Language
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Phrase translation class, based on locales
	 *  @name    CoreLanguage
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreLanguage extends Konsolidate
	{
		private $_locale;
		private $_engine;

		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @returns object
		 *  @syntax  object = &new CoreLanguage( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->setEngine( "Switch" );
		}
		
		/**
		 *  set the locale
		 *  @name    setLocale
		 *  @type    method
		 *  @access  public
		 *  @param   string locale
		 *  @returns void
		 *  @syntax  void CoreLanguage->setLocale( string locale )
		 */
		public function setLocale( $sLocale )
		{
			$this->_locale = $sLocale;
		}

		/**
		 *  get the locale
		 *  @name    getLocale
		 *  @type    method
		 *  @access  public
		 *  @returns string locale
		 *  @syntax  string CoreLanguage->getLocale()
		 */
		public function getLocale()
		{
			return $this->_locale;
		}

		/**
		 *  set the 'translation' engine
		 *  @name    setEngine
		 *  @type    method
		 *  @access  public
		 *  @param   string engine
		 *  @returns bool
		 *  @syntax  bool CoreLanguage->setEngine( string engine )
		 */
		public function setEngine( $sEngine )
		{
			assert( is_string( $sEngine ) );
			assert( !empty( $sEngine ) );

			$oTMP = $this->register( $sEngine );
			if ( $oTMP !== false )
				$this->_engine = $sEngine;

			return $this->_engine === $sEngine;
		}

		/**
		 *  translate a phrase using the already set engine (default engine is 'Switch') and locale
		 *  @name    translate
		 *  @type    method
		 *  @access  public
		 *  @param   string phrase
		 *  @returns string translation
		 *  @syntax  string CoreLanguage->translate( string phrase )
		 */
		public function translate( $sPhrase )
		{
			return $this->call( "{$this->_engine}/translate", $sPhrase );
		}
	}

?>