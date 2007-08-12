<?php

	/**
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
	class CoreLanguage extends Konsolidate
	{
		private $_locale;
		private $_engine;

		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->setEngine( "Switch" );
		}
		
		public function setLocale( $sLocale )
		{
			$this->_locale = $sLocale;
		}

		public function getLocale()
		{
			return $this->_locale;
		}

		public function setEngine( $sEngine )
		{
			assert( is_string( $sEngine ) );
			assert( !empty( $sEngine ) );

			$oTMP = $this->register( $sEngine );
			if ( $oTMP !== false )
				$this->_engine = $sEngine;

			return $this->_engine === $sEngine;
		}

		public function translate( $sPhrase )
		{
			return $this->call( "{$this->_engine}/translate", $sPhrase );
		}
	}

?>