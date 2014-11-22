<?php


/**
 *  Basic implementation of the HTTP protocol
 *  @name    CoreNetworkProtocolHTTP
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 *  @todo    Make proper use of the CoreNetworkSocket class, cURL fallback (for ease and performance) and
 *           implement HTTPS support
 */
class CoreNetworkProtocolHTTP extends Konsolidate
{
	const EOL = "\r\n";

	/**
	 *  The class version
	 *  @name    version
	 *  @type    string
	 *  @access  public
	 */
	public $version;

	/**
	 *  The array containing all prepared data
	 *  @name    _storage
	 *  @type    string
	 *  @access  protected
	 */
	protected $_storage;

	/**
	 *  The array containing all prepared files
	 *  @name    _filestorage
	 *  @type    string
	 *  @access  protected
	 */
	protected $_filestorage;

	/**
	 *  A boolean describing whether or not to use multipart/form-data
	 *  @name    _multiform
	 *  @type    string
	 *  @access  protected
	 */
	protected $_multiform;

	/**
	 *  The useragent to use
	 *  @name    _useragent
	 *  @type    string
	 *  @access  protected
	 */
	protected $_useragent;

	/**
	 *  An array containing status handlers
	 *  @name    _statushandler
	 *  @type    string
	 *  @access  protected
	 */
	protected $_statushandler;

	/**
	 *  An array containing the result headers that were send back after a request
	 *  @name    _requestheader
	 *  @type    string
	 *  @access  protected
	 */
	protected $_requestheader;

	/**
	 *  An array containing the result headers that are added to all requests
	 *  @name    _headerdata
	 *  @type    string
	 *  @access  protected
	 */
	protected $_headerdata;


	/**
	 *  CoreNetworkProtocolHTTP constructor
	 *  @name    CoreNetworkProtocolHTTP
	 *  @type    constructor
	 *  @access  public
	 *  @param   object parent object
	 *  @return  object
	 *  @note    This object is constructed by one of Konsolidates modules
	 */
	public function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$this->version        = '1.0.7';
		$this->_storage       = Array();
		$this->_filestorage   = Array();
		$this->_multiform     = false;
		$this->_useragent     = '';
		$this->_statushandler = Array();
		$this->_headerdata    = Array();
	}

	/**
	 *  Assign variables to the upcoming request
	 *  @name   prepareData
	 *  @type   method
	 *  @access public
	 *  @param  mixed  $variable  either an array containing key=>value pairs, which will be prepared as variables,
	 *                            or a string with the variable name
	 *  @param  mixed  $value     the value to set, note that $value will not be processed if you have provided an
	 *                            array as first variable
	 *  @return bool
	 */
	public function prepareData($variable, $value=false)
	{
		$success = true;

		if (is_array($variable))
		{
			foreach ($variable as $key=>$value)
				$success &= $this->prepareData($key, $value);

			return $success;
		}
		else if (is_array($value))
		{
			foreach ($value as $key=>$subValue)
				$success &= $this->prepareData("{$variable}[" . (is_integer($key) ? $key : '\'' . $key . '\''). ']', $subValue);

			return $success;
		}
		else if (is_string($variable))
		{
			if (is_object($value))
				$value = serialize($value);
			$this->_storage[$variable] = $value;

			return $this->_storage[$variable] == $value;
		}

		return false;
	}

	/**
	 *  Add files to the upcoming request
	 *  @name   prepareFile
	 *  @type   method
	 *  @access public
	 *  @param  string $file  the filename (including path) of the file that ought to be uploaded
	 *  @param  string $mime  the mime-type to use for the file [optional, defaults to 'application/octet-stream' which
	 *                        works for most files]
	 *  @return bool
	 *  @note   requires the request to be of type 'POST'), one additional variable will be added to the request. The
	 *          variable is called 'http_filecount' and contains the number of files being POSTed)
	 */
	public function prepareFile($file, $mime='')
	{
		if (file_exists($file))
		{
			if (empty($mime))
				$mime = 'application/octet-stream';

			$fp = fopen($file, 'rb');
			if ($fp)
			{
				$data = '';
				while (!feof($fp))
					$data .= fgets($fp, fileSize($file));
				fclose($fp);

				$this->_filestorage[] = Array(
					'name'=>$file,
					'data'=>$data,
					'mime'=>$mime
				);

				if (strLen($data) > 0)
				{
					$this->_multiform = true;

					return true;
				}
			}
		}

		return false;
	}

	/**
	 *  bind a statushandler function to a status code
	 *  @name   setStatusHandler
	 *  @type   method
	 *  @access public
	 *  @param  number  $status   the status code to respond on
	 *  @param  string  $function the function to call if status equals $status
	 *  @return void
	 *  @note   The function may receive up to two arguments, the first the status code (so you _can_ write a
	 *          catchAll/catchMulti function), the second being the HTTPRequest object itself, hint: make it a
	 *          reference if you need it
	 */
	public function setStatusHandler($status, $function)
	{
		$this->_statushandler[$status] = $function;
	}

	/**
	 *  Trigger a specific status handler (if it's defined)
	 *  @name   _triggerStatusHandler
	 *  @type   method
	 *  @access protected
	 *  @param  number $status The status number
	 *  @return void
	 */
	protected function _triggerStatusHandler($status)
	{
		if (CoreTool::arrVal($this->_statushandler, $status, false))
			$this->_statushandler[$status]($status, $this);
	}

	/**
	 *  Get the response line of the last request
	 *  @name   getResponse
	 *  @type   method
	 *  @access public
	 *  @return string
	 */
	public function getResponse()
	{
		return $this->getHeader('response');
	}

	/**
	 *  Get the response status of the last request
	 *  @name   getResponseStatus
	 *  @type   method
	 *  @access public
	 *  @return string
	 */
	public function getResponseStatus()
	{
		return $this->getHeader('status');
	}

	/**
	 *  Get the response info-text of the last requests status
	 *  @name   getResponseInfo
	 *  @type   method
	 *  @access public
	 *  @return string
	 */
	public function getResponseInfo()
	{
		return $this->getHeader('statusinfo');
	}

	/**
	 *  Get the response protocol of the last request
	 *  @name   getResponseProtocol
	 *  @type   method
	 *  @access public
	 *  @return string
	 */
	public function getResponseProtocol()
	{
		return $this->getHeader('protocol');
	}

	/**
	 *  Get a specific header from the last request
	 *  @name   getHeader
	 *  @type   method
	 *  @access public
	 *  @param  string $header  The header you wish to read [optional, returns all headers in an array if ommited)
	 *  @return string|array|bool
	 */
	public function getHeader($header='')
	{
		if (empty($header))
			return $this->_requestheader;
		else if (is_array($this->_requestheader) && array_key_exists($header, $this->_requestheader))
			return $this->_requestheader[$header];

		return false;
	}

	/**
	 *  Set a header to add to all upcoming requests
	 *  @name   setHeader
	 *  @type   method
	 *  @access public
	 *  @since  1.0.3
	 *  @param  mixed  $header  either an array containing key=>value pairs, which will be prepared as headers, or
	 *                          a string with the header name
	 *  @param  mixed  $value   the value to set, note that $value will not be processed if you have provided
	 *                          an array as first variable if the value is ommited or empty (0/false/'') the header
	 *                          will not be send
	 *  @return void
	 */
	public function setHeader($header, $value=false)
	{
		$success = true;

		if (is_array($header))
		{
			foreach ($header as $key=>$value)
				$success &= $this->setHeader($key, $value);

			return $success;
		}
		else if (is_string($header))
		{
			$this->_headerdata[$header] = $value;

			return($this->_headerdata[$header] == $value);
		}

		return false;
	}

	/**
	 *  store the headers seperatly
	 *  @name   _parseHeader
	 *  @type   method
	 *  @access protected
	 *  @param  array $header  the Array of headers
	 *  @return void
	 */
	protected function _parseHeader($header)
	{
		for ($i = 0; $i < count($header); ++$i)
			if ($i == 0) // the status reply (also starts a new array, which prevents mixing previous header info
			{
				$part = explode(' ', $header[$i], 3);
				$this->_requestheader = Array(
					'response'   => $header[$i],
					'protocol'   => $part[0],
					'status'     => $part[1],
					'statusinfo' => $part[2]
				);
			}
			else // other headers
			{
				$part = explode(':', $header[$i], 2);
				$this->_requestheader[$part[0]] = trim($part[1]);
			}
	}

	/**
	 *  get all required information from the path provided to a request
	 *  @name   _parseURL
	 *  @type   method
	 *  @access protected
	 *  @param  string $url  the URL to parse
	 *  @return void
	 */
	protected function _parseURL($url)
	{
		$url = parse_url((!strpos($url, '://') ? 'http://' : '') . $url);

		$this->host   = CoreTool::arrVal($url, 'host', $_SERVER['HTTP_HOST']);
		$this->path   = CoreTool::arrVal($url, 'path', '/');
		$this->scheme = CoreTool::arrVal($url, 'scheme', 'http');
		$this->port   = (int) CoreTool::arrVal($url, 'port', 80);
	}

	/**
	 *  Build up the actual data transportation string
	 *  @name   _buildDataString
	 *  @type   method
	 *  @access protected
	 *  @param  string $method    the request method to use
	 *  @param  string $boundary  the boundary to use to seperate variables/files from eachother
	 *  @return string
	 */
	protected function _buildDataString($method='GET', $boundary='++HTTPRequest++')
	{
		//  If we are sending files, add a variable telling the receiving end how many files are being transmitted
		if (strToUpper($method) == 'POST' && count($this->_filestorage) > 0)
			$storage = array_merge($this->_storage, Array('http_filecount'=>count($this->_filestorage)));
		else
			$storage = $this->_storage;

		$data  = '';
		foreach ($storage as $key=>$value)
		{
			if ($this->_multiform)
			{
				$data .= '--' . $boundary . PHP_EOL;
				$data .= 'Content-Disposition: form-data; name="' . $key . '"' . PHP_EOL;
				$data .= PHP_EOL;
				$data .= $value . PHP_EOL;
			}
			else
			{
				$data .= (empty($data) ? '' : '&') . $key . '=' . urlencode($sValue);
			}
		}

		if (strToUpper($method) == 'POST' && count($this->_filestorage) > 0)
		{
			for ($i = 0; $i < count($this->_filestorage); ++$i)
			{
				$key   = $i + 1;
				$data .= ($i > 0 ? PHP_EOL : '') . '--' . $boundary . PHP_EOL;
				$data .= 'Content-Disposition: form-data; name="file' . $key . '"; filename="' . $this->_filestorage[$i]['name'] . '"' . PHP_EOL;
				$data .= 'Content-Type: ' . $this->_filestorage[$i]['mime'] . PHP_EOL;
				$data .= PHP_EOL;
				$data .= $this->_filestorage[$i]['data'];
			}
		}
		if ($this->_multiform)
			$data .= '--' . $boundary . '--';

		return $data;
	}

	/**
	 *  Build up the entire request
	 *  @name   _buildRequestString
	 *  @type   method
	 *  @access protected
	 *  @param  string $method    the request method to use
	 *  @param  mixed  $referrer  the referer to provide
	 *  @return string
	 */
	protected function _buildRequestString($method='GET', $referrer=false)
	{
		$boundary = str_pad(substr(md5(time()), 0, 12), 40, '-', STR_PAD_LEFT);
		$method   = strToUpper($method);
		$data     = $this->_buildDataString($method, $boundary);

		//  Set or override headers
		$this->setHeader(
			Array(
				'Host'       => $this->host,
				'User-Agent' => (!empty($this->_useragent) ? $this->_useragent . ' ' : '') . get_class($this) . '/' . $this->version . ' (PHP)',
				'Referer'    => (!empty($referrer) ? $referrer : 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']),
				'Connection' => 'close'
			)
		);
		$request = $method . ' ' . $this->path . ($method == 'GET' && !empty($data) ? '?' . $data : '') . ' HTTP/1.1' . PHP_EOL;

		if (count($this->_headerdata) > 0)
			foreach ($this->_headerdata as $key=>$value)
				if (!empty($sValue))
					$request .= $key . ': ' . $value . PHP_EOL;

		if ($method == 'POST')
		{
			$request .= 'Content-type: ' . ($this->_multiform ? 'multipart/form-data; boundary=' . $boundary : 'application/x-www-form-urlencoded') . PHP_EOL;
			$request .= 'Content-length: ' . strlen($data) . static::EOL . static::EOL;
			$request .= "{$data}";
		}
		$request .= static::EOL . static::EOL;

		return $request;
	}

	/**
	 *  prepare and perform an actual request
	 *  @name   request
	 *  @type   method
	 *  @access public
	 *  @param  string $method    the request method to use
	 *  @param  string $url       the URL to request
	 *  @param  array  $data      additional paramaters to send (use key=>value pairs)
	 *  @param  mixed  $referrer  the referer to provide
	 *  @return string
	 */
	public function request($method, $url, $data=Array(), $referrer=false)
	{
		//  Files cannot be transmitted with a GET request, so even if files were added, we do not use multipart/form-data
		if (strToUpper($method) == 'GET' && $this->_multiform)
			$this->_multiform = false;

		//  Prepare all request URL requirements
		$this->_parseURL($url);

		//  Prepare all data (NOTE: variables set with PostRequest::prepare will be overwritten by variables provided in $data if they carry the same name!)
		$this->prepareData($data);

		//  Prepare the actual request
		$request = $this->_buildRequestString($method, $referrer);

		//  Open the connection, post the data and read the feedback
		$fp = @fsockopen($this->host, $this->port);
		if ($fp)
		{
			$result = Array(
				'header'  => Array(),
				'content' => ''
			);
			$header     = true;
			$chunked    = false;
			$beginChunk = false;
			$bytes      = 1024;

			fputs($fp, $request, strLen($request));
			while (!feof($fp))
			{
				$data = fgets($fp, $bytes);
				$trimmed = trim($data);

				//  determine wether or not the header has ended (this empty line is not added to either the header or
				//  the content)
				if (empty($trimmed) && $header)
				{
					$header = false;
					$this->_parseHeader($result['header']);

					//  if the content is delivered in chunks, we need to handle the content slightly different
					if ($this->getHeader('Transfer-Encoding') == 'chunked')
					{
						$chunked = true;
						$beginChunk     = true;
					}
				}
				//  add the result to the header array
				else if ($header)
				{
					$result['header'][] = $trimmed;
				}
				//  add the result to the content string
				else
				{
					//  we should handle chunked data delivery
					if ($chunked)
					{
						//  we are at the beginning of an era (chunk wise)
						if ($beginChunk)
						{
							$beginChunk = false;
							$bytes      = hexdec($trimmed); // chunk sizes are provided as HEX values

							if ($bytes == 0)
								break;

							unset($data); // clear data
						}
						//  the end of the chunk has been reached
						else if (is_numeric($trimmed) && $trimmed == 0)
						{
							$beginChunk = true;
							$bytes      = 1024;

							unset($data); // clear data
						}
					}
					if (!empty($data)) // do we have content?
						$result['content'] .= $data;
				}
			}

			fclose($fp);
			$this->_triggerStatusHandler($this->getResponseStatus());

			return $result['content'];
		}

		return false;
	}

	/**
	 *  Do a 'POST' request
	 *  @name   post
	 *  @type   method
	 *  @access public
	 *  @param  string $url      The URL to request
	 *  @param  array  $data     additional paramaters to send (use key=>value pairs)
	 *  @param  mixed  $referrer  The referer to provide
	 *  @return string
	 *  @note   if you are POSTing files, one additional variable is added to the request. The variable is called
	 *          'http_filecount' and contains the number of files being POSTed
	 */
	public function post($url, $data=Array(), $referrer=false)
	{
		return $this->request('post', $url, $data, $referrer);
	}

	/**
	 *  Do a 'GET' request
	 *  @name   get
	 *  @type   method
	 *  @access public
	 *  @param  string $url      The URL to request
	 *  @param  array  $data     additional paramaters to send (use key=>value pairs)
	 *  @param  mixed  $referrer  The referer to provide
	 *  @return string
	 */
	public function get()
	{
		$args     = func_get_args();
		$url      = array_shift($args);
		$data     = (bool) count($args) ? array_shift($args) : Array();
		$referrer = (bool) count($args) ? array_shift($args) : false;

		return $this->request('get', $url, $data, $referrer);
	}

	/**
	 *  Do a 'HEAD' request
	 *  @name   head
	 *  @type   method
	 *  @access public
	 *  @param  string $url      The URL to request
	 *  @param  array  $data     additional paramaters to send (use key=>value pairs)
	 *  @param  mixed  $referrer  The referer to provide
	 *  @return string
	 */
	public function head($url, $data=Array(), $referrer=false)
	{
		return $this->request('head', $url, $data, $referrer);
	}

	/**
	 *  Do a 'OPTIONS' request
	 *  @name   options
	 *  @type   method
	 *  @access public
	 *  @param  string $url      The URL to request
	 *  @return string
	 */
	public function options($url)
	{
		return $this->request('options', $url);
	}

	/**
	 *  Do a 'TRACE' request
	 *  @name   trace
	 *  @type   method
	 *  @access public
	 *  @param  string $url      The URL to request
	 *  @return string
	 */
	public function trace($url)
	{
		return $this->request('trace', $url);
	}
}
