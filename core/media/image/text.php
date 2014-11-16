<?php


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
	 *           to handle your request based on the provided parameters. Refer to the method calls of _createFromStyle
	 *           and _createFromArgument for an overview of possible arguments (the calls cannot be mixed, use either
	 *           one of the calls)
	 *  @see     _createFromStyle
	 *  @see     _createFromArgument
	 */
	public function create()
	{
		$args = func_get_args();
		if (count($args) > 3 || (isset($args[2]) && !is_array($args[2])))
			return call_user_func_array(Array($this, '_createFromArgument'), $args);

		return call_user_func_array(Array($this, '_createFromStyle'), $args);
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
	 *  @see     create
	 */
	protected function _createFromStyle($image, $text, $style)
	{
		$this->_style = $style;
		$antiAlias   = $this->_getStyle('anti-alias');
		$size        = (int)    $this->_getStyle('font-size', 10);
		$angle       = (int)    $this->_getStyle('angle', 0);
		$width       = (int)    $this->_getStyle('width');
		$height      = (int)    $this->_getStyle('height');
		$x           = (int)    $this->_getStyle('left', 0);
		$y           = (int)    $this->_getStyle('top', 0);
		$color       = (string) $this->_getStyle('color', '#000');
		$bgColor     = (string) $this->_getStyle('background-color', '#fff');
		$bgImage     = (string) $this->_getStyle('background-image');
		$bgRepeat    = (string) $this->_getStyle('background-repeat', 'repeat');
		$font        = DOCUMENT_ROOT . $this->_getStyle('font-family');

		if (is_null($antiAlias))
			$antiAlias = $size > 24;
		else
			$antiAlias = (bool) $antiAlias;

		$baseSize   = $antiAlias ? $size * ($size < 72 ? 4 : 2) : $size;
		$point      = imagettfbbox($baseSize, $angle, $font, $text);
		$fontX      = min($point[0], $point[6]) * -1;
		$fontY      = min($point[5], $point[7]) * -1;
		$textHeight = max($point[1], $point[3]) - min($point[5], $point[7]);
		$textWidth  = max($point[2], $point[4]) - min($point[0], $point[6]);
		$ratio      = $size / $baseSize;
		$newWidth   = !empty($width) ? $width : ceil(($textWidth + 8) * $ratio);
		$newHeight  = !empty($height) ? $height : ceil(($textHeight + 6) * $ratio);

		if (empty($width))
			$width = $textWidth + 8;

		if (empty($height))
			$height = $textHeight + 8;

		if (!empty($bgImage))
		{
			$image      = &$this->call('../create', $newWidth, $newHeight, $bgColor);
			$tile       = &$this->call('../_load', DOCUMENT_ROOT . $bgImage);
			$tileWidth  = imagesx($tile);
			$tileHeight = imagesy($tile);

			if (strToLower(substr($bgRepeat, 0, 6)) == 'repeat')
			{
				imagesettile($image, $tile);
				imagefilledrectangle($image, 0, 0, strToLower($bgRepeat) == 'repeat-y' ? $tileWidth : $newWidth, strToLower($bgRepeat) == 'repeat-x' ? $tileHeight : $newHeight, IMG_COLOR_TILED);
			}
			else
			{
				$image = $this->call('../copy', $image, $tile);
			}
		}
		else if (!is_resource($image))
		{
			if ($bgColor == 'transparent' || $bgColor == 'none' || empty($bgColor))
				$image  = &$this->call('../create', $newWidth, $newHeight);
			else
				$image  = &$this->call('../create', $newWidth, $newHeight, $bgColor);
		}

		if ($antiAlias)
		{
			$rework      = $this->call('../_create', $textWidth, $textHeight);
			$textImage   = $this->call('../_create', $newWidth, $newHeight);
			$transparent = imagecolorallocatealpha($rework, 0, 0, 0, 127);
			$reworkColor = $this->call('../getColor', $color, $rework);
			$reworkBG     = $this->call('../getColor', '#000', $textImage);

			imagealphablending($rework, false);
			imagefilledrectangle($rework, 0, 0, $textWidth, $textHeight, $transparent);

			imagealphablending($rework, true);
			imagettftext($rework, $baseSize, $angle, $fontX, $fontY, $reworkColor, $font, $text);
			imagealphablending($rework, false);

			imagecolortransparent($textImage, $reworkBG);
			imagealphablending($textImage, false);
			imagecopyresampled($textImage, $rework, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

			imagealphablending($image, true);
			imagecopy($image, $textImage, $x, $y, 0, 0, $newWidth, $newHeight);
			imagealphablending($image, false);
			imagedestroy($rework);
			imagedestroy($textImage);
		}
		else
		{
			imagealphablending($image, true);
			imagettftext($image, $size, $angle, $x, $fontY + $y, $this->call('../getColor', $color), $font, $text);
		}

		return $image;
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
	 *  @param   string  color (optional, default '#000', black)
	 *  @param   string  background color (optional, default '#fff', white)
	 *  @param   bool    antialias (optional, default false for text sizes < 24, true otherwise)
	 *  @param   int     angle of rotation in degrees (optional, default 0)
	 *  @return  resource image
	 *  @see     create
	 */
	protected function _createFromArgument($image, $text, $font, $size=10, $x=0, $y=0, $color='#000', $bgColor='#fff', $antiAlias=null, $angle=0)
	{
		if (is_null($antiAlias))
			$antiAlias = $size > 24;

		if ($antiAlias === true)
		{
			$baseSize    = $size * ($size < 72 ? 4 : 2);
			$point       = imagettfbbox($baseSize, $angle, $font, $text);
			$fontX       = min($point[0], $point[6]) * -1;
			$fontY       = min($point[5], $point[7]) * -1;
			$height      = max($point[1], $point[3]) - min($point[5], $point[7]);
			$width       = max($point[2], $point[4]) - min($point[0], $point[6]);
			$ratio       = $size / $baseSize;
			$newWidth    = ceil($width * $ratio);
			$newHeight   = ceil($height * $ratio);
			$rework      = $this->call('../_create', $width, $height);
			$textImage   = $this->call('../_create', $newWidth, $newHeight);
			$transparent = imagecolorallocatealpha($rework, 0, 0, 0, 127);
			$reworkColor = $this->call('../getColor', $color, $rework);
			$reworkBG    = $this->call('../getColor', '#000', $textImage);
			if (!is_resource($image))
				$image = &$this->call('../create', $newWidth + $x, $newHeight + $y, $bgColor);

			imagealphablending($rework, false);
			imagefilledrectangle($rework, 0, 0, $width, $height, $transparent);
			imagealphablending($rework, true);
			imagettftext($rework, $baseSize, $angle, $fontX, $fontY, $reworkColor, $font, $text);
			imagealphablending($rework, false);

			imagecolortransparent($textImage, $bkg);
			imagealphablending($textImage, false);
			imagecopyresampled($textImage, $rework, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

			imagealphablending($image, true);
			imagecopy($image, $textImage, $x, $y, 0, 0, $newWidth, $newHeight);
			imagealphablending($image, false);
			imagedestroy($rework);
			imagedestroy($textImage);
		}
		else
		{
			$point  = imagettfbbox($size, $angle, $font, $text);
			$fontY  = min($point[5], $point[7]) * -1;
			$height = 2 + (max($point[1], $point[3]) - min($point[5], $point[7]));
			$width  = 2 + (max($point[2], $point[4]) - min($point[0], $point[6]));

			if (!is_resource($image))
				$image  = &$this->call('../create', $width + $x, $height + $y, $bgColor);
			else
				imagealphablending($image, true);

			imagettftext($image, $size, $angle, $x, $fontY + $y, $this->call('../getColor', $color), $font, $text);
		}

		return $image;
	}

	/**
	 *  get style from style definition array
	 *  @name    _getStyle
	 *  @type    method
	 *  @access  protected
	 *  @param   string  property
	 *  @param   string  default (optional, default null)
	 *  @return  mixed property value
	 *  @syntax  CoreMediaImageText->_getStyle(string property [, mixed default])
	 */
	protected function _getStyle($property, $default=null)
	{
		if (array_key_exists($property, $this->_style) && $this->_style[$property] != 'auto')
			return $this->_style[$property];

		return $default;
	}
}
