<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CorePlugin
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Plugin
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Manage external objects for use within Konsolidate
	 *  @name    CorePlugin
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CorePlugin extends Konsolidate
	{
		/**
		 *  Hook an object as module into the Konsolidate structure
		 *  @name    hook
		 *  @type    method
		 *  @access  public
		 *  @param   string modulename
		 *  @param   object module
		 *  @return  object
		 *  @syntax  Object->hook( string modulename, object module );
		 */
		public function hook( $sModule, &$oModule )
		{
			return $this->_module[ strToUpper( $sModule ) ] = $oModule;
		}

		/**
		 *  Create a new (non-konsolidate) instance and hook it into the CoreManager
		 *  @name    create
		 *  @type    method
		 *  @access  public
		 *  @param   string modulename
		 *  @param   string classname
		 *  @param   mixed  class argument1
		 *  @param   mixed  class argument2
		 *  @param   mixed  class argument...
		 *  @return  object
		 *  @syntax  Object->create( string modulename, object module );
		 *  @note    You can provide as many arguments as needed to construct the class
		 */
		public function create()
		{
			$aArgument  = func_get_args();
			$mModule    = array_shift( $aArgument );
			$sClassName = array_shift( $aArgument );
			$oObject    = null;

			if ( !class_exists( $sClassName ) )
				return false;

			if ( class_exists( "ReflectionClass" ) ) //  Can we use a sophisticated method of PHP5?
			{
				$oObject = call_user_func_array(
					Array( 
						new ReflectionClass( $sClassName ), 
						"newInstance" 
					),
					$aArgument
				);
			}
			else // fall back onto the evil... erm eval method, should not happen, for Konsolidate is now PHP5 only
			{
				$sArgument = "";
				foreach ( $aArgument as $sKey=>$mValue )
				{
					$sParam     = "mParam{$sKey}";
					$$sParam    = $mValue;
					$sArgument .= ( !empty( $sArgument ) ? "," : "" ) . "\$$sParam";
				}
				$sConstructor = "\$oObject = new {$sClassName}({$sArgument});";
				eval( $sConstructor );
			}

			return $this->hook( strToUpper( $mModule ), $oObject );
		}
	}

?>