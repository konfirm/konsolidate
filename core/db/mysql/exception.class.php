<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDBMySQLException
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: DB/MySQL/Exception
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 35 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-05-16 17:17:08 +0200 (Wed, 16 May 2007) $
	 *               \___\/           
	 */
	class CoreDBMySQLException extends Exception
	{
		public $error;
		public $errno;
		
		public function __construct( &$rConnection )
		{
			$this->error = is_resource( $rConnection ) ? mysql_error( $rConnection ) : mysql_error();
			$this->errno = is_resource( $rConnection ) ? mysql_errno( $rConnection ) : mysql_errno();
		}
	}

?>