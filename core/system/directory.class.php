<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
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
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreSystemDirectory extends Konsolidate
	{
		/**
		 *  Create a directory
		 *  @name    create
		 *  @type    method
		 *  @access  public
		 *  @param   string path
		 *  @param   oct    mode [optional, default 0777]
		 *  @return  bool
		 *  @syntax  bool CoreSystemDirectory->create( string path [, oct mode ] );
		 */
		public function create( $sPath, $nMode=0777 )
		{
			return ( is_dir( $sPath) || @mkdir( $sPath, $nMode, true ) ) && chmod( $sPath, $nMode );
		}

		/**
		 *  Get the directory contents
		 *  @name    getList
		 *  @type    method
		 *  @access  public
		 *  @param   string path
		 *  @param   bool   prependpath [optional, default true]
		 *  @return  array
		 *  @syntax  array CoreSystemDirectory->getList( string path [, bool prependpath ] );
		 */
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