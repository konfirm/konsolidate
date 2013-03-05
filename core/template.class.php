<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreTemplate
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Template
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Two step template engine (compilation and runtime parsing, where compilations are stored as cache)
	 *  @name    CoreTemplate
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreTemplate extends Konsolidate
	{
		/**
		 *  Where to look for templates
		 *  @name    _templatepath
		 *  @type    variable
		 *  @access  protected
		 */
		protected $_templatepath;

		/**
		 *  Where to store the compiled templates
		 *  @name    _compilepath
		 *  @type    variable
		 *  @access  protected
		 */
		protected $_compilepath;

		/**
		 *  How long ago was the document compiled
		 *  @name    _compiletime
		 *  @type    variable
		 *  @access  protected
		 */
		protected $_compiletime;

		/**
		 *  Does the server support short PHP open tags
		 *  @name    _shortopentag
		 *  @type    boolean
		 *  @access  protected
		 */
		protected $_shortopentag;

		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreTemplate( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_templatepath = $this->get( "/Config/Path/template", realpath( ( defined( "TEMPLATE_PATH" ) ? TEMPLATE_PATH : "./templates" ) ) );
			$this->_compilepath  = $this->get( "/Config/Path/compile", realpath( ( defined( "COMPILE_PATH" ) ? COMPILE_PATH : "./compile" ) ) );
			$this->_shortopentag = (bool) ini_get( "short_open_tag" );
			ini_set( "include_path", $this->_templatepath . PATH_SEPARATOR . ini_get( "include_path" ) );		}

		/**
		 *  Fetch the built template output
		 *  @name    fetch
		 *  @type    method
		 *  @access  public
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @param   bool   force [optional]
		 *  @return  string document
		 *  @syntax  Object->fetch( string template [, string reference [, bool force ] ] );
		 */
		public function fetch( $sTemplate, $sReference="", $bForce=false )
		{
			return $this->_compose( $sTemplate, $sReference, $bForce );
		}

		/**
		 *  Dumps the built template output to browser
		 *  @name    display
		 *  @type    method
		 *  @access  public
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @param   bool   force [optional]
		 *  @return  bool success
		 *  @syntax  Object->display( string template [, string reference [, bool force ] ] );
		 */
		public function display( $sTemplate, $sReference="", $bForce=false )
		{
			return print( $this->fetch( $sTemplate, $sReference, $bForce ) );
		}

		/**
		 *  Append a variable to the template engine in order to make it known inside the template
		 *  @name    append
		 *  @type    method
		 *  @access  public
		 *  @param   mixed   either a variable name or an array with name=>value pairs
		 *  @param   mixed   the value to set, ignored if 'variable' is an array [optional]
		 *  @return  void
		 *  @syntax  Object->append( mixed variable [, mixed value ] );
		 */
		public function append( $mVariable, $mValue=null )
		{
			if ( is_array( $mVariable ) )
			{
				foreach( $mVariable as $sVariable=>$mValue )
					$this->append( $sVariable, $mValue );
			}
			else
			{
				$mCurrent = $this->$mVariable;
				if ( !is_null( $mCurrent ) )
				{
					switch( getType( $mCurrent ) )
					{
						case "boolean":
							$mCurrent &= $mValue;
							break;
						case "integer":
						case "double": // for historical reasons "double" is returned in case of a float, and not simply "float"
							$mCurrent += $mValue;
							break;
						case "string":
							$mCurrent .= $mValue;
							break;
						case "array":
							if ( is_array( $mValue ) )
								foreach( $mValue as $sKey=>$mVal )
									if ( is_int( $sKey ) )
										array_push( $mCurrent, $mVal );
									else
										$mCurrent[ $sKey ] = $mVal;
							else
								array_push( $mCurrent, $mValue );
							break;
						case "object":
						case "resource":
						case "NULL":
						case "user function": // depricated since PHP 4
						case "unknown type": // omgwtfbbq!1one!
						default:
							$this->$mVariable = $mValue;
							break;
					}
					$this->$mVariable = $mCurrent;
				}
				else
				{
					$this->$mVariable = $mValue;
				}
			}
			return true;
		}

		/**
		 *  Aet a variable to the template engine in order to make it known inside the template
		 *  @name    set
		 *  @type    method
		 *  @access  public
		 *  @param   mixed   either a variable name or an array with name=>value pairs
		 *  @param   mixed   the value to set, ignored if 'variable' is an array [optional]
		 *  @param   bool    should the variable overwrite or extend (append) existing values?
		 *  @return  void
		 *  @syntax  Object->set( mixed variable [, mixed value [, bool append ] ] );
		 */
		public function set()
		{
			//  in order to achieve compatiblity with Konsolidates set method in strict mode, the params are read 'manually'
 			$aParam    = func_get_args();
			$mVariable = array_shift( $aParam );
			$mValue    = (bool) count( $aParam ) ? array_shift( $aParam ) : null;
			$bAppend   = (bool) count( $aParam ) ? array_shift( $aParam ) : false;

			if ( $bAppend === true )
				$this->append( $mVariable, $mValue );
			else
				parent::set( $mVariable, $mValue );
		}

		/**
		 *  Checks whether one (or more) of the dependencies has been updated after the precompilation cache has been built
		 *  @name    isUpdated
		 *  @type    method
		 *  @access  public
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @return  bool updated
		 *  @syntax  Object->isUpdated( string template [, string reference ] );
		 */
		public function isUpdated( $sTemplate, $sReference="" )
		{
			$sCacheFile   = $this->_getCompileName( $sTemplate, $sReference );
			$nLastCompile = $this->_getCompileUpdateTime( $sCacheFile );
			if ( file_exists( "{$this->_compilepath}/{$sCacheFile}" ) )
				if ( $nLastCompile > $this->_getDependencyUpdateTime( $sCacheFile ) )
					return false;
			return true;
		}

		/**
		 *  Reversed implementation of isUpdated
		 *  @name    isCompiled
		 *  @type    method
		 *  @access  public
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @return  bool compiled
		 *  @syntax  Object->isCompiled( string template [, string reference ] );
		 *  @see     isUpdated
		 *  @note    This alias method exists to make switching from CoreTemplate to NiceTemplate (and vice versa) painless
		 */
		public function isCompiled( $sTemplate, $sReference="" )
		{
			return !$this->isUpdated( $sTemplate, $sReference );
		}


		/**
		 *  Builds the template
		 *  @name    _compose
		 *  @type    method
		 *  @access  protected
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @param   bool   force [optional]
		 *  @return  string document
		 *  @syntax  Object->_compose( string template [, string reference [, bool force ] ] );
		 */
		protected function _compose( $sTemplate, $sReference="", $bForce=false )
		{
			$sCacheFile = $this->_getCompileName( $sTemplate, $sReference );
			//  prepare variables in the current scope
			foreach( $this->_property as $sVariable=>$sValue )
				$$sVariable = $sValue;

			if ( $bForce === true || $this->isUpdated( $sTemplate, $sReference ) )
			{
				//  start capturing the output
				ob_start();

				//  include the template, so the PHP code inside is executed and the content is send to the output buffer
				if ( $sTemplate{0} != "/" )
					$sTemplate = "{$this->_templatepath}/{$sTemplate}";
				if ( !file_exists( $sTemplate ) )
					throw new Exception( "Template not found '$sTemplate'" );
				include( $sTemplate );

				//  get the buffer contents and convert the request-time PHP tags to normal PHP tags
				$sCapture = strtr( ob_get_contents(), Array( "<!?"=>"<?", "?!>"=>"?>" ) );

				// end and clean the output buffer
				ob_end_clean();

				if ( !$this->_shortopentag )
				{
					//  The captured output may require a bit of rewriting
					$sCapture = preg_replace( "/<\?=\s*/", "<?php echo ", $sCapture );
					$sCapture = preg_replace( "/<\?[^php]/", "<?php", $sCapture );
				}

				if ( !$this->_storeCompilation( $sCacheFile, $sCapture ) )
					$this->call( "/Log/write", "Store of compilation has failed for template {$sTemplate} in file {$sCacheFile}" );
			}

			ob_start();
			include( "{$this->_compilepath}/{$sCacheFile}" );

			$sCapture = ob_get_contents();
			ob_end_clean();

			return $sCapture;
		}

		/**
		 *  Prepare paths and save compiled data to the filesystem
		 *  @name    _storeCompilation
		 *  @type    method
		 *  @access  protected
		 *  @param   string filename
		 *  @param   string content
		 *  @return  void
		 *  @syntax  Object->_storeCompilation( string cachefile, string content );
		 */
		protected function _storeCompilation( $sCacheFile, $sSource )
		{
			if ( !is_dir( "{$this->_compilepath}/dep/" ) )
				mkdir( "{$this->_compilepath}/dep/" );
			return ( 
				$this->_storeData( "{$this->_compilepath}/dep/" . md5( $sCacheFile ), serialize( get_included_files() ) ) && 
				$this->_storeData( "{$this->_compilepath}/{$sCacheFile}", $sSource, 1 )
			);
		}

		/**
		 *  Write data to files
		 *  @name    _storeData
		 *  @type    method
		 *  @access  protected
		 *  @param   string filename
		 *  @param   string content
		 *  @return  bool success
		 *  @syntax  Object->_storeData( string file, string content );
		 */
		protected function _storeData( $sFile, $sContent )
		{
			return $this->call( "/System/File/write", $sFile, $sContent );
		}

		/**
		 *  get the name of the compiled resource
		 *  @name    _getCompileName
		 *  @type    method
		 *  @access  protected
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @return  string compiled name
		 *  @syntax  Object->_getCompileName( string template [, string reference ] );
		 */
		protected function _getCompileName( $sTemplate, $sReference="" )
		{
			$sBase = basename( $sTemplate );
			return md5( "{$sTemplate}/{$sReference}-" . substr( $sBase, 0, strPos( $sBase, "." ) ) ) . ( !empty( $sReference ) ? "-{$sReference}" : "" ) . ".gen.php";
		}

		/**
		 *  get the latest update timestamp from all dependencies
		 *  @name    _getDependencyUpdateTime
		 *  @type    method
		 *  @access  protected
		 *  @param   string filename
		 *  @return  number timestamp
		 *  @syntax  Object->_getDependencyUpdateTime( string cachefile );
		 */
		protected function _getDependencyUpdateTime( $sCacheFile )
		{
			if ( !file_exists( "{$this->_compilepath}/dep/" . md5( $sCacheFile ) ) )
				return time();
			$aDependency = unserialize( file_get_contents( "{$this->_compilepath}/dep/" . md5( $sCacheFile ) ) );
			$nLatest     = 0;
			foreach( $aDependency as $sFileName )
				$nLatest = max( $nLatest, filemtime( $sFileName ) );
			return $nLatest;
		}

		/**
		 *  get the timestamp of the compilation
		 *  @name    _getCompileUpdateTime
		 *  @type    method
		 *  @access  protected
		 *  @param   string filename
		 *  @return  number timestamp
		 *  @syntax  Object->_getCompileUpdateTime( string cachefile );
		 */
		protected function _getCompileUpdateTime( $sCacheFile )
		{
			if ( !file_exists( "{$this->_compilepath}/{$sCacheFile}" ) )
				return false;
			$this->_compiletime = filemtime( "{$this->_compilepath}/{$sCacheFile}" );
			return $this->_compiletime;
		}
	}

?>
