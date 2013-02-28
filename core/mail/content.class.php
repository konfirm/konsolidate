<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreMailContent
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Mail/Content
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */

	/**
	 *  Automatically assign content to e-mails, using a job name
	 *  @name    CoreMailContent
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreMailContent extends Konsolidate
	{
		/**
		 *  Load the mails content based on the provided job name and set all relevant properties in the Mail object
		 *  @name    load
		 *  @type    method
		 *  @access  public
		 *  @param   string job name
		 *  @return  bool   success
		 *  @syntax  bool   CoreMailContent->load( string job )
		 */
		public function load( $sJob )
		{
			$sQuery  = "SELECT mlcid AS `id`,
						       mlcdescription AS `description`,
						       mlcjob AS `job`,
						       mlcfrom AS `from`,
						       mlcsender AS `sender`,
						       mlcto AS `to`,
						       mlcsubject AS `subject`,
						       mlccontent AS `content`,
						       mlcrichcontent AS `richcontent`,
						       mlctemplate AS `template`
						  FROM mailcontent
						 WHERE mlcjob=" . $this->call( "/DB/quote" , $sJob );
			$oResult = $this->call( "/DB/query", $sQuery );
			if ( is_object( $oResult ) && $oResult->errno <= 0 )
			{
				$oRecord = $oResult->next();
				foreach( $oRecord as $sKey=>$sValue )
					$this->set( "../{$sKey}", $sValue );
				return true;
			}
			return false;
		}
	}

?>