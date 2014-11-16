<?php

/*
 *            ________ ___
 *           /   /   /\  /\       Konsolidate
 *      ____/   /___/  \/  \
 *     /           /\      /      http://www.konsolidate.nl
 *    /___     ___/  \    /
 *    \  /   /\   \  /    \
 *     \/___/  \___\/      \
 *      \   \  /\   \  /\  /
 *       \___\/  \___\/  \/
 *         \          \  /        $Rev$
 *          \___    ___\/         $Author$
 *              \   \  /          $Date$
 *               \___\/
 *
 *  The konsolidate class, which acts as the 'one ring' being responsible for the proper inner workings of the Konsolidate framework/library/foundation
 *  @name   Konsolidate
 *  @type   class
 *  @author Rogier Spieker <rogier@konsolidate.nl>
 */
class Konsolidate implements Iterator
{
	/**
	 *  The parent Konsolidate object
	 *  @name    _parent
	 *  @type    object
	 *  @access  protected
	 */
	protected $_parent;

	/**
	 *  Run in debug mode
	 *  @name    _debug
	 *  @type    object
	 *  @access  protected
	 */
	protected $_debug;

	/**
	 *  The array in which all modules (plugins) are stored for re-use
	 *  @name    _module
	 *  @type    array
	 *  @access  protected
	 */
	protected $_module;

	/**
	 *  The array in which all custom properties are stored
	 *  @name    _property
	 *  @type    array
	 *  @access  protected
	 */
	protected $_property;

	/**
	 *  The array in which all modulepaths relative to the current instance are stored
	 *  @name    _path
	 *  @type    array
	 *  @access  protected
	 */
	protected $_path;

	/**
	 *  The character(s) which seperates objects (and the final method) in the call method
	 *  @name    _path
	 *  @type    array
	 *  @access  protected
	 */
	protected $_objectseparator;

	/**
	 *  Module reference cache, making lookups faster
	 *  @name    _lookupcache
	 *  @type    array
	 *  @access  protected
	 */
	protected $_lookupcache;

	/**
	 *  Module lookup cache, making lookups faster for checkModuleAvailability
	 *  @name    _modulecheck
	 *  @type    array
	 *  @access  protected
	 */
	static protected $_modulecheck;

	/**
	 *  Error traces
	 *  @name    _tracelog
	 *  @type    array
	 *  @access  protected
	 */
	protected $_tracelog;


	/**
	 *  Konsolidate constructor
	 *  @name    Konsolidate
	 *  @type    constructor
	 *  @access  public
	 *  @param   array   array with the paths to load the modules from (the order of the paths is the order in which to look up modules)
	 *  @return  object
	 *  @syntax  object = new Konsolidate(array path)
	 *  @note    The syntax described is the syntax the implementor of Konsolidate should use, all childnodes constructed by Konsolidate
	 *           are handled by the internals of Konsolidate.
	 */
	public function __construct($path)
	{
		$this->_debug       = false;
		$this->_module      = Array();
		$this->_property    = Array();
		$this->_lookupcache = Array();
		$this->_tracelog    = Array();

		if (is_object($path) && $path instanceof Konsolidate)
		{
			$this->_parent          = $path;
			$this->_path            = $this->getFilePath();
			$this->_objectseparator = $this->_parent->_objectseparator;
		}
		else if (is_array($path))  //  We are the Root instance
		{
			$this->_parent          = false;
			$this->_path            = $path;
			$this->_objectseparator = isset($this->_objectseparator) && !empty($this->_objectseparator) ? $this->_objectseparator : '/';

			//  We always want access to our static tools
			$this->import('tool.php');
		}
	}

	/**
	 *  get a property value from a module using a path
	 *  @name    get
	 *  @type    method
	 *  @access  public
	 *  @param   string   path to the property to get
	 *  @param   mixed    default return value (optional, default null)
	 *  @return  mixed
	 *  @syntax  Konsolidate->get(string module [, mixed default]);
	 *  @note    supplying a default value should be done per call, the default is never stored
	 */
	public function get()
	{
		$args     = func_get_args();
		$property = array_shift($args);
		$default  = (bool) count($args) ? array_shift($args) : null;
		$seperator = strrpos($property, $this->_objectseparator);

		if ($seperator !== false && ($instance = $this->getModule(substr($property, 0, $seperator))) !== false)
			return $instance->get(substr($property, $seperator + 1), $default);
		else if ($this->checkModuleAvailability($property))
			return $this->register($property);
		$return = $this->$property;
		return is_null($return) ? $default : $return; // can (and will be by default!) still be null
	}

	/**
	 *  set a property in a module using a path
	 *  @name    set
	 *  @type    method
	 *  @access  public
	 *  @param   string   path to the property to set
	 *  @param   mixed    value
	 *  @return  void
	 *  @syntax  Konsolidate->set(string module, mixed value);
	 */
	public function set()
	{
		$args  = func_get_args();
		$property  = array_shift($args);
		$seperator = strrpos($property, $this->_objectseparator);
		if ($seperator !== false && ($instance = $this->getModule(substr($property, 0, $seperator))) !== false)
		{
			array_unshift($args, substr($property, $seperator + 1));
			return call_user_func_array(
				Array(
					$instance, // the object
					'set'      // the method
				),
				$args     // the arguments
			);
		}
		$value           = array_shift($args);
		$this->$property = $value;
		return $this->$property === $value;
	}

	/**
	 *  Call a method from a module and return its return value
	 *  @name    call
	 *  @type    method
	 *  @access  public
	 *  @param   string   path to the method to call
	 *  @param   mixed    [optional] argument
	 *  @return  mixed
	 *  @syntax  Konsolidate->call(string module [, mixed argument [, mixed argument, [, ...]]]);
	 *  @note    One can supply as many arguments as needed
	 */
	public function call()
	{
		$args      = func_get_args();
		$call      = array_shift($args);
		$seperator = strrpos($call, $this->_objectseparator);

		if ($seperator !== false)
		{
			$instance = $this->getModule(substr($call, 0, $seperator));
			$method   = substr($call, $seperator + 1);
		}
		else
		{
			$instance = $this;
			$method   = $call;
		}

		if (!is_object($instance))
		{
			$this->call('/Log/write', 'Module \'' . get_class($instance) . '\' not found!');
			return false;
		}

		return call_user_func_array(
			Array(
				$instance,  // the object
				$method   // the method
			),
			$args     // the arguments
		);
	}

	/**
	 *  Register a (unique) sub module of the current one
	 *  @name    register
	 *  @type    method
	 *  @access  public
	 *  @param   string   modulename
	 *  @return  object
	 *  @syntax  Konsolidate->register(string module);
	 *  @note    register only create a single (unique) instance and always returns the same instance
	 *           use the instance method to create different instances of the same class
	 */
	public function register($module)
	{
		$module = strToUpper($module);
		if (!array_key_exists($module, $this->_module))
		{
			$instance = $this->instance($module);

			if ($instance === false)
				return $instance;

			$this->_module[$module] = $instance;
		}
		return $this->_module[$module];
	}

	/**
	 *  Create a sub module of the current one
	 *  @name    instance
	 *  @type    method
	 *  @access  public
	 *  @param   string   modulename
	 *  @param   mixed    param N
	 *  @return  object
	 *  @syntax  Konsolidate->instance(string module [, mixed param1 [, mixed param2 [, mixed param N]]]);
	 *  @note    instance creates an instance every time you call it, if you require a single instance which
	 *           is always returned, use the register method
	 */
	public function instance($module)
	{
		//  In case we request an instance of a remote node, we verify it here and leave the instancing to the instance parent
		$seperator = strrpos($module, $this->_objectseparator);
		if ($seperator !== false && ($instance = $this->getModule(substr($module, 0, $seperator))) !== false)
		{
			$args = func_get_args();
			if (count($args))
			{
				$args[0] = substr($args[0], $seperator + 1);
				return call_user_func_array(
					Array(
						$instance,
						'instance'
					),
					$args
				);
			}
		}

		//  optimize the number of calls to import, as importing is rather expensive due to the file I/O involved
		static $imports = Array();
		if (!isset($imports[$module]))
		{
			$imports[$module] = microtime(true);
			$this->import($module . '.php');
		}

		//  try to construct the module classes top down, this ensures the correct order of construction
		$constructed = false;
		foreach ($this->_path as $name=>$path)
		{
			$className  = $name . ucFirst(strToLower($module));
			if (class_exists($className))
			{
				$args = func_get_args();
				array_shift($args);  //  the first argument is always the module to instance, we discard it

				if ((bool) count($args))
				{
					array_unshift($args, $this); //  inject the 'parent reference', as Konsolidate dictates
					$instance = new ReflectionClass($className);
					$instance = $instance->newInstanceArgs($args);
				}
				else
				{
					$instance = new $className($this);
				}
				$constructed = is_object($instance);
				break;
			}
		}

		if (!$constructed)
		{
			//  create class stubs on the fly
			eval('class ' . $className . ' extends Konsolidate{public $_dynamicStubClass=true;}');
			$instance    = new $className($this);
			$constructed = is_object($instance);

			if (!$constructed)
				return false;
		}

		return $instance;
	}

	/**
	 *  Import a file within the tree
	 *  @name    import
	 *  @type    method
	 *  @access  public
	 *  @param   string   filename
	 *  @return  object
	 *  @syntax  Konsolidate->import(string file);
	 */
	public function import($file)
	{
		$seperator = strrpos($file, $this->_objectseparator);
		if ($seperator !== false && ($instance = $this->getModule(substr($file, 0, $seperator))) !== false)
			return $instance->import(substr($file, $seperator + 1));

		//  include all imported files (if they exist) bottom up, this solves the implementation classes having to know core paths
		$compatible = strpos($file, '.class.php') ? str_replace('.class.php', '.php', $file) : str_replace('.php', '.class.php', $file);
		$included   = array_flip(get_included_files());
		$pathList   = array_reverse($this->_path, true);
		$imported   = false;

		foreach ($pathList as $path)
		{
			$currentFile   = $path . '/' . strToLower($file);
			$currentCompat = $path . '/' . strToLower($compatible);
			if (isset($included[$currentFile]) || isset($included[$currentCompat]))
			{
				$imported = true;
			}
			else if (realpath($currentFile))
			{
				include($currentFile);
				$imported = true;
			}
			else if (realpath($currentCompat))
			{
				include($currentCompat);
				$imported = true;
			}
		}

		return $imported;
	}

	/**
	 *  Verify whether given module exists (either for real, or as required stub as per directory structure)
	 *  @name    import
	 *  @type    method
	 *  @access  public
	 *  @param   string   module
	 *  @return  object
	 *  @syntax  Konsolidate->checkModuleAvailability(string module);
	 */
	public function checkModuleAvailability($module)
	{
		$module = strtolower($module);
		$className  = get_class($this);

		//  lookahead to submodules
		if (!isset(self::$_modulecheck[$className]))
			$this->_indexModuleAvailability();

		//  if we are dealing with a submodule pattern which is not in our cache by default, test for it
		if (strpos($module, $this->_objectseparator) !== false)
			foreach ($this->_path as $name=>$path)
				if (realpath($path . '/' . $module . '.php') || realpath($path . '/' . $module))
				{
					self::$_modulecheck[$className][$module] = true;
					break;
				}

		return isset(self::$_modulecheck[$className][$module]) ? self::$_modulecheck[$className][$module] : false;
	}


	/**
	 *  Get the root node
	 *  @name    getRoot
	 *  @type    method
	 *  @access  public
	 *  @return  mixed
	 *  @syntax  Konsolidate->getRoot();
	 */
	public function getRoot()
	{
		if ($this->_parent !== false)
			return $this->_parent->getRoot();
		return $this;
	}

	/**
	 *  Get the parent node, if any
	 *  @name    getParent
	 *  @type    method
	 *  @access  public
	 *  @return  mixed
	 *  @syntax  Konsolidate->getParent();
	 */
	function getParent()
	{
		if ($this->_parent !== false)
			return $this->_parent;
		return false;
	}

	/**
	 *  Get the file path based on the location in the Konsolidate Tree
	 *  @name    getFilePath
	 *  @type    method
	 *  @access  public
	 *  @return  mixed
	 *  @syntax  Konsolidate->getFilePath();
	 */
	public function getFilePath()
	{
		if (is_array($this->_path))
		{
			return $this->_path;
		}
		else
		{
			$parentPath = $this->_parent->getFilePath();
			$className  = str_replace(array_keys($parentPath), '', get_class($this));
			$pathList   = Array();

			foreach ($parentPath as $tier=>$path)
			{
				$classPath = $path . '/' . strToLower($className);
				if (realpath($classPath))
					$pathList[$tier . $className] = $classPath;
			}

			return $pathList;
		}
	}

	/**
	 *  Get a reference to a module based on it's path
	 *  @name    getModule
	 *  @type    method
	 *  @access  public
	 *  @param   string  module path
	 *  @return  mixed
	 *  @syntax  Konsolidate->getModule(string path);
	 */
	public function getModule($call)
	{
		$path = strToUpper($call);
		if (!array_key_exists($path, $this->_lookupcache))
		{
			$pathList   = explode($this->_objectseparator, $path);
			$instance = $this;
			while(is_object($instance) && count($pathList))
			{
				$segment = array_shift($pathList);
				switch (strToLower($segment))
				{
					case '':        //  root
					case '_root':
						$traverse = $instance->getRoot();
						break;
					case '..':      //  parent
					case '_parent': //
						$traverse = $instance->getParent();
						break;
					case '.':       //  self
						$traverse = $this;
						break;
					default:        //  child
						$traverse = $instance->register($segment);
						break;
				}

				if (!is_object($traverse))
				{
					$this->call('/Log/write', 'Module \'' . $segment . '\' not found in module ' . get_class($instance) . '!', 3);
					return $traverse;
				}

				$instance = $traverse;
			}

			$this->_lookupcache[$path] = $instance;
		}
		return $this->_lookupcache[$path];
	}

	public function getTopAuthoredClass()
	{
		if (property_exists($this, '_dynamicStubClass'))
			return $this->call('../getTopAuthoredClass');
		return get_class($this);
	}

	//  Iterator functionality

	public function key()
	{
		return key($this->_property);
	}

	public function current()
	{
		return current($this->_property);
	}

	public function next()
	{
		return next($this->_property);
	}

	public function rewind()
	{
		return reset($this->_property);
	}

	public function valid()
	{
		return !is_null($this->key());
	}

	//  End Iterator functionality



	/**
	 *  Throw exceptions
	 *  @name    exception
	 *  @type    method
	 *  @access  public
	 *  @param   string  message (option)
	 *  @param   int     code (option)
	 *  @return  void
	 *  @syntax  Konsolidate->exception([string message [, int code]]);
	 *  @note    Exception classes must be an extend of PHP's built-in Exception class, if the exception method is called and the calling module does not
	 *           have an exception class, Konsolidate will generate one dynamically.
	 *  @note    Exception classname use the same structure as normal Konsolidated classnames, but they must omit the tiername, e.g. you have a module
	 *           'Example' in the tier 'Demo' (class DemoExample in example.php), its exception class name should be (or will be generated dynamically)
	 *           'ExampleException' and be located in the file example/exception.php.
	 */
	public function exception($message=null, $code=0)
	{
		$this->import('exception.php');

		$throwClass     = str_replace(array_keys($this->getRoot()->getFilePath()), '', get_class($this) . 'Exception');
		$exceptionClass = '';
		foreach ($this->_path as $name=>$path)
		{
			$className  = $name . 'Exception';
			if (class_exists($className))
			{
				$exceptionClass = $className;
				break;
			}
		}

		$trace = debug_backtrace();
		$file  = '';
		$line  = '';
		if (count($trace) >= 4 && isset($trace[3]))
		{
			//  The origin of species
			$file = $trace[3]['file'];
			$line = $trace[3]['line'];
		}

		if (empty($exceptionClass))
			$exceptionClass = 'Exception';

		//  Create tierless Exception on the fly if the requested Exception does not exist
		if (!class_exists($throwClass))
		{
			eval('class ' . $throwClass . ' extends ' . $exceptionClass . '{public function __construct($s=null,$c=0){parent::__construct($s,$c);$this->file=\'' . $file . '\';$this->line= (int) \'' . $line . '\';}}');
		}

		if (class_exists($throwClass))
			throw new $throwClass($message, $code);

		throw new Exception($message, $code);
	}


	// Magic methods.
	public function __set($property, $value)
	{
		if (array_key_exists(strToUpper($property), $this->_module))
			throw new Exception('Trying to overwrite existing module ' . $property . ' in ' . get_class($this) . ' with ' . gettype($value) . ' ' . $value);
		else if ($this->checkModuleAvailability($property))
			throw new Exception('Trying to set a property ' . gettype($value) . ' ' . $value . ' in ' . get_class($this) . ' where a module is available');
		$this->_property[$property] = $value;
	}

	public function __get($property)
	{
		if ($property == 'modules')
			return $this->_module;
		else if (array_key_exists($property, $this->_property))
			return $this->_property[$property];
		else if (array_key_exists(strToUpper($property), $this->_module))
			return $this->_module[strToUpper($property)];
		else if ($this->checkModuleAvailability($property))
			return $this->get($property);
		return null;
	}

	public function __call($method, $args)
	{
		$self        = get_class($this);
		$topAuthored = $this->getTopAuthoredClass();
		$message     = 'Call to unknown method \'' . $self . '::' . $method . '\'' . ($topAuthored != $self ? ', nearest authored class is \'' . $topAuthored . '\'' : '');
		$this->call('/Log/write', $message, 0);
		throw new Exception($message);

		return false;
	}

	/**
	 *  Allow modules to be called as 'call' methods
	 *  @name    __invoke
	 *  @type    method
	 *  @access  public
	 *  @param   mixed   arg N
	 *  @return  mixed
	 *  @syntax  Konsolidate([mixed arg N]);
	 *  @note    __invoke only works in PHP 5.3+
	 *  @note    You can now effectively leave out the '->call' part when calling on methods, e.g. $K('/DB/query', 'SHOW TABLES') instead of $K->call('/DB/query', 'SHOW TABLES');
	 *  @see     call
	 */
	public function __invoke()
	{
		return call_user_func_array(
			Array(
				$this,       // the object
				'call'       // the method
			),
			func_get_args()  // the arguments
		);
	}

	/**
	 *  Allow isset/empty tests on inaccessible properties
	 *  @name    __isset
	 *  @type    method
	 *  @access  public
	 *  @param   string property
	 *  @return  bool isset
	 *  @syntax  isset(Konsolidate->property), empty(Konsolidate->property);
	 *  @note    __isset only works in PHP 5.1+
	 */
	public function __isset($property)
	{
		return isset($this->_property[$property]);
	}

	/**
	 *  Allow unsetting of inaccessible properties
	 *  @name    __unset
	 *  @type    method
	 *  @access  public
	 *  @param   string property
	 *  @syntax  unset(Konsolidate->property);
	 *  @note    __unset only works in PHP 5.1+
	 */
	public function __unset($property)
	{
		unset($this->_property[$property]);
	}

	/**
	 *  Create a string representing the Konsolidate instance
	 *  @name    __toString
	 *  @type    method
	 *  @access  public
	 *  @syntax  print Konsolidate();
	 */
	public function __toString()
	{
		$return = [
			'<div style="font-family:\'Lucida Grande\', Verdana, Arial, sans-serif;font-size:11px;">',
			'<h3 style="margin:0;padding:0;">' . get_class($this) . '</h3>',
		];

		if (count($this->_property))
		{
			$return[] = '<div style="color:#400;"><em>Custom properties</em><ul>';

			foreach ($this->_property as $key=>$value)
			{
				$type = gettype($value);
				if ($type === 'object')
					$type = get_class($value);

				$return[] = '<li><code>' . $key . '</code> = (' . $type . ') <code>' . var_export($value, true) . '</code></li>';
			}

			$return[] = '</ul></div>';
		}

		if (count($this->_module))
		{
			$return[] = '<strong>Modules</strong><ul>';

			foreach ($this->_module as $key=>$value)
				$return[] = ' <li style="list-style-type:square;">' . $key . '<br />' . $value . '</li>';

			$return[] = '</ul>';
		}
		$return[] = '</div>';

		return join('', $return);
	}

	/**
	 *  Look ahead at all available submodules and cache the availability
	 *  @name    _indexModuleAvailability
	 *  @type    method
	 *  @access  protected
	 *  @returns void
	 *  @syntax  Konsolidate->_indexModuleAvailability();
	 */
	protected function _indexModuleAvailability()
	{
		if (!is_array(self::$_modulecheck))
			self::$_modulecheck = Array();

		$class = get_class($this);
		if (!isset(self::$_modulecheck[$class]))
		{
			$list = Array();
			if (is_array($this->_path))
				foreach ($this->_path as $tier=>$path)
					foreach (glob($path . '/*') as $item)
						$list[strtolower(basename($item, '.php'))] = true;
			self::$_modulecheck[$class] = $list;
		}
	}
}
