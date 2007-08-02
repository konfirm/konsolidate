<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreDBMySQLQuery
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: DB/MySQL/Query
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 35 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-05-16 17:17:08 +0200 (Wed, 16 May 2007) $
	 *               \___\/           
	 */
	class CoreDBMySQLQuery extends Konsolidate
	{
		private $_conn;
		private $_result;

		public $query;
		public $exception;
		public $error;
		public $errno;

		public function __construct( &$oParent )
		{
			parent::__construct( $oParent );
			$this->query   = null;
			$this->_conn   = null;
			$this->_result = null;
			$this->rows    = null;
		}

		public function execute( $sQuery, &$rConnection )
		{
			$this->query   = $sQuery;
			$this->_conn   = $rConnection;
			$this->_result = @mysql_query( $this->query, $this->_conn );

			if ( is_resource( $this->_result ) )
				$this->rows = mysql_num_rows( $this->_result );
			else if ( $this->_result === true )
				$this->rows = mysql_affected_rows();

			//  We want the exception object to tell us everything is going extremely well, don't throw it!
			$this->import( "../exception.class.php" );
			$this->exception = new CoreDBMySQLException( $this->_conn );
			$this->errno     = &$this->exception->errno;
			$this->error     = &$this->exception->error;
		}

		public function rewind()
		{
			if ( is_resource( $this->_result ) && mysql_num_rows( $this->_result ) > 0 )
				return mysql_data_seek( $this->_result, 0 );
			return false;
		}

		public function next()
		{
			if ( is_resource( $this->_result ) )
				return mysql_fetch_object( $this->_result );
			return false;
		}

		public function lastInsertID()
		{
			return mysql_insert_id( $this->_conn );
		}

		public function lastId()
		{
			return $this->lastInsertID();
		}

		public function fetchAll()
		{
			$aReturn = Array();
			while( $oRecord = $this->next() )
				array_push( $aReturn, $oRecord );
			$this->rewind();
			return $aReturn;
		}
	}

?>