<?php


/**
 *  File IO, either use an instance to put/get or read/write an entire file at once
 *  @name    CoreSystemFile
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreSystemFile extends Konsolidate
{
	protected $_filepointer;

	/**
	 *  Read an entire file and return the contents
	 *  @name    read
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @return  string file contents (bool false on error)
	 *  @syntax  bool [object]->read(string filename)
	 */
	public static function read($file)
	{
		if (file_exists($file) && is_readable($file))
			return file_get_contents($file);

		return false;
	}

	/**
	 *  Write data to a file
	 *  @name    write
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @param   string data
	 *  @return  bool success
	 *  @syntax  bool [object]->write(string filename, string data)
	 */
	public static function write($file, $data)
	{
		return file_put_contents($file, $data);
	}

	/**
	 *  Set the mode of a file
	 *  @name    mode
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @param   number mode
	 *  @return  bool success
	 *  @syntax  bool [object]->mode(string file, number mode)
	 *  @note    mode needs be an octal number, eg 0777
	 */
	public static function mode($file, $mode)
	{
		return chmod($file, $mode);
	}

	/**
	 *  unlink (delete) a file
	 *  @name    unlink
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @return  bool success
	 *  @syntax  bool [object]->unlink(string filename)
	 */
	public static function unlink($file)
	{
		return unlink($file);
	}

	/**
	 *  delete a file
	 *  @name    delete
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @return  bool success
	 *  @syntax  bool [object]->delete(string filename)
	 *  @see     unlink
	 *  @note    an alias method for unlink
	 */
	public static function delete($file)
	{
		return $this->unlink($file);
	}

	/**
	 *  rename a file
	 *  @name    rename
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @param   string newfilename
	 *  @param   bool   force (optional, default false)
	 *  @return  bool success
	 *  @syntax  bool [object]->rename(string filename, string newfilename [, bool force])
	 */
	public static function rename($file, $newFile, $force=false)
	{
		if (file_exists($file) && ($force || (!$force && !file_exists($newFile))))
			return rename($file, $newFile);

		return false;
	}

	/**
	 *  Open a file for interaction
	 *  @name    open
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @param   string mode (optional, default 'r' (read access))
	 *  @return  bool success
	 *  @syntax  bool [object]->open(string filename [, string mode]);
	 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
	 *           you can best create a unique instance to use this method, obtained through:
	 *           [KonsolidateObject]->instance('/System/File');
	 */
	public function open($file, $mode='r')
	{
		$this->_filepointer = fopen($file, $mode);

		return $this->_filepointer !== false;
	}

	/**
	 *  Get data from an opened file
	 *  @name    get
	 *  @type    method
	 *  @access  public
	 *  @param   mixed  int length [optional, default 4096 bytes], or string property
	 *  @return  mixed  data
	 *  @syntax  string [object]->get([int bytes]);
	 *           mixed  [object]->get(string property);
	 *  @note    If a string property is provided, the property value is returned, otherwise the next line of the
	 *           opened file is returned.
	 *           Warning: Since you cannot know if your code is the only code currently accessing any file
	 *           you can best create a unique instance to use this method, obtained through:
	 *           [KonsolidateObject]->instance('/System/File');
	 */
	public function get()
	{
		//  in order to achieve compatiblity with Konsolidates set method in strict mode, the params are read 'manually'
		$args    = func_get_args();
		$length  = (bool) count($args) ? array_shift($args) : 4096;
		$default = (bool) count($args) ? array_shift($args) : null;

		if (is_integer($length))
		{
			if (is_resource($this->_filepointer) && !feof($this->_filepointer))
				return fgets($this->_filepointer, $length);

			return false;
		}

		return parent::get($length, $default);
	}

	/**
	 *  Put data into an opened file
	 *  @name    put
	 *  @type    method
	 *  @access  public
	 *  @param   string data
	 *  @return  bool success
	 *  @syntax  bool [object]->put(string data);
	 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
	 *           you can best create a unique instance to use this method, obtained through: [KonsolidateObject]->instance('/System/File');
	 */
	public function put($data)
	{
		if (is_resource($this->_filepointer))
			return fputs($this->_filepointer, $data, strlen($data));

		return false;
	}

	/**
	 *  Get data from an opened file
	 *  @name    next
	 *  @type    method
	 *  @access  public
	 *  @return  string data
	 *  @syntax  string [object]->next();
	 *  @see     get
	 *  @note    Alias of get, relying on the default amount of bytes
	 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
	 *           you can best create a unique instance to use this method, obtained through:
	 *           [KonsolidateObject]->instance('/System/File');
	 */
	public function next()
	{
		return $this->get();
	}

	/**
	 *  Close the opened file
	 *  @name    close
	 *  @type    method
	 *  @access  public
	 *  @return  bool success
	 *  @syntax  bool [object]->close
	 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
	 *           you can best create a unique instance to use this method, obtained through:
	 *           [KonsolidateObject]->instance('/System/File');
	 */
	public function close()
	{
		if (is_resource($this->_filepointer))
			return fclose($this->_filepointer);

		return false;
	}

	/**
	 *  Get the filepointer of the opened file
	 *  @name    getFilePointer
	 *  @type    method
	 *  @access  public
	 *  @return  resource filepointer
	 *  @syntax  resource [object]->getFilePointer()
	 *  @note    Warning: Since you cannot know if your code is the only code currently accessing any file
	 *           you can best create a unique instance to use this method, obtained through:
	 *           [KonsolidateObject]->instance('/System/File');
	 */
	public function getFilePointer()
	{
		return $this->_filepointer;
	}

	/**
	 *  Magic __destruct, closes open filepointers
	 *  @name    __destruct
	 *  @type    method
	 *  @access  public
	 *  @return  void
	 */
	public function __destruct()
	{
		if (!is_null($this->_filepointer) && is_resource($this->_filepointer))
			$this->close();
	}
}
