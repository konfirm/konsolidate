<?php


/**
 *  Standard processor for use with RPC-Controller Modules
 *  @name    CoreRPCControl
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreRPCControl extends Konsolidate
{
	/**
	 *  Send/assign feedback based on preferred format
	 *  @name    _feedback
	 *  @type    method
	 *  @access  protected
	 *  @param   bool   error during processing (optional, default true, we assume the worst)
	 *  @param   string message to display (optional, default empty)
	 *  @param   mixed  content, either a string with additional message, or an array containing arrays, strings or
	 *           numbers (optional, default empty)
	 *  @return  void
	 *  @syntax  void CoreRPCControl->_feedback()
	 */
	protected function _feedback($error=true, $message='', $content='')
	{
		if ($this->get('/Request/_format') == 'xml')
		{
			if ($this->call('/RPC/Status/send', $error, $message, $content))
				exit;
		}
		else
		{
			echo 'emulating normal POST/GET, dumping vars instead of assigning to template<br />';
			echo ' - error: ' . var_export($error, true) . '<br />';
			echo ' - message: ' . var_export($message, true) . '<br />';
			echo ' - content: ' . var_export($content, true) . '<br />';
		}
	}

	/**
	 *  Process the RPC request
	 *  @name    process
	 *  @type    method
	 *  @access  public
	 *  @param   string command
	 *  @return  void
	 *  @syntax  void CoreRPCControl->process(string command)
	 */
	function process($command)
	{
		$start  = strrpos($command, $this->_objectseparator);
		$module = substr($command, 0, $start);
		$method = substr($command, $start + 1);

		$processor = $this->get($module);
		if (is_object($processor) && method_exists($processor, $method))
		{
			$processor->$method();

			return $this->_feedback(!$processor->getStatus(), $processor->getMessage(), $processor->getContent());
		}

		return $this->_feedback();
	}
}
