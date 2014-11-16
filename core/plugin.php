<?php


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
	 *  @syntax  Object->hook(string modulename, object module);
	 */
	public function hook($module, $instance)
	{
		return $this->_module[strToUpper($module)] = $instance;
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
	 *  @syntax  Object->create(string modulename, object module);
	 *  @note    You can provide as many arguments as needed to construct the class
	 */
	public function create()
	{
		$args      = func_get_args();
		$module    = array_shift($args);
		$className = array_shift($args);
		$object    = null;

		if (!class_exists($className))
			return false;

		$object = call_user_func_array(Array(new ReflectionClass($className), 'newInstance'), $args);

		return $this->hook(strToUpper($module), $object);
	}
}
