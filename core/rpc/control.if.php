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
	 *         \          \  /        $Rev: 35 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-05-16 17:17:08 +0200 (Wed, 16 May 2007) $
	 *               \___\/           
	 */
	interface CoreRPCControlInterface
	{
		public function getMessage();
		public function getContent();
		public function getStatus();
	}

?>