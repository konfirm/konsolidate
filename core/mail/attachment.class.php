<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreMailAttachment
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Mail/Attachment
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */

	/**
	 *  Create attachments for Mail
	 *  @name    CoreMailAttachment
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreMailAttachment extends Konsolidate
	{
		/**
		 *  The file name
		 *  @name    _name
		 *  @type    string
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_name;

		/**
		 *  The file data
		 *  @name    _data
		 *  @type    string
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_data;

		/**
		 *  The file type
		 *  @name    _type
		 *  @type    string
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_type;

		/**
		 *  The file disposition
		 *  @name    _disposition
		 *  @type    string
		 *  @access  protected
		 *  @note    this property is set implicitly using __set (without the preceeding '_')
		 */
		protected $_disposition;

		/**
		 *  dynamically set properties and take special care of them
		 *  @name    __set
		 *  @type    method
		 *  @access  public
		 *  @param   string property
		 *  @param   mixed  value
		 *  @return  void
		 *  @syntax  void CoreMailAttachment->(string property) = mixed variable
		 */
		public function __set( $sProperty, $mValue )
		{
			if ( property_exists( $this, "_{$sProperty}" ) )
				$this->{"_{$sProperty}"} = $mValue;
			else
				parent::__set( $sProperty, $mValue );
		}

		/**
		 *  get properties according to special defined rules
		 *  @name    __get
		 *  @type    method
		 *  @access  public
		 *  @param   string property
		 *  @return  mixed  value
		 *  @syntax  mixed = CoreMailAttachment->(string property);
		 */
		public function __get( $sProperty )
		{
			switch( $sProperty )
			{
				case "data":
					return !empty( $this->_data ) ? $this->_data : $this->call( "/System/File/read", $this->_name );
				case "type":
					return !empty( $this->_type ) ? $this->_type : $this->call( "/System/File/MIME/getType", $this->_name );
				case "disposition":
					return !empty( $this->_disposition ) ? $this->_disposition : "attachment";
				case "name":
					return $this->_name;
				default:
					return parent::__get( $sProperty );
			}
		}
	}

?>