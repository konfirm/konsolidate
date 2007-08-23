<?php

	class CoreKey extends Konsolidate
	{
		const CHAR    = "abcdefghijklmnopqrstuvwxyz";
		const NUMERIC = "0123456789";

		private $_salt;
		private $_exclude;
		private $_lowercase;
		private $_uppercase;
		private $_numeric;
		private $_format;

		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->_exclude   = "oO0iI1lzZ2sS5uUvVwWnNmMRQq";
			$this->_lowercase = true;
			$this->_uppercase = true;
			$this->_numeric   = true;
			$this->_format    = "XXXX-XXXX";
			$this->_createSalt();
		}

		public function create( $sFormat=null ) //  format uses XXXX-XXXX-XXXX, where X is replaced with a key part
		{
			if ( is_null( $sFormat ) )
				$sFormat = $this->_format;
			return vsprintf( str_replace( Array( "%", "X" ), Array( "%%", "%s" ), $sFormat ), preg_split( "//", substr( str_shuffle( $this->_salt ), 0, substr_count( $sFormat, "X" ) ), -1, PREG_SPLIT_NO_EMPTY ) );
		}

		private function _createSalt()
		{
			$this->_salt  = $this->_lowercase ? self::CHAR : "";
			$this->_salt .= $this->_uppercase ? strToUpper( self::CHAR ) : "";
			$this->_salt .= $this->_numeric ? self::NUMERIC : "";
			$this->_salt  = preg_replace( "/[{$this->_exclude}]/", "", $this->_salt );
			$this->_salt  = str_repeat( $this->_salt, strlen( $this->_format ) );
			$this->_salt  = str_shuffle( $this->_salt );
		}

		public function __set( $sProperty, $mValue )
		{
			switch( $sProperty )
			{
				case "lowercase":
				case "uppercase":
				case "numeric":
				case "exclude":
				case "format":
					$sProperty = "_{$sProperty}";
					$this->$sProperty = $mValue;
					$this->_createSalt();
					break;
				default:
					parent::__set( $sProperty, $mValue );
					break;
			}
		}
	}

?>
