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
		 *  @syntax  object = new Konsolidate( array path )
		 *  @note    The syntax described is the syntax the implementor of Konsolidate should use, all childnodes constructed by Konsolidate
		 *           are handled by the internals of Konsolidate.
		 */
		public function __construct( $mPath )
		{
			$this->_debug       = false;
			$this->_module      = Array();
			$this->_property    = Array();
			$this->_lookupcache = Array();
			$this->_tracelog    = Array();

			if ( is_object( $mPath ) && $mPath instanceof Konsolidate )
			{
				$this->_parent          = $mPath;
				$this->_path            = $this->getFilePath();
				$this->_objectseparator = $this->_parent->_objectseparator;
			}
			else if ( is_array( $mPath ) )  //  We are the Root instance
			{
				$this->_parent          = false;
				$this->_path            = $mPath;
				$this->_objectseparator = isset( $this->_objectseparator ) && !empty( $this->_objectseparator ) ? $this->_objectseparator : "/";

				//  We always want access to our static tools
				$this->import( "tool.class.php" );
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
		 *  @syntax  Konsolidate->get( string module [, mixed default ] );
		 *  @note    supplying a default value should be done per call, the default is never stored
		 */
		public function get()
		{
			$aArgument  = func_get_args();
			$sProperty  = array_shift( $aArgument );
			$mDefault   = (bool) count( $aArgument ) ? array_shift( $aArgument ) : null;

			$nSeperator = strrpos( $sProperty, $this->_objectseparator );
			if ( $nSeperator !== false && ( $oModule = $this->getModule( substr( $sProperty, 0, $nSeperator ) ) ) !== false )
				return $oModule->get( substr( $sProperty, $nSeperator + 1 ), $mDefault );
			else if ( $this->checkModuleAvailability( $sProperty ) )
				return $this->register( $sProperty );
			$mReturn = $this->$sProperty;
			return is_null( $mReturn ) ? $mDefault : $mReturn; // can (and will be by default!) still be null
		}

		/**
		 *  set a property in a module using a path
		 *  @name    set
		 *  @type    method
		 *  @access  public
		 *  @param   string   path to the property to set
		 *  @param   mixed    value
		 *  @return  void
		 *  @syntax  Konsolidate->set( string module, mixed value );
		 */
		public function set()
		{
			$aArgument  = func_get_args();
			$sProperty  = array_shift( $aArgument );
			$nSeperator = strrpos( $sProperty, $this->_objectseparator );
			if ( $nSeperator !== false && ( $oModule = $this->getModule( substr( $sProperty, 0, $nSeperator ) ) ) !== false )
			{
				array_unshift( $aArgument, substr( $sProperty, $nSeperator + 1 ) );
				return call_user_func_array(
					Array(
						$oModule, // the object
						"set"      // the method
					),
					$aArgument     // the arguments
				);
			}
			$mValue           = array_shift( $aArgument );
			$this->$sProperty = $mValue;
			return $this->$sProperty === $mValue;
		}

		/**
		 *  Call a method from a module and return its return value
		 *  @name    call
		 *  @type    method
		 *  @access  public
		 *  @param   string   path to the method to call
		 *  @param   mixed    [optional] argument
		 *  @return  mixed
		 *  @syntax  Konsolidate->call( string module [, mixed argument [, mixed argument, [, ... ] ] ] );
		 *  @note    One can supply as many arguments as needed
		 */
		public function call()
		{
			$aArgument  = func_get_args();
			$sCall      = array_shift( $aArgument );
			$nSeperator = strrpos( $sCall, $this->_objectseparator );

			if ( $nSeperator !== false )
			{
				$oModule = $this->getModule( substr( $sCall, 0, $nSeperator ) );
				$sMethod = substr( $sCall, $nSeperator + 1 );
			}
			else
			{
				$oModule = $this;
				$sMethod = $sCall;
			}

			if ( !is_object( $oModule ) )
			{
				$this->call( "/Log/write", "Module '" . get_class( $oModule ) . "' not found!" );
				return false;
			}

			return call_user_func_array(
				Array(
					$oModule,  // the object
					$sMethod   // the method
				),
				$aArgument     // the arguments
			);
		}

		/**
		 *  Register a (unique) sub module of the current one
		 *  @name    register
		 *  @type    method
		 *  @access  public
		 *  @param   string   modulename
		 *  @return  object
		 *  @syntax  Konsolidate->register( string module );
		 *  @note    register only create a single (unique) instance and always returns the same instance
		 *           use the instance method to create different instances of the same class
		 */
		public function register( $sModule )
		{
			$sModule = strToUpper( $sModule );
			if ( !array_key_exists( $sModule, $this->_module ) )
			{
				$oModule = $this->instance( $sModule );

				if ( $oModule === false )
					return $oModule;

				$this->_module[ $sModule ] = $oModule;
			}
			return $this->_module[ $sModule ];
		}

		/**
		 *  Create a sub module of the current one
		 *  @name    instance
		 *  @type    method
		 *  @access  public
		 *  @param   string   modulename
		 *  @param   mixed    param N
		 *  @return  object
		 *  @syntax  Konsolidate->instance( string module [, mixed param1 [, mixed param2 [, mixed param N ] ] ] );
		 *  @note    instance creates an instance every time you call it, if you require a single instance which
		 *           is always returned, use the register method
		 */
		public function instance( $sModule )
		{
			//  In case we request an instance of a remote node, we verify it here and leave the instancing to the instance parent
			$nSeperator = strrpos( $sModule, $this->_objectseparator );
			if ( $nSeperator !== false && ( $oModule = $this->getModule( substr( $sModule, 0, $nSeperator ) ) ) !== false )
			{
				$aArgument = func_get_args();
				if ( count( $aArgument ) )
				{
					$aArgument[ 0 ] = substr( $aArgument[ 0 ], $nSeperator + 1 );
					return call_user_func_array(
						Array(
							$oModule,
							"instance"
						),
						$aArgument
					);
				}
			}

			//  optimize the number of calls to import, as importing is rather expensive due to the file I/O involved
			static $aImported = Array();
			if ( !isset( $aImported[ $sModule ] ) )
			{
				$aImported[ $sModule ] = microtime(true);
				$this->import( "{$sModule}.class.php" );
			}

			//  try to construct the module classes top down, this ensures the correct order of construction
			$bConstructed = false;
			foreach ( $this->_path as $sMod=>$sPath )
			{
				$sClass  = "{$sMod}" . ucFirst( strToLower( $sModule ) );
				if ( class_exists( $sClass ) )
				{
					$aArgument = func_get_args();
					array_shift( $aArgument );  //  the first argument is always the module to instance, we discard it

					if ( (bool) count( $aArgument ) )
					{
						array_unshift( $aArgument, $this ); //  inject the 'parent reference', as Konsolidate dictates
						$oModule = new ReflectionClass( $sClass );
						$oModule = $oModule->newInstanceArgs( $aArgument );
    				}
					else
					{
						$oModule = new $sClass( $this );
					}
					$bConstructed = is_object( $oModule );
					break;
				}
			}

			if ( !$bConstructed )
			{
				//  create class stubs on the fly
				eval( "class {$sClass} extends Konsolidate{ public \$_dynamicStubClass=true; }" );
				$oModule      = new $sClass( $this );
				$bConstructed = is_object( $oModule );

				if ( !$bConstructed )
					return false;
			}

			return $oModule;
		 }

		/**
		 *  Import a file within the tree
		 *  @name    import
		 *  @type    method
		 *  @access  public
		 *  @param   string   filename
		 *  @return  object
		 *  @syntax  Konsolidate->import( string file );
		 */
		public function import( $sFile )
		{
			$nSeperator = strrpos( $sFile, $this->_objectseparator );
			if ( $nSeperator !== false && ( $oModule = $this->getModule( substr( $sFile, 0, $nSeperator ) ) ) !== false )
				return $oModule->import( substr( $sFile, $nSeperator + 1 ) );

			//  include all imported files (if they exist) bottom up, this solves the implementation classes having to know core paths
			$aIncluded = array_flip( get_included_files() );
			$aPath     = array_reverse( $this->_path, true );
			$bImported = false;
			foreach ( $aPath as $sPath )
			{
				$sCurrentFile = "{$sPath}/" . strToLower( $sFile );
				if ( isset( $aIncluded[ $sCurrentFile ] ) )
				{
					$bImported = true;
				}
				else if ( realpath( $sCurrentFile ) )
				{
					include( $sCurrentFile );
					$bImported = true;
				}
			}
			return $bImported;
		}

		/**
		 *  Verify whether given module exists (either for real, or as required stub as per directory structure)
		 *  @name    import
		 *  @type    method
		 *  @access  public
		 *  @param   string   module
		 *  @return  object
		 *  @syntax  Konsolidate->checkModuleAvailability( string module );
		 */
		public function checkModuleAvailability( $sModule )
		{
			$sModule = strtolower($sModule);
			$sClass  = get_class($this);

			//  lookahead to submodules
			if (!isset(self::$_modulecheck[$sClass]))
				$this->_indexModuleAvailability();

			//  if we are dealing with a submodule pattern which is not in our cache by default, test for it
			if (strpos($sModule, $this->_objectseparator) !== false)
				foreach ( $this->_path as $sMod=>$sPath )
					if ( realpath( "{$sPath}/{$sModule}.class.php" ) || realpath( "{$sPath}/{$sModule}" ) )
					{
						self::$_modulecheck[$sClass][$sModule] = true;
						break;
					}

			return isset(self::$_modulecheck[$sClass][$sModule]) ? self::$_modulecheck[$sClass][$sModule] : false;
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
			if ( $this->_parent !== false )
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
			if ( $this->_parent !== false )
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
			if ( is_array( $this->_path ) )
			{
				return $this->_path;
			}
			else
			{
				$aParentPath = $this->_parent->getFilePath();
				$sClass      = str_replace( array_keys( $aParentPath ), "", get_class( $this ) );
				$aPath       = Array();
				foreach ( $aParentPath as $sTier=>$sPath )
				{
					$sClassPath = $sPath . "/" . strToLower( $sClass );
					if ( realpath( $sClassPath ) )
						$aPath[ "{$sTier}{$sClass}" ] = $sClassPath;
				}
				return $aPath;
			}
		}

		/**
		 *  Get a reference to a module based on it's path
		 *  @name    getModule
		 *  @type    method
		 *  @access  public
		 *  @param   string  module path
		 *  @return  mixed
		 *  @syntax  Konsolidate->getModule( string path );
		 */
		public function getModule( $sCall )
		{
			$sPath = strToUpper( $sCall );
			if ( !array_key_exists( $sPath, $this->_lookupcache ) )
			{
				$aPath   = explode( $this->_objectseparator, $sPath );
				$oModule = $this;
				while( is_object( $oModule ) && count( $aPath ) )
				{
					$sSegment = array_shift( $aPath );
					switch( strToLower( $sSegment ) )
					{
						case "":        //  root
						case "_root":
							$oTraverse = $oModule->getRoot();
							break;
						case "..":      //  parent
						case "_parent": //
							$oTraverse = $oModule->getParent();
							break;
						case ".":       //  self
							$oTraverse = $this;
							break;
						default:        //  child
							$oTraverse = $oModule->register( $sSegment );
							break;
					}

					if ( !is_object( $oTraverse ) )
					{
						$this->call( "/Log/write", "Module '{$sSegment}' not found in module " . get_class( $oModule ) . "!", 3 );
						return $oTraverse;
					}

					$oModule = $oTraverse;
				}

				$this->_lookupcache[ $sPath ] = $oModule;
			}
			return $this->_lookupcache[ $sPath ];
		}

		public function getTopAuthoredClass()
		{
			if ( property_exists( $this, "_dynamicStubClass" ) )
				return $this->call( "../getTopAuthoredClass" );
			return get_class( $this );
		}

		//  Iterator functionality

		public function key()
		{
			return key( $this->_property );
		}

		public function current()
		{
			return current( $this->_property );
		}

		public function next()
		{
			return next( $this->_property );
		}

		public function rewind()
		{
			return reset( $this->_property );
		}

		public function valid()
		{
			return !is_null( $this->key() );
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
		 *  @syntax  Konsolidate->exception( [ string message [, int code ] ] );
		 *  @note    Exception classes must be an extend of PHP's built-in Exception class, if the exception method is called and the calling module does not
		 *           have an exception class, Konsolidate will generate one dynamically.
		 *  @note    Exception classname use the same structure as normal Konsolidated classnames, but they must omit the tiername, e.g. you have a module
		 *           'Example' in the tier 'Demo' (class DemoExample in example.class.php), its exception class name should be (or will be generated dynamically)
		 *           'ExampleException' and be located in the file example/exception.class.php.
		 */
		public function exception( $sMessage=null, $nCode=0 )
		{
			$this->import( "exception.class.php" );

			$sThrowClass     = str_replace( array_keys( $this->getRoot()->getFilePath() ), "", get_class( $this ) . "Exception" );
			$sExceptionClass = "";
			foreach ( $this->_path as $sMod=>$sPath )
			{
				$sClass  = "{$sMod}Exception";
				if ( class_exists( $sClass ) )
				{
					$sExceptionClass = $sClass;
					break;
				}
			}

			$aTrace = debug_backtrace();
			$sFile  = "";
			$sLine  = "";
			if ( count( $aTrace ) >= 4 && isset( $aTrace[ 3 ] ) )
			{
				//  The origin of species
				$sFile = $aTrace[ 3 ][ "file" ];
				$sLine = $aTrace[ 3 ][ "line" ];
			}

			if ( empty( $sExceptionClass ) )
				$sExceptionClass = "Exception";

			//  Create tierless Exception on the fly if the requested Exception does not exist
			if ( !class_exists( $sThrowClass ) )
			{
				eval( "class {$sThrowClass} extends {$sExceptionClass}{public function __construct(\$s=null,\$c=0){parent::__construct(\$s,\$c);\$this->file='{$sFile}';\$this->line= (int) '{$sLine}';}}" );
			}

			if ( class_exists( $sThrowClass ) )
				throw new $sThrowClass( $sMessage, $nCode );
			throw new Exception( $sMessage, $nCode );
		}


		// Magic methods.
		public function __set( $sProperty, $mValue )
		{
			if ( array_key_exists( strToUpper( $sProperty ), $this->_module ) )
				throw new Exception( "Trying to overwrite existing module {$sProperty} in " . get_class( $this ) . " with " . gettype( $mValue ) . " {$mValue}" );
			else if ( $this->checkModuleAvailability( $sProperty ) )
				throw new Exception( "Trying to set a property " . gettype( $mValue ) . " {$mValue} in " . get_class( $this ) . " where a module is available" );
			$this->_property[ $sProperty ] = $mValue;
		}

		public function __get( $sProperty )
		{
			if ( $sProperty == "modules" )
				return $this->_module;
			else if ( array_key_exists( $sProperty, $this->_property ) )
				return $this->_property[ $sProperty ];
			else if ( array_key_exists( strToUpper( $sProperty ), $this->_module ) )
				return $this->_module[ strToUpper( $sProperty ) ];
			else if ( $this->checkModuleAvailability( $sProperty ) )
				return $this->get( $sProperty );
			return null;
		}

		public function __call( $sMethod, $aArgument )
		{
			$sSelf        = get_class( $this );
			$sTopAuthored = $this->getTopAuthoredClass();
			$sMessage     = "Call to unknown method '{$sSelf}::{$sMethod}'" . ( $sTopAuthored != $sSelf ? ", nearest authored class is '{$sTopAuthored}'" : "" );
			$this->call( "/Log/write", $sMessage, 0 );
			throw new Exception( $sMessage );
			return false;
		}

		/**
		 *  Allow modules to be called as 'call' methods
		 *  @name    __invoke
		 *  @type    method
		 *  @access  public
		 *  @param   mixed   arg N
		 *  @return  mixed
		 *  @syntax  Konsolidate( [ mixed arg N ] );
		 *  @note    __invoke only works in PHP 5.3+
		 *  @note    You can now effectively leave out the '->call' part when calling on methods, e.g. $oK( "/DB/query", "SHOW TABLES" ) instead of $oK->call( "/DB/query", "SHOW TABLES" );
		 *  @see     call
		 */
		public function __invoke()
		{
			return call_user_func_array(
				Array(
					$this,       // the object
					"call"       // the method
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
		public function __isset( $sProperty )
		{
			return isset($this->_property[ $sProperty ]);
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
		public function __unset( $sProperty )
		{
			unset($this->_property[ $sProperty ]);
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
			$sReturn  = "<div style='font-family:\"Lucida Grande\", Verdana, Arial, sans-serif;font-size:11px;color'>";
			$sReturn .= "<h3 style='margin:0;padding:0;'>" . get_class( $this ) . "</h3>\n";
			if ( count( $this->_property ) )
			{
				$sReturn .= "<div style='color:#400;'>\n";
				$sReturn .= "<em>Custom properties</em>\n";
				$sReturn .= "<ul>";
				foreach( $this->_property as $sKey=>$mValue )
					if ( is_object( $mValue ) )
						$sReturn .= " <li>{$sKey}\t= (object " . get_class( $mValue ) . ")</li>\n";
					else if ( is_array( $mValue ) )
						$sReturn .= " <li>{$sKey}\t= (array)</li>\n";
					else if ( is_bool( $mValue ) )
						$sReturn .= " <li>{$sKey}\t= (bool) " . ( $mValue ? "true" : "false" ) . "</li>\n";
					else
						$sReturn .= " <li>{$sKey}\t= (" . gettype( $mValue ) . ") {$mValue}</li>\n";
				$sReturn .= "</ul>";
				$sReturn .= "</div>";
			}
			if ( count( $this->_module ) )
			{
				$sReturn .= "<strong>Modules</strong>\n";
				$sReturn .= "<ul>";
				foreach( $this->_module as $sKey=>$mValue )
					$sReturn .= " <li style='list-style-type:square;'>{$sKey}\n<br />{$mValue}</li>";
				$sReturn .= "</ul>";
			}
			$sReturn .= "</div>";

			return $sReturn;
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
							$list[strtolower(basename($item, '.class.php'))] = true;
				self::$_modulecheck[$class] = $list;
			}
		}
	}

?>
