<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreLanguageSwitch
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Language
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Language engine based on DB interaction
	 *  @name    CoreKey
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreLanguageSwitch extends Konsolidate
	{
		private $_request;
		private $_usage;

		/**
		 *  translate a phrase using the locale set in the (Core)Language module
		 *  @name    translate
		 *  @type    method
		 *  @access  public
		 *  @param   string phrase
		 *  @returns string translation
		 *  @syntax  string CoreLanguageSwitch->translate( string phrase )
		 */
		public function translate( $sPhrase )
		{
			$this->_trackPhrase( $sPhrase );
			$sLocale = $this->call( "../getLocale" );
			if ( empty( $sLocale ) )
				return $sPhrase;
			return $this->_getPhraseByLocale( $sPhrase, $sLocale );
		}

		/**
		 *  get translate phrase based on locale
		 *  @name    _getPhraseByLocale
		 *  @type    method
		 *  @access  private
		 *  @param   string phrase
		 *  @param   string locale
		 *  @returns string translation
		 *  @syntax  string CoreLanguageSwitch->_getPhraseByLocale( string phrase )
		 */
		private function _getPhraseByLocale( $sPhrase, $sLocale )
		{
			$sQuery  = "SELECT lsp.lspphrase AS phrase,
						       lst.lsttranslation AS translation
						  FROM languageswitchphrase lsp
						 INNER JOIN languageswitchtranslation lst 
						    ON lst.lspid=lsp.lspid 
						   AND lst.lstlocale=" . $this->call( "/DB/quote", $sLocale ) . " 
						   AND lst.lstenabled=1
						 WHERE lsp.lspphrase=" . $this->call( "/DB/quote", $sPhrase );
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 && $oResult->rows > 0 )
				while( $oRecord = $oResult->next() )
					return $oRecord->translation;
			return $sPhrase;
		}

		/**
		 *  keep track of used translations, making overviewing translations in a CMS easy
		 *  @name    _trackPhrase
		 *  @type    method
		 *  @access  private
		 *  @param   string phrase
		 *  @returns void
		 *  @syntax  void CoreLanguageSwitch->_trackPhrase( string phrase )
		 */
		private function _trackPhrase( $sPhrase )
		{
			if ( !is_array( $this->_usage ) )
				$this->_usage = Array();
			array_push( $this->_usage, $this->call( "/DB/quote", $sPhrase ) );
		}

		/**
		 *  magic __destruct, write all requested phrases to the database, inserting new phrases or increasing usage counter
		 *  @name    __destruct
		 *  @type    method
		 *  @access  public
		 *  @returns void
		 *  @syntax  void CoreLanguageSwitch->__destruct()
		 */
		public function __destruct()
		{
			if ( is_array( $this->_usage ) && count( $this->_usage ) > 0 )
			{
				$sQuery  = "INSERT INTO languageswitchphrase
							       ( lspphrase, lspcreatedts )
							VALUES ( " . implode( ", NOW() ),( ", $this->_usage ) . ", NOW() )
							ON DUPLICATE KEY
							UPDATE lspmodifiedts=NOW()";
				$this->call( "/DB/query", $sQuery );
			}
		}
	}

?>