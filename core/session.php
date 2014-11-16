<?php


/**
 *  Session support, aimed for use on WebFarms (Multiple webservers serving a single domain) by using the database
 *  as common resource
 *  @name    CoreSession
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */

class CoreSession extends  Konsolidate
{
	/**
	 *  The session id
	 *  @name    _id
	 *  @type    string
	 *  @access  protected
	 */
	protected $_id;

	/**
	 *  Was the session started
	 *  @name    _started
	 *  @type    boolean
	 *  @access  protected
	 */
	protected $_started;

	/**
	 *  Session name
	 *  @name    _sessionname
	 *  @type    string
	 *  @access  protected
	 *  @note    value of /Config/Session/name or default 'KSESSION'
	 */
	protected $_sessionname;

	/**
	 *  Session duration (in seconds)
	 *  @name    _duration
	 *  @type    int
	 *  @access  protected
	 *  @note    value of /Config/Session/duration or default 1800 (30 minutes)
	 */
	protected $_duration;

	/**
	 *  Cookie domain
	 *  @name    _cookiedomain
	 *  @type    string
	 *  @access  protected
	 *  @note    value of /Config/Cookie/domain or default value of _SERVER['HTTP_HOST']
	 */
	protected $_cookiedomain;

	/**
	 *  Have one or more any of the properties changed
	 *  @name    _updated
	 *  @type    bool
	 *  @access  protected
	 */
	protected $_updated;


	/**
	 *  constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   object parent object
	 *  @return  object
	 *  @note    This object is constructed by one of Konsolidates modules
	 */
	public function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$this->_id           = md5($this->get('/User/Tracker/id') . $this->get('/User/Tracker/code'));
		$this->_started      = false;
		$this->_sessionname  = $this->get('/Config/Session/name', 'KSESSION');
		$this->_duration     = $this->get('/Config/Session/duration', 1800); // 30 minutes
		$this->_cookiedomain = $this->get('/Config/Cookie/domain', $_SERVER['HTTP_HOST']);
		$this->_updated      = false;
	}

	/**
	 *  Start the session
	 *  @name    start
	 *  @type    method
	 *  @access  public
	 *  @param   string sessionname, optional defaults to protected _sessionname
	 *  @return  bool   success
	 */
	public function start($name=null)
	{
		if (!empty($name) && $name != $this->_sessionname)
		{
			$this->_sessionname = $name;
			$this->_started     = false;
		}

		if (!$this->_started)
		{
			$cookie         = $this->_getSessionCookie();
			$this->_started = $this->_setSessionCookie();

			if ($cookie === $this->_id)
			{
				$query  =  'SELECT sesdata
							  FROM session
							 WHERE ustid=' . $this->get('/User/Tracker/id') . '
							   AND sescode=' . $this->call('/DB/quote', $this->_id) . '
							   AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(sesmodifiedts) <= ' . $this->_duration;
				$result = $this->call('/DB/query', $query);
				if (is_object($result) && $result->errno <= 0 && $result->rows == 1)
				{
					$record = $result->next();
					$this->_property = unserialize($record->sesdata);
					if (!is_array($this->_property))
					{
						$this->_property = Array();

						return false;
					}

					return true;
				}
			}
			else if ($cookie === false)
			{
				return true;
			}
		}
		else
		{
			return true;
		}

		return false;
	}

	/**
	 *  Register one or more session variables
	 *  @name    register
	 *  @type    method
	 *  @access  public
	 *  @param   string variable
	 *  @return  void
	 *  @note    Variables can also be assigned to a CoreSession directly using Konsolidate->set('/Session/variable', 'value');
	 */
	public function register($module)
	{
		$args = func_get_args();
		if (count($args) == 1 && is_string($module))
			if ($this->checkModuleAvailability($module) || !array_key_exists($module, $GLOBALS))
				return parent::register($module);

		if ($this->start())
			foreach ($args as $variable)
			{
				if (is_array($variable))
					call_user_func_array(Array($this, 'register'), $variable);
				else
					$this->$variable = $GLOBALS[$variable];
			}
	}

	/**
	 *  Remove one or more session variables from the session
	 *  @name    unregister
	 *  @type    method
	 *  @access  public
	 *  @param   string variable
	 *  @return  void
	 */
	public function unregister()
	{
		if ($this->start())
		{
			$args = func_get_args();
			foreach ($args as $variable)
				if (is_array($variable))
				{
					call_user_func_array(Array($this, 'unregister'), $variable);
				}
				else if ($this->isRegistered($variable))
				{
					$this->_updated = true;
					unset($this->_property[$variable]);
				}
		}
	}

	/**
	 *  Create/update the session cookie
	 *  @name    _setSessionCookie
	 *  @type    method
	 *  @access  protected
	 *  @return  bool
	 */
	protected function _setSessionCookie()
	{
		return setCookie(
			$this->_sessionname,
			$this->_id,
			time() + $this->_duration,
			'/',
			$this->_cookiedomain
		);
	}

	/**
	 *  Get the session cookie
	 *  @name    _getSessionCookie
	 *  @type    method
	 *  @access  protected
	 *  @return  string
	 */
	protected function _getSessionCookie()
	{
		return $this->call('/Tool/cookieVal', $this->_sessionname, false);
	}

	/**
	 *  Destroy the session variables or session entirely
	 *  @name    destroy
	 *  @type    method
	 *  @access  public
	 *  @param   bool   removecookie [optional, default false]
	 *  @return  void
	 *  @note    The cookie is kept by default
	 */
	public function destroy($removeCookie=false)
	{
		$this->_property = Array();
		$this->_updated  = true;
		if ($removeCookie)
			return setCookie(
				$this->_sessionname,
				'',
				time() + $this->_duration,
				'/',
				$this->_cookiedomain
			);
	}

	/**
	 *  Is a variable registered
	 *  @name    isRegistered
	 *  @type    method
	 *  @access  public
	 *  @param   string variable
	 *  @return  bool
	 */
	public function isRegistered($variable)
	{
		if ($this->start())
			return array_key_exists($variable, $this->_property);

		return false;
	}

	/**
	 *  Store the session data
	 *  @name    writeClose
	 *  @type    method
	 *  @access  public
	 *  @return  bool
	 *  @note    unlike PHP's session_write_close function, CoreSession->writeClose does _NOT_ end the session, you can
	 *           still add/change values which will be stored
	 */
	public function writeClose()
	{
		if ($this->start() && $this->_updated)
		{
			$data   = serialize($this->_property);
			$query  = 'INSERT INTO session (
							   ustid,
							   sescode,
							   sesdata,
							   sesmodifiedts,
							   sescreatedts
						)
						VALUES (
							   ' . $this->get('/User/Tracker/id') . ',
							   ' . $this->call('/DB/quote', $this->_id) . ',
							   ' . $this->call('/DB/quote', $data) . ',
							   NOW(),
							   NOW()
						)
						ON DUPLICATE KEY
						UPDATE sesdata=VALUES(sesdata),
							   sesmodifiedts=NOW()';
			$result = $this->call('/DB/query', $query);
			if (is_object($result) && $result->errno <= 0)
			{
				$this->_updated = false;

				return true;
			}
		}

		return false;
	}

	/**
	 *  Alias for writeClose
	 *  @name    commit
	 *  @type    method
	 *  @access  public
	 *  @return  bool
	 *  @see     writeClose
	 */
	public function commit()
	{
		return $this->writeClose();
	}

	function __set($property, $value)
	{
		if ((!array_key_exists($property, $this->_property) || (array_key_exists($property, $this->_property) && $this->$property !== $value)))
			$this->_updated = true;

		parent::__set($property, $value);
	}

	function __get($property)
	{
		$this->start();

		return parent::__get($property);
	}

	public function __destruct()
	{
		if ($this->_updated)
			$this->writeClose();
	}
}
