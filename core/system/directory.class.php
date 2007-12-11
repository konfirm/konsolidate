<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSystemDirectory
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: System/Directory
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Directory management class
	 *  @name    CoreSystemDirectory
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreSystemDirectory extends Konsolidate
	{
		public function create( $sPath, $nMode=0777 )
		{
			return ( is_dir( $sPath) || @mkdir( $sPath, $nMode, true ) ) && chmod( $sPath, $nMode );
		}

		public function getList( $sPath, $bPrependPath=true )
		{
			$aReturn = Array();
			foreach( new DirectoryIterator( $sPath ) as $nIndex=>$oItem )
			{
				$sName = $oItem->getFilename();
				if ( $sName{0} != "." )
					array_push( $aReturn, ( $bPrependPath ? "{$sPath}/" : "" ) . $sName );
			}
			return $aReturn;
		}
	}

?>