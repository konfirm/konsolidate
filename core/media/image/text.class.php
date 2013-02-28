<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreMediaImageText
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Media/Image/Text
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Add text to images
	 *  @name    CoreMediaImageText
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreMediaImageText extends Konsolidate
	{
		/**
		 *  Style information
		 *  @name    _focalLength
		 *  @type    int
		 *  @access  protected
		 */
		protected $_style;

		/**
		 *  Create a new textlabel
		 *  @name    create
		 *  @type    method
		 *  @access  public
		 *  @return  resource image
		 *  @note    this method examines the provided input and bridges the call to the most approproate method avaiable 
		 *           to handle your request based on the provided parameters. Refer to the method calls of _createFromStyle and _createFromArgument
		 *           for an overview of possible arguments (the calls cannot be mixed, use either one of the calls)
		 *  @see     _createFromStyle
		 *  @see     _createFromArgument
		 */
		public function create()
		{
			$aArgument = func_get_args();
			if ( count( $aArgument ) > 3 || ( isset( $aArgument[ 2 ] ) && !is_array( $aArgument[ 2 ] ) ) )
				return call_user_func_array(
					Array(
						$this,
						"_createFromArgument"
					),
					$aArgument
				);
			return call_user_func_array(
				Array(
					$this,
					"_createFromStyle"
				),
				$aArgument
			);
		}

		/**
		 *  Create a new textlabel based on an style property array
		 *  @name    _createFromStyle
		 *  @type    method
		 *  @access  protected
		 *  @param   resource image (or null to create a new image)
		 *  @param   string  text
		 *  @param   array   style definition
		 *  @return  resource image
		 *  @syntax  CoreMediaImageText->_createFromStyle( resource image, string text, array style )
		 *  @see     create
		 */
		protected function _createFromStyle( $mImage, $sText, $aStyle )
		{
			$this->_style = $aStyle;
			$bAntiAlias   = $this->_getStyle( "anti-alias" );
			$nSize        = (int)    $this->_getStyle( "font-size", 10 );
			$nAngle       = (int)    $this->_getStyle( "angle", 0 );
			$nWidth       = (int)    $this->_getStyle( "width" );
			$nHeight      = (int)    $this->_getStyle( "height" );
			$nX           = (int)    $this->_getStyle( "left", 0 );
			$nY           = (int)    $this->_getStyle( "top", 0 );
			$sColor       = (string) $this->_getStyle( "color", "#000" );
			$sBGColor     = (string) $this->_getStyle( "background-color", "#fff" );
			$sBGImage     = (string) $this->_getStyle( "background-image" );
			$sBGRepeat    = (string) $this->_getStyle( "background-repeat", "repeat" );
			$sFont        = DOCUMENT_ROOT . $this->_getStyle( "font-family" );

			if ( is_null( $bAntiAlias ) )
				$bAntiAlias = $nSize > 24;
			else
				$bAntiAlias = (bool) $bAntiAlias;

			$nBaseSize   = $bAntiAlias ? $nSize * ( $nSize < 72 ? 4 : 2 ) : $nSize;
			$aPoint      = imagettfbbox( $nBaseSize, $nAngle, $sFont, $sText );
			$nFontX      = min( $aPoint[ 0 ], $aPoint[ 6 ] ) * -1;
			$nFontY      = min( $aPoint[ 5 ], $aPoint[ 7 ] ) * -1;
			$nTextHeight = max( $aPoint[ 1 ], $aPoint[ 3 ] ) - min( $aPoint[ 5 ], $aPoint[ 7 ] );
			$nTextWidth  = max( $aPoint[ 2 ], $aPoint[ 4 ] ) - min( $aPoint[ 0 ], $aPoint[ 6 ] );
			$nRatio      = $nSize / $nBaseSize;
			$nNewWidth   = !empty( $nWidth ) ? $nWidth : ceil( ( $nTextWidth + 8 ) * $nRatio );
			$nNewHeight  = !empty( $nHeight ) ? $nHeight : ceil( ( $nTextHeight + 6 ) * $nRatio );

			if ( empty( $nWidth ) )
				$nWidth = $nTextWidth + 8;

			if ( empty( $nHeight ) )
				$nHeight = $nTextHeight + 8;

			if ( !empty( $sBGImage ) )
			{
				$mImage      = &$this->call( "../create", $nNewWidth, $nNewHeight, $sBGColor );
				$rTile       = &$this->call( "../_load", DOCUMENT_ROOT . $sBGImage );
				$nTileWidth  = imagesx( $rTile );
				$nTileHeight = imagesy( $rTile );

				if ( strToLower( substr( $sBGRepeat, 0, 6 ) ) == "repeat" )
				{
					imagesettile( $mImage, $rTile );
					imagefilledrectangle( $mImage, 0, 0, strToLower( $sBGRepeat ) == "repeat-y" ? $nTileWidth : $nNewWidth, strToLower( $sBGRepeat ) == "repeat-x" ? $nTileHeight : $nNewHeight, IMG_COLOR_TILED );
				}
				else
				{
					$mImage = $this->call( "../copy", $mImage, $rTile );
				}
			}
			elseif ( !is_resource( $mImage ) )
			{
				if ( $sBGColor == "transparent" || $sBGColor == "none" || empty( $sBGColor ) )
					$mImage  = &$this->call( "../create", $nNewWidth, $nNewHeight );
				else
					$mImage  = &$this->call( "../create", $nNewWidth, $nNewHeight, $sBGColor );
			}

			if ( $bAntiAlias )
			{
				$rImage     = $this->call( "../_create", $nTextWidth, $nTextHeight );
				$rText      = $this->call( "../_create", $nNewWidth, $nNewHeight );
				$nTrans     = imagecolorallocatealpha( $rImage, 0, 0, 0, 127 );
				$nColor     = $this->call( "../getColor", $sColor, $rImage );
				$nBGColor   = $this->call( "../getColor", "#000", $rText );

				imagealphablending( $rImage, false );
				imagefilledrectangle( $rImage, 0, 0, $nTextWidth, $nTextHeight, $nTrans );

				imagealphablending( $rImage, true );
				imagettftext( $rImage, $nBaseSize, $nAngle, $nFontX, $nFontY, $nColor, $sFont, $sText );
				imagealphablending( $rImage, false );

				imagecolortransparent( $rText, $nBGColor );
				imagealphablending( $rText, false );
				imagecopyresampled( $rText, $rImage, 0, 0, 0, 0, $nNewWidth, $nNewHeight, $nWidth, $nHeight );

				imagealphablending( $mImage, true );
				imagecopy( $mImage, $rText, $nX, $nY, 0, 0, $nNewWidth, $nNewHeight );
				imagealphablending( $mImage, false );
				imagedestroy( $rImage );
				imagedestroy( $rText );
			}
			else
			{
				imagealphablending( $mImage, true );
				imagettftext( $mImage, $nSize, $nAngle, $nX, $nFontY + $nY, $this->call( "../getColor", $sColor ), $sFont, $sText );
			}

			return $mImage;
		}

		/**
		 *  Create a new textlabel based on an style property array
		 *  @name    _create
		 *  @type    method
		 *  @access  protected
		 *  @param   resource image (or null to create a new image)
		 *  @param   string  text
		 *  @param   string  fontfilelocation
		 *  @param   int     textsize (optional, default 10)
		 *  @param   int     X position (optional, default 0)
		 *  @param   int     Y position (optional, default 0)
		 *  @param   string  color (optional, default "#000", black)
		 *  @param   string  background color (optional, default "#fff", white)
		 *  @param   bool    antialias (optional, default false for text sizes < 24, true otherwise)
		 *  @param   int     angle of rotation in degrees (optional, default 0)
		 *  @return  resource image
		 *  @syntax  CoreMediaImageText->_createFromArgument( resource image, string text, string fontfile [, int textsize [, int X [, int Y [, string color [, string backgroundcolor [, bool antialias [, float angle ] ] ] ] ] ] ] )
		 *  @see     create
		 */
		protected function _createFromArgument( $mImage, $sText, $sFont, $nSize=10, $nX=0, $nY=0, $sColor="#000", $sBGColor="#fff", $bAntiAlias=null, $nAngle=0 )
		{
			if ( is_null( $bAntiAlias ) )
				$bAntiAlias = $nSize > 24;

			if ( $bAntiAlias === true )
			{
				$nBaseSize  = $nSize * ( $nSize < 72 ? 4 : 2 );
				$aPoint     = imagettfbbox( $nBaseSize, $nAngle, $sFont, $sText );
				$nFontX     = min( $aPoint[ 0 ], $aPoint[ 6 ]) * -1;
				$nFontY     = min( $aPoint[ 5 ], $aPoint[ 7 ]) * -1;
				$nHeight    = max( $aPoint[ 1 ], $aPoint[ 3 ]) - min( $aPoint[ 5 ], $aPoint[ 7 ] );
				$nWidth     = max( $aPoint[ 2 ], $aPoint[ 4 ]) - min( $aPoint[ 0 ], $aPoint[ 6 ] );
				$nRatio     = $nSize / $nBaseSize;
				$nNewWidth  = ceil( $nWidth * $nRatio );
				$nNewHeight = ceil( $nHeight * $nRatio );
				$rImage     = $this->call( "../_create", $nWidth, $nHeight );
				$rText      = $this->call( "../_create", $nNewWidth, $nNewHeight );
				$nTrans     = imagecolorallocatealpha( $rImage, 0, 0, 0, 127 );
				$nColor     = $this->call( "../getColor", $sColor, $rImage );
				$nBGColor   = $this->call( "../getColor", "#000", $rText );
				if ( !is_resource( $mImage ) )
					$mImage = &$this->call( "../create", $nNewWidth + $nX, $nNewHeight + $nY, $sBGColor );

				imagealphablending( $rImage, false );
				imagefilledrectangle( $rImage, 0, 0, $nWidth, $nHeight, $nTrans );
				imagealphablending( $rImage, true );
				imagettftext( $rImage, $nBaseSize, $nAngle, $nFontX, $nFontY, $nColor, $sFont, $sText );
				imagealphablending( $rImage, false );

				imagecolortransparent( $rText, $bkg );
				imagealphablending( $rText, false );
				imagecopyresampled( $rText, $rImage, 0, 0, 0, 0, $nNewWidth, $nNewHeight, $nWidth, $nHeight );

				imagealphablending( $mImage, true );
				imagecopy( $mImage, $rText, $nX, $nY, 0, 0, $nNewWidth, $nNewHeight );
				imagealphablending( $mImage, false );
				imagedestroy( $rImage);
				imagedestroy( $rText );
			}
			else
			{
				$aPoint  = imagettfbbox( $nSize, $nAngle, $sFont, $sText );
				$nFontY  = min( $aPoint[ 5 ], $aPoint[ 7 ]) * -1;
				$nHeight = 2 + ( max( $aPoint[ 1 ], $aPoint[ 3 ] ) - min( $aPoint[ 5 ], $aPoint[ 7 ] ) );
				$nWidth  = 2 + ( max( $aPoint[ 2 ], $aPoint[ 4 ] ) - min( $aPoint[ 0 ], $aPoint[ 6 ] ) );

				if ( !is_resource( $mImage ) )
					$mImage  = &$this->call( "../create", $nWidth + $nX, $nHeight + $nY, $sBGColor );
				else
					imagealphablending( $mImage, true );

				imagettftext( $mImage, $nSize, $nAngle, $nX, $nFontY + $nY, $this->call( "../getColor", $sColor ), $sFont, $sText );
			}

			return $mImage;
		}

		/**
		 *  get style from style definition array
		 *  @name    _getStyle
		 *  @type    method
		 *  @access  protected
		 *  @param   string  property
		 *  @param   string  default (optional, default null)
		 *  @return  mixed property value
		 *  @syntax  CoreMediaImageText->_getStyle( string property [, mixed default ] )
		 */
		protected function _getStyle( $sProperty, $mDefault=null )
		{
			if ( array_key_exists( $sProperty, $this->_style ) && $this->_style[ $sProperty ] != "auto" )
				return $this->_style[ $sProperty ];
			return $mDefault;
		}
	}

?>