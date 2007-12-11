<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
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
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreTemplate extends Konsolidate
	{
		/**
		 *  Where to look for templates
		 *  @name    templatepath
		 *  @type    variable
		 *  @access  public
		 */
		var $templatepath;

		/**
		 *  Where to store the compiled templates
		 *  @name    compilepath
		 *  @type    variable
		 *  @access  public
		 */
		var $compilepath;

		/**
		 *  How long ago was the document compiled
		 *  @name    compiletime
		 *  @type    variable
		 *  @access  private
		 */
		var $_compiletime;


		function __construct( &$oParent )
		{
			parent::__construct( $oParent );

			$this->templatepath = realpath( ( defined( "TEMPLATE_PATH" ) ? TEMPLATE_PATH : "./templates" ) );
			$this->compilepath  = realpath( ( defined( "COMPILE_PATH" ) ? COMPILE_PATH : "./compile" ) );
			ini_set( "include_path", ini_get( "include_path" ) . ":" . $this->templatepath );
		}


		/**
		 *  Fetch the built template output
		 *  @name    fetch
		 *  @type    method
		 *  @access  public
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @param   bool   force [optional]
		 *  @returns string document
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
		 *  @returns bool success
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
		 *  @returns void
		 *  @syntax  Object->append( mixed variable [, mixed value ] );
		 */
		public function append( $mVariable, $mValue=null )
		{
			if ( is_array( $mVariable ) )
			{
				foreach( $mVariable as $sVariable=>$mValue )
					$this->assign( $sVariable, $mValue );
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
		 *  @returns void
		 *  @syntax  Object->set( mixed variable [, mixed value [, bool append ] ] );
		 */
		public function set( $mVariable, $mValue=null, $bAppend=false )
		{
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
		 *  @returns bool updated
		 *  @syntax  Object->isUpdated( string template [, string reference ] );
		 */
		public function isUpdated( $sTemplate, $sReference="" )
		{
			$sCacheFile   = $this->_getCompileName( $sTemplate, $sReference );
			$nLastCompile = $this->_getCompileUpdateTime( $sCacheFile );
			if ( file_exists( "{$this->compilepath}/{$sCacheFile}" ) )
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
		 *  @returns bool compiled
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
		 *  @access  private
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @param   bool   force [optional]
		 *  @returns string document
		 *  @syntax  Object->_compose( string template [, string reference [, bool force ] ] );
		 */
		private function _compose( $sTemplate, $sReference="", $bForce=false )
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
					$sTemplate = "{$this->templatepath}/{$sTemplate}";
				if ( !file_exists( $sTemplate ) )
					throw new Exception( "Template not found '$sTemplate'" );
				include( $sTemplate );

				//  get the buffer contents and convert the request-time PHP tags to normal PHP tags
				$sCapture = strtr( ob_get_contents(), Array( "<!?"=>"<?", "?!>"=>"?>" ) );

				// end and clean the output buffer
				ob_end_clean();

				if ( !$this->_storeCompilation( $sCacheFile, $sCapture ) )
					$this->call( "/Log/write", "Store of compilation has failed for template {$sTemplate} in file {$sCacheFile}" );
			}

			ob_start();
			include( "{$this->compilepath}/{$sCacheFile}" );

			$sCapture = ob_get_contents();
			ob_end_clean();

			return $sCapture;
		}

		/**
		 *  Prepare paths and save compiled data to the filesystem
		 *  @name    _storeCompilation
		 *  @type    method
		 *  @access  private
		 *  @param   string filename
		 *  @param   string content
		 *  @returns void
		 *  @syntax  Object->_storeCompilation( string cachefile, string content );
		 */
		private function _storeCompilation( $sCacheFile, $sSource )
		{
			if ( !is_dir( "{$this->compilepath}/dep/" ) )
				mkdir( "{$this->compilepath}/dep/" );
			return ( 
				$this->_storeData( "{$this->compilepath}/dep/" . md5( $sCacheFile ), serialize( get_included_files() ) ) && 
				$this->_storeData( "{$this->compilepath}/{$sCacheFile}", $sSource, 1 )
			);
		}

		/**
		 *  Write data to files
		 *  @name    _storeData
		 *  @type    method
		 *  @access  private
		 *  @param   string filename
		 *  @param   string content
		 *  @returns bool success
		 *  @syntax  Object->_storeData( string file, string content );
		 */
		private function _storeData( $sFile, $sContent )
		{
			return $this->call( "/System/File/write", $sFile, $sContent );
		}

		/**
		 *  get the name of the compiled resource
		 *  @name    _getCompileName
		 *  @type    method
		 *  @access  private
		 *  @param   string template
		 *  @param   string reference [optional]
		 *  @returns string compiled name
		 *  @syntax  Object->_getCompileName( string template [, string reference ] );
		 */
		private function _getCompileName( $sTemplate, $sReference="" )
		{
			$sBase = basename( $sTemplate );
			return md5( "{$sTemplate}/{$sReference}-" . substr( $sBase, 0, strPos( $sBase, "." ) ) ) . ( !empty( $sReference ) ? "-{$sReference}" : "" ) . ".gen.php";
		}

		/**
		 *  get the latest update timestamp from all dependencies
		 *  @name    _getDependencyUpdateTime
		 *  @type    method
		 *  @access  private
		 *  @param   string filename
		 *  @returns number timestamp
		 *  @syntax  Object->_getDependencyUpdateTime( string cachefile );
		 */
		private function _getDependencyUpdateTime( $sCacheFile )
		{
			if ( !file_exists( "{$this->compilepath}/dep/" . md5( $sCacheFile ) ) )
				return time();
			$aDependency = unserialize( file_get_contents( "{$this->compilepath}/dep/" . md5( $sCacheFile ) ) );
			$nLatest     = 0;
			foreach( $aDependency as $sFileName )
				$nLatest = max( $nLatest, filemtime( $sFileName ) );
			return $nLatest;
		}

		/**
		 *  get the timestamp of the compilation
		 *  @name    _getCompileUpdateTime
		 *  @type    method
		 *  @access  private
		 *  @param   string filename
		 *  @returns number timestamp
		 *  @syntax  Object->_getCompileUpdateTime( string cachefile );
		 */
		private function _getCompileUpdateTime( $sCacheFile )
		{
			if ( !file_exists( "{$this->compilepath}/{$sCacheFile}" ) )
				return false;
			$this->_compiletime = filemtime( "{$this->compilepath}/{$sCacheFile}" );
			return $this->_compiletime;
		}
	}

?>