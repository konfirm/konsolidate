<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreManager
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Manager
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 35 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-05-16 17:17:08 +0200 (Wed, 16 May 2007) $
	 *               \___\/           
	 *
	 *  Manage external objects for use within Konsolidate
	 *  @name    CoreManager
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@klof.net>
	 */
	class CoreManager extends Konsolidate
	{
		/**
		 *  Hook an object as module into the Konsolidate structure
		 *  @name    hook
		 *  @type    method
		 *  @access  public
		 *  @param   string modulename
		 *  @param   object module
		 *  @returns object
		 *  @syntax  Object->hook( string modulename, object module );
		 */
		public function &hook( $sModule, &$oModule )
		{
			return $this->_module[ strToUpper( $sModule ) ] = &$oModule;
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
		 *  @returns object
		 *  @syntax  Object->create( string modulename, object module );
		 *  @note    You can provide as many arguments as needed to construct the class
		 */
		public function &create()
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