<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreSystemBenchmark
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: System/Benchmark
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 44 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-06-02 20:48:00 +0200 (Sat, 02 Jun 2007) $
	 *               \___\/           
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