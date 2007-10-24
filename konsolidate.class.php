<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	 *  @author Rogier Spieker <rogier@klof.net>
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
		protected $_objectseperator;

		/**
		 *  Module reference cache, making lookups faster
		 *  @name    _lookupcache
		 *  @type    array
		 *  @access  protected
		 */
		protected $_lookupcache;

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
		 *  @returns object
		 *  @syntax  object = &new Konsolidate( array path )
		 *  @note    The syntax described is the syntax the implementor of Konsolidate should use, all childnodes constructed by Konsolidate
		 *           are handled by the internals of Konsolidate.
		 */
		public function __construct( &$mPath )
		{
			$this->_debug       = true;
			$this->_module      = Array();
			$this->_property    = Array();
			$this->_lookupcache = Array();
			$this->_tracelog    = Array();
			
			if ( is_object( $mPath ) && ( is_subclass_of( $mPath, "Konsolidate" ) || $mPath instanceof Konsolidate ) )
			{
				$this->_parent          = &$mPath;
				$this->_path            = $this->getFilePath();
				$this->_objectseperator = $this->_parent->_objectseperator;
			}
			else if ( is_array( $mPath ) )  //  We are the Root instance
			{
				$this->_parent          = false;
				$this->_path            = &$mPath;
				$this->_objectseperator = isset( $this->_objectseperator ) && !empty( $this->_objectseperator ) ? $this->_objectseperator : "/";
	
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
		 *  @returns mixed
		 *  @syntax  Konsolidate->get( string module [, mixed default ] );
		 *  @note    supplying a default value should be done per call, the default is never stored
		 */
		public function get( $sProperty, $mDefault=null )
		{
			$nSeperator = strrpos( $sProperty, $this->_objectseperator );
			if ( $nSeperator !== false && ( $oModule = &$this->getModule( substr( $sProperty, 0, $nSeperator ) ) ) !== false )
				return $oModule->get( substr( $sProperty, $nSeperator + 1 ), $mDefault );
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
		 *  @returns void
		 *  @syntax  Konsolidate->set( string module, mixed value );
		 */
		public function set()
		{
			$aArgument  = func_get_args();
			$sProperty  = array_shift( $aArgument );
			$nSeperator = strrpos( $sProperty, $this->_objectseperator );
			if ( $nSeperator !== false && ( $oModule = &$this->getModule( substr( $sProperty, 0, $nSeperator ) ) ) !== false )
			{
				array_unshift( $aArgument, substr( $sProperty, $nSeperator + 1 ) );
				return call_user_func_array( 
					Array( 
						&$oModule, // the object
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
		 *  @returns mixed
		 *  @syntax  Konsolidate->call( string module [, mixed argument [, mixed argument, [, ... ] ] ] );
		 *  @note    One can supply as many arguments as needed
		 */
		public function call()
		{
			$aArgument  = func_get_args();
			$sCall      = array_shift( $aArgument );
			$nSeperator = strrpos( $sCall, $this->_objectseperator );

			if ( $nSeperator !== false )
			{
				$oModule = &$this->getModule( substr( $sCall, 0, $nSeperator ) );
				$sMethod = substr( $sCall, $nSeperator + 1 );
			}
			else
			{
				$oModule = &$this;
				$sMethod = $sCall;
			}

			if ( !is_object( $oModule ) )
			{
				$this->call( "/Log/write", "Module '" . get_class( $oModule ) . "' not found!" );
				return false;
			}

			return call_user_func_array(
				Array( 
					&$oModule, // the object
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
		 *  @returns &object
		 *  @syntax  &Konsolidate->register( string module );
		 *  @note    register only create a single (unique) instance and always returns the same instance
		 *           use the instance method to create different instances of the same class
		 */
		public function &register( $sModule )
		{
			$sModule = strToUpper( $sModule );
			if ( !array_key_exists( $sModule, $this->_module ) )
			{
				$oModule = &$this->instance( $sModule );

				if ( $oModule === false )
					return $oModule;

				$this->_module[ $sModule ] = &$oModule;
			}
			return $this->_module[ $sModule ];
		}

		/**
		 *  Create a sub module of the current one
		 *  @name    instance
		 *  @type    method
		 *  @access  public
		 *  @param   string   modulename
		 *  @returns &object
		 *  @syntax  &Konsolidate->instance( string module );
		 *  @note    instance creates an instance every time you call it, if you require a single instance which
		 *           is always returned, use the register method
		 */
		public function &instance( $sModule )
		{
			//  In case we request an instance of a remote node, we verify it here and leave the instancing to the instance parent
			$nSeperator = strrpos( $sModule, $this->_objectseperator );
			if ( $nSeperator !== false && ( $oModule = &$this->getModule( substr( $sModule, 0, $nSeperator ) ) ) !== false )
				return $oModule->instance( substr( $sModule, $nSeperator + 1 ) );

			$this->import( "{$sModule}.class.php" );

			//  try to construct the module classes top down, this ensures the correct order of construction
			$bConstructed = false;
			foreach ( $this->_path as $sMod=>$sPath )
			{
				$sClass  = "{$sMod}" . ucFirst( strToLower( $sModule ) );
				if ( class_exists( $sClass ) )
				{
					$oModule      = new $sClass( $this );
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
				if ( $bConstructed )
					$this->call( "/Log/write", "class '{$sClass}' not found in module " . get_class( $this ) . ", dynamic stub created", 0 );
				else
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
		 *  @returns &object
		 *  @syntax  &Konsolidate->import( string file );
		 */
		public function import( $sFile )
		{
			$nSeperator = strrpos( $sFile, $this->_objectseperator );
			if ( $nSeperator !== false && ( $oModule = &$this->getModule( substr( $sFile, 0, $nSeperator ) ) ) !== false )
				return $oModule->import( substr( $sFile, $nSeperator + 1 ) );

			//  include all imported files (if they exist) bottom up, this solves the implementation classes having to know core paths
			$aPath     = array_reverse( $this->_path, true );
			$bImported = false;
			foreach ( $aPath as $sPath )
			{
				$sCurrentFile = "{$sPath}/" . strToLower( $sFile );
				if ( file_exists( $sCurrentFile ) )
				{
					include_once( $sCurrentFile );
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
		 *  @returns &object
		 *  @syntax  &Konsolidate->checkModuleAvailability( string module );
		 */
		public function checkModuleAvailability( $sProperty )
		{
			$sProperty = strToLower( $sProperty );
			foreach ( $this->_path as $sMod=>$sPath )
				if ( file_exists( "{$sPath}/{$sProperty}.class.php" ) || is_dir( "{$sPath}/{$sProperty}" ) )
					return true;
			return false;
		}


		/**
		 *  Get the root node
		 *  @name    getRoot
		 *  @type    method
		 *  @access  public
		 *  @returns mixed
		 *  @syntax  &Konsolidate->getRoot();
		 */
		public function &getRoot()
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
		 *  @returns mixed
		 *  @syntax  &Konsolidate->getParent();
		 */
		function &getParent()
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
		 *  @returns mixed
		 *  @syntax  &Konsolidate->getFilePath();
		 */
		public function &getFilePath()
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
					$aPath[ "{$sTier}{$sClass}" ] = $sPath . "/" . strToLower( $sClass );
				return $aPath;
			}
		}

		/**
		 *  Get a reference to a module based on it's path
		 *  @name    getModule
		 *  @type    method
		 *  @access  public
		 *  @param   string  module path
		 *  @returns mixed
		 *  @syntax  &Konsolidate->getModule( string path );
		 */
		public function &getModule( $sCall )
		{
			$sPath = strToUpper( $sCall );
			if ( !array_key_exists( $sPath, $this->_lookupcache ) )
			{
				$aPath   = explode( $this->_objectseperator, $sPath );
				$oModule = &$this;
				while( is_object( $oModule ) && count( $aPath ) )
				{
					$sSegment = array_shift( $aPath );
					switch( strToLower( $sSegment ) )
					{
						case "":        //  root
						case "_root":   
							$oTraverse = &$oModule->getRoot();
							break;
						case "..":      //  parent
						case "_parent": //  
							$oTraverse = &$oModule->getParent();
							break;
						case ".":       //  self
							$oTraverse = &$this;
							break;
						default:        //  child
							$oTraverse = &$oModule->register( $sSegment );
							break;
					}

					if ( !is_object( $oTraverse ) )
					{
						$this->call( "/Log/write", "Module '{$sSegment}' not found in module " . get_class( $oModule ) . "!", 3 );
						return $oTraverse;
					}

					$oModule =& $oTraverse;
				}

				$this->_lookupcache[ $sPath ] = &$oModule;
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
			return (bool) $this->current();
		}

		//  End Iterator functionality


		// Magic methods.
		public function __set( $sProperty, $mValue )
		{
/* Experimental Strict Checking on register/available Modules */
			if ( array_key_exists( strToUpper( $sProperty ), $this->_module ) )
				throw new Exception( "Trying to overwrite existing module {$sProperty} in " . get_class( $this ) . " with " . gettype( $mValue ) . " {$mValue}" );
			else if ( $this->checkModuleAvailability( $sProperty ) )
				throw new Exception( "Trying to set a property " . gettype( $mValue ) . " {$mValue} in " . get_class( $this ) . " where a module is available" );
			// else 
				// we've ended up in the situation where we may want to automagically create a stub class but since we 
				// don't know that at this point, we cannot throw an exception.
				// Due to Konsolidate's architecture, you end up having both the property AND the module (object) in the 
				// same tree, where the property now has precendence over the module, rendering the 'get'-ing of a Module
				// obsolete, since you will get the property.
				// In short: 'get' of a stub wil work as intended, unless your code has assigned a property with the same name
				// which let's 'get' return the property and not the stub module.
/* End Experimental Strict Checking on register/available Modules */
			$this->_property[ $sProperty ] = $mValue;
		}

		public function __get( $sProperty )
		{
			if ( array_key_exists( $sProperty, $this->_property ) )
				return $this->_property[ $sProperty ];
			else if ( array_key_exists( strToUpper( $sProperty ), $this->_module ) )
				return $this->_module[ strToUpper( $sProperty ) ];
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

	}

?>
