<?php


/**
 *  Support for REST (by accident) protocol, POST/GET only
 *  @name    CoreRPCREST
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreRPCREST extends CoreRPC
{
	/**
	 *  Autonimously process a 'REST' request
	 *  @name    process
	 *  @type    method
	 *  @access  public
	 *  @param   string configfile (optional, default null)
	 *  @return  bool
	 */
	public function process($configFile=null)
	{
		if (!is_null($configFile))
			$this->loadConfig($configFile);

		if (is_array($this->_config))
		{
			$args    = explode('/', trim(str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']), '/'));
			$command = array_shift($args);

			if (array_key_exists('rest', $this->_config) && array_key_exists($command, $this->_config['rest']))
			{
				$command = '../Control/' . $this->_config['rest'][$command];
				$module  = dirname($command);

				array_unshift($args, $command);
				call_user_func_array(Array($this, 'call'), $args);

				$this->call('/RPC/Status/send',
					$this->call($module . '/getStatus'),
					$this->call($module . '/getMessage'),
					$this->call($module . '/getContent')
				);

				return true;
			}
		}

		return false;
	}
}
