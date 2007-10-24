<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreSystemBenchmark extends Konsolidate
	{
		protected $_starttime;
		protected $_endtime;

		public function start()
		{
			$this->_starttime = microtime( true );
		}

		public function stop()
		{
			$this->_endtime = microtime( true );
			return $this->_endtime - $this->_starttime;
		}
	}

?>