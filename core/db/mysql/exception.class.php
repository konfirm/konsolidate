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
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
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