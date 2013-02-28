<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSystemBenchmark
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: System/Benchmark
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Simple benchmarking functions
	 *  @name    CoreSystemBenchmark
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreSystemBenchmark extends Konsolidate
	{
		protected $_timer;

		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->_timer = Array();
		}

		public function start()
		{
			$this->_timer[] = microtime( true );
			return count( $this->_timer ) - 1;
		}

		public function stop( $nTimer=null )
		{
			if ( empty( $nTimer ) )
				$nTimer = count( $this->_timer ) - 1;
			if ( $nTimer < 0 || $nTimer >= count( $this->_timer ) )
				return false;
			return microtime( true ) - $this->_timer[ $nTimer ];
		}
	}

?>
