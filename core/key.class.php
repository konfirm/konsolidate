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

		public function __construct( $oParent )
		{
			parent::__construct( $oParent );

			$this->salt      = "";
			$this->exclude   = "oO0iI1lzZ2sS5uUvVwWnNmMRQq";
			$this->lowercase = true;
			$this->uppercase = true;
			$this->numeric   = true;
		}

		public function create( $sFormat="XXXX-XXXX" ) //  format uses XXXX-XXXX-XXXX, where X is replaced with a key part
		{
			return vsprintf( str_replace( Array( "%", "X" ), Array( "%%", "%s" ), $sFormat ), preg_split( "//", substr( str_shuffle( $this->_salt ), 0, substr_count( $sFormat, "X" ) ), -1, PREG_SPLIT_NO_EMPTY ) );
		}

		public function __set( $sProperty, $mValue )
		{
			switch( $sProperty )
			{
				case "lowercase":
				case "uppercase":
				case "numeric":
				case "exclude":
					$sProperty = "_{$sProperty}";
					$this->$sProperty = $mValue;
					$this->_salt  = $this->_lowercase ? self::CHAR : "";
					$this->_salt .= $this->_uppercase ? strToUpper( self::CHAR ) : "";
					$this->_salt .= $this->_numeric ? self::NUMERIC : "";
					$this->_salt  = preg_replace( "/[{$this->_exclude}]/", "", $this->_salt );
					break;
				default:
					parent::__set( $sProperty, $mValue );
					break;
			}
		}
	}

?>
