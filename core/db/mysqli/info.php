<?php


/**
 *  MySQLi infor on a result set
 *  @name    CoreDBMySQLiInfo
 *  @type    class
 *  @package Core
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreDBMySQLiInfo extends Konsolidate
{
	/**
	 *  Process the mysqli info/stat strings into properties of the Info object
	 *  @name    process
	 *  @type    method
	 *  @access  public
	 *  @param   resource connection
	 *  @param   bool     extended info
	 *  @param   array    append info
	 *  @return  void
	 */
	public function process($connection, $extendInfo=false, $appendInfo=null)
	{
		$this->info = $connection->info;
		$info       = $this->_parseData($this->info);

		if (is_array($appendInfo))
			$info = array_merge($info, $appendInfo);

		if ($extendInfo)
		{
			$this->stat = $connection->stat();
			$info = array_merge($info, $this->_parseData($this->stat));
		}

		foreach ($info as $key=>$value)
		{
			$this->$key = is_numeric($value) ? 0 + $value : $value;
			if (strpos($key, '_') !== false)
			{
				$camelCase = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
				$this->$camelCase = $value;
			}
		}
	}

	/**
	 *  Parse the data string obtained from the info string
	 *  @name    _parseData
	 *  @type    method
	 *  @access  protected
	 *  @param   string data (mysqli data or info string)
	 *  @return  Array  parsed information
	 */
	protected function _parseData($data)
	{
		$result = Array();
		$replace = Array(
			'/\s\s/' => ',',
			'/\:\s/' => ':',
			'/\s/'   =>  '_'
		);

		if (!empty($data))
		{
			$data = preg_replace(array_keys($replace), array_values($replace), strtolower($data));
			if (preg_match_all('/([a-z_]+)\:([0-9\.]+),*/', $data, $match) && count($match) === 3)
				$result = array_combine($match[1], $match[2]);
		}

		return $result;
	}
}