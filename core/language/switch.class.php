<?php

	/**
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
	class CoreLanguageSwitch extends Konsolidate
	{
		private $_request;
		private $_usage;

		public function translate( $sPhrase )
		{
			$this->_trackPhrase( $sPhrase );
			$sLocale = $this->call( "../getLocale" );
			if ( empty( $sLocale ) )
				return $sPhrase;
			return $this->_getPhraseByLocale( $sPhrase, $sLocale );
		}

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

		private function _trackPhrase( $sPhrase )
		{
			if ( !is_array( $this->_usage ) )
				$this->_usage = Array();
			array_push( $this->_usage, $this->call( "/DB/quote", $sPhrase ) );
		}

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