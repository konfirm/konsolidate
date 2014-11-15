<?php


/**
 *  The default interface RPC/Control modules have to implement
 *  @name    CoreRPCControlInterface
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
interface CoreRPCControlInterface
{
	public function getMessage();
	public function getContent();
	public function getStatus();
}
