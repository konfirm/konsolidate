<?php


/**
 *  CSV (Character Seperated Values) file support
 *  @name    CoreSystemFileCSV
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreSystemFileCSV extends Konsolidate
{
	/**
	 *  The filepointer resource for interactive get/put
	 *  @name    _filepointer
	 *  @type    resource
	 *  @access  protected
	 */
	protected $_filepointer;

	/**
	 *  The fieldname array, used to create proper and consistent objects when using the next method
	 *  @name    _fieldname
	 *  @type    array
	 *  @access  protected
	 */
	protected $_fieldname;


	/**
	 *  constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   object parent object
	 *  @return  object
	 *  @syntax  object = &new CoreSystemFileCSV(object parent)
	 *  @note    This object is constructed by one of Konsolidates modules
	 */
	function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$this->_fieldname = false;
		$this->delimiter = ',';
		$this->enclosure = '"';
	}

	/**
	 *  Open a connection to a CSV file
	 *  @name    open
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @param   string mode [optional, default 'r']
	 *  @param   bool   use first row as field definition [optional, default true]
	 *  @return  bool  success
	 *  @syntax  string [object]->open(string filename [, string mode [, bool firstrowdefines]]);
	 */
	public function open($file, $mode='r', $definitionRow=true)
	{
		if ($this->call('../open', $file, $mode))
		{
			$this->_filepointer = $this->call('../getFilePointer');
			$this->_fieldname   = $definitionRow;

			return true;
		}

		return false;
	}

	/**
	 *  Get CSV data from an opened file
	 *  @name    get
	 *  @type    method
	 *  @access  public
	 *  @param   mixed  int length [optional, default 4096 bytes], or string property
	 *  @return  mixed  data
	 *  @syntax  string [object]->get([int bytes]);
	 *           mixed  [object]->get(string property);
	 *  @note    If a string property is provided, the property value is returned, otherwise the next line of the
	 *           opened file is returned.
	 */
	public function get()
	{
		//  in order to achieve compatiblity with Konsolidates set method in strict mode, the params are read 'manually'
		$args      = func_get_args();
		$length    = (bool) count($args) ? array_shift($args) : 4096;
		$delimiter = (bool) count($args) ? array_shift($args) : null;
		$enclosure = (bool) count($args) ? array_shift($args) : null;

		if (is_integer($length))
		{
			if (empty($delimiter))
				$delimiter = $this->delimiter;
			if (empty($enclosure))
				$enclosure = $this->enclosure;

			if ($this->_filepointer !== false && !feof($this->_filepointer))
				return fgetcsv($this->_filepointer, $length, $delimiter, $enclosure);

			return false;
		}

		return parent::get($length, $delimiter);
	}

	/**
	 *  Put a record into a CSV file
	 *  @name    put
	 *  @type    method
	 *  @access  public
	 *  @param   mixed  data
	 *  @param   string delimiter [optional, default class property 'delimiter' (default ',')]
	 *  @param   string enclosure [optional, default class property 'enclosure' (default '"')]
	 *  @param   bool   use first row as field definition [optional, default true]
	 *  @return  bool  success
	 *  @syntax  bool [object]->put(mixed data [, string delimiter [, string enclosure]]);
	 */
	public function put($data, $delimiter=null, $enclosure=null)
	{
		if (empty($delimiter))
			$delimiter = $this->delimiter;
		if (empty($enclosure))
			$enclosure = $this->enclosure;

		if ($this->_filepointer !== false)
			return fputcsv($this->_filepointer, is_array($data) ? $data : Array($data), $delimiter, $enclosure);

		return false;
	}

	/**
	 *  Get the next record from the CSV file
	 *  @name    put
	 *  @type    method
	 *  @access  public
	 *  @param   int    length    [optional, default 4096]
	 *  @param   string delimiter [optional, default class property 'delimiter' (default ',')]
	 *  @param   string enclosure [optional, default class property 'enclosure' (default '"')]
	 *  @return  mixed  object (if fieldnames are known), array (if fieldnames are not known)
	 *  @syntax  mixed [object]->put(mixed data [, string delimiter [, string enclosure]]);
	 */
	public function next($length=4096, $delimiter=null, $sEncosure=null)
	{
		if (empty($delimiter))
			$delimiter = $this->delimiter;
		if (empty($enclosure))
			$enclosure = $this->enclosure;

		if ($this->_fieldname === true)
			$this->_fieldname = $this->get($length, $delimiter, $enclosure);

		$result = $this->get($length, $delimiter, $enclosure);
		if ($result !== false)
		{
			if (is_array($this->_fieldname))
			{
				$return = (object) null;
				for ($i = 0; $i < count($this->_fieldname); ++$i)
					$return->{$this->_fieldname[$i]} = $result[$i];

				return $return;
			}

			return $result;
		}

		return false;
	}

	/**
	 *  Close the connection to the CSV file
	 *  @name    close
	 *  @type    method
	 *  @access  public
	 *  @return  bool success
	 *  @syntax  bool [object]->close();
	 */
	public function close()
	{
		if ($this->_filepointer !== false)
			return fclose($this->_filepointer);

		return false;
	}
}
