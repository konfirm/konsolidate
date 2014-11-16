<?php


/**
 *  Phrase translation class, based on locales
 *  @name    CoreLanguage
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreLanguage extends Konsolidate
{
	/**
	 *  The locale to translate to
	 *  @name    _locale
	 *  @type    string
	 *  @access  protected
	 */
	protected $_locale;

	/**
	 *  The translation engine to use (default is 'switch')
	 *  @name    _engine
	 *  @type    string
	 *  @access  protected
	 */
	protected $_engine;

	/**
	 *  constructor
	 *  @name    __construct
	 *  @type    constructor
	 *  @access  public
	 *  @param   object parent object
	 *  @return  object
	 *  @note    This object is constructed by one of Konsolidates modules
	 */
	public function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$this->setEngine('Switch');
	}

	/**
	 *  set the locale
	 *  @name    setLocale
	 *  @type    method
	 *  @access  public
	 *  @param   string locale
	 *  @return  void
	 */
	public function setLocale($sLocale)
	{
		$this->_locale = $sLocale;
	}

	/**
	 *  get the locale
	 *  @name    getLocale
	 *  @type    method
	 *  @access  public
	 *  @return  string locale
	 */
	public function getLocale()
	{
		return $this->_locale;
	}

	/**
	 *  set the 'translation' engine
	 *  @name    setEngine
	 *  @type    method
	 *  @access  public
	 *  @param   string engine
	 *  @return  bool
	 */
	public function setEngine($sEngine)
	{
		assert(is_string($sEngine));
		assert(!empty($sEngine));

		$oTMP = $this->register($sEngine);
		if ($oTMP !== false)
			$this->_engine = $sEngine;

		return $this->_engine === $sEngine;
	}

	/**
	 *  translate a phrase using the already set engine (default engine is 'Switch') and locale
	 *  @name    translate
	 *  @type    method
	 *  @access  public
	 *  @param   string phrase
	 *  @return  string translation
	 */
	public function translate($sPhrase)
	{
		return $this->call("{$this->_engine}/translate", $sPhrase);
	}
}
