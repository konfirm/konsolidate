<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Interface:  CoreRPCControlInterface
	 *     \/___/  \___\/      \      Tier:       Core
	 *      \   \  /\   \  /\  /      
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */
	interface CoreRPCControlInterface
	{
		public function getMessage();
		public function getContent();
		public function getStatus();
	}

?>