<?php

	/*
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


	/**
	 *  The default interface RPC/Control modules have to implement
	 *  @name    CoreRPCControlInterface
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	interface CoreRPCControlInterface
	{
		public function getMessage();
		public function getContent();
		public function getStatus();
	}

?>