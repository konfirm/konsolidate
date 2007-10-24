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
			return ( is_dir( $sDir) || @mkdir( $sDir, $nMode, true ) );
		}

		public function unlink( $sPath, $bRecurse=true )
		{
			if ( is_dir( $sPath ) && $dh = opendir( $sPath ) )
			{
				while( ( $sEntry = readdir( $dh ) ) !== false )
					if ( $sEntry{0} != "." )
						$this->
			}
			else
			{
				return $this->call( "../File/unlink", $sPath );
			}
			return false;
		}
	}

?>