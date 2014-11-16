<?php


/**
 *  Creation and manipulation of Images
 *  @name    CoreMediaImage
 *  @type    class
 *  @package Konsolidate
 *  @author  Rogier Spieker <rogier@konsolidate.nl>
 */
class CoreMediaImage extends Konsolidate
{
	/**
	 *  The image resource
	 *  @name    _image
	 *  @type    resrouce
	 *  @access  protected
	 */
	protected $_image;

	/**
	 *  CoreImage constructor
	 *  @name    CoreImage
	 *  @type    constructor
	 *  @access  public
	 *  @param   object parent object
	 *  @return  object
	 *  @note    This object is constructed by one of Konsolidates modules
	 */
	public function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$this->_image = null;
	}

	/**
	 *  Create a new image (including an internal reference to it)
	 *  @name    create
	 *  @type    method
	 *  @access  public
	 *  @param   int    width
	 *  @param   int    height
	 *  @param   string hex backgroundcolor [optional]
	 *  @return  resource image
	 */
	public function create($nWidth, $nHeight, $sBGColor=null)
	{
		$this->_image = $this->_create($nWidth, $nHeight, $sBGColor);
		return $this->_image;
	}

	/**
	 *  Load an existing image (create an internal reference to it)
	 *  @name    load
	 *  @type    method
	 *  @access  public
	 *  @param   string filename
	 *  @return  resource image
	 */
	public function load($sFile)
	{
		$this->_image = $this->_load($sFile);
		return $this->_image;
	}

	/**
	 *  Merge two images into eachother
	 *  @name    merge
	 *  @type    method
	 *  @access  public
	 *  @param   mixed destination (string filename or image resource)
	 *  @param   mixed source      (string filename or image resource)
	 *  @param   int   X position in destination [optional]
	 *  @param   int   Y position in destination [optional]
	 *  @param   int   X position in source [optional]
	 *  @param   int   Y position in source [optional]
	 *  @param   int   width of the part to merge [optional]
	 *  @param   int   height of the part to merge [optional]
	 *  @param   int   percentage of transparency of the source region [optional]
	 *  @return  resource image
	 */
	public function merge($mDestination, $mSource, $nDX=0, $nDY=0, $nSX=0, $nSY=0, $nSW=0, $nSH=0, $nPercentage=100)
	{
		if (!is_resource($mSource))
			$mSource = $this->_load($mSource);

		if (!is_resource($mDestination))
			$this->load($mDestination);

		if ($nSW <= 0)
			$nSW = imagesx($mSource) - $nSX;
		if ($nSH <= 0)
			$nSH = imagesy($mSource) - $nSY;

		imagecopyresampled($this->_image, $mSource, $nDX, $nDY, $nSX, $nSY, $nSW, $nSH, $nSW, $nSH);

		return $this->_image;
	}

	/**
	 *  Copy two images into eachother
	 *  @name    copy
	 *  @type    method
	 *  @access  public
	 *  @param   mixed destination (string filename or image resource)
	 *  @param   mixed source      (string filename or image resource)
	 *  @param   int   X position in destination [optional]
	 *  @param   int   Y position in destination [optional]
	 *  @param   int   X position in source [optional]
	 *  @param   int   Y position in source [optional]
	 *  @param   int   width of the part to image [optional]
	 *  @param   int   height of the part to merge [optional]
	 *  @return  resource image
	 */
	public function copy($mDestination, $mSource, $nDX=0, $nDY=0, $nSX=0, $nSY=0, $nSW=0, $nSH=0)
	{
		if (!is_resource($mDestination))
			$this->load($mDestination);

		if (!is_resource($mSource))
			$mSource = $this->_load($mSource);

		if ($nSW <= 0)
			$nSW = imagesx($mSource) - $nSX;
		if ($nSH <= 0)
			$nSH = imagesy($mSource) - $nSY;

		if (imageistruecolor($this->_image) && imageistruecolor($mSource))
			imagecopymerge($this->_image, $mSource, $nDX, $nDY, $nSX, $nSY, $nSW, $nSH, 100);
		else
			imagecopy($this->_image, $mSource, $nDX, $nDY, $nSX, $nSY, $nSW, $nSH);

		return $this->_image;
	}

	/**
	 *  Resize an image
	 *  @name    resize
	 *  @type    method
	 *  @access  public
	 *  @param   mixed image (string filename or image resource)
	 *  @param   int   new width [optional]
	 *  @param   int   new height [optional]
	 *  @return  resource image
	 */
	public function resize($mImage, $nWidth=0, $nHeight=0)
	{
		if (!is_resource($mImage))
			$mImage = $this->load($mImage);

		if ($nWidth == 0)
			$nWidth = imagesx($mImage);
		if ($nHeight == 0)
			$nHeight = imagesy($mImage);

		$this->create($nWidth, $nHeight);
		imagecopyresampled($this->_image, $mImage, 0, 0, 0, 0, $nWidth, $nHeight, imagesx($mImage), imagesy($mImage));
		return $this->_image;
	}

	/**
	 *  Crop an image
	 *  @name    crop
	 *  @type    method
	 *  @access  public
	 *  @param   mixed image (string filename or image resource)
	 *  @param   int   X offset
	 *  @param   int   Y offset
	 *  @param   int   new width
	 *  @param   int   new height
	 *  @return  resource image
	 */
	public function crop($mImage, $nX, $nY, $nWidth, $nHeight)
	{
		if (!is_resource($mImage))
			$mImage = $this->load($mImage);

		if ($nX < 0)
			$nX = imagesx($mImage) - abs($nX);
		if ($nY < 0)
			$nY = imagesy($mImage) - abs($nY);
		if ($nWidth < 0)
			$nWidth = imagesx($mImage) - (abs($nWidth) + $nX);
		if ($nHeight < 0)
			$nHeight = imagesy($mImage) - (abs($nHeight) + $nY);

		$this->create($nWidth, $nHeight);
		imagecopyresampled($this->_image, $mImage, 0, 0, $nX, $nY, $nWidth, $nHeight, $nWidth, $nHeight);

		return $this->_image;
	}

	/**
	 *  Display (save) the generated image
	 *  @name    display
	 *  @type    method
	 *  @access  public
	 *  @param   string imagetype (one of: jpg|jpeg|gif|png) [optional]
	 *  @param   int    quality [optional]
	 *  @param   string filename [optional]
	 *  @return  resource image
	 */
	public function display($sType='JPEG', $nQuality=75, $sFile=null)
	{
		switch (strToUpper($sType))
		{
			case 'GIF':
			case IMAGETYPE_GIF:
				if (is_null($sFile) && !headers_sent())
					header('Content-type: image/gif');

				@imagegif($this->_image, $sFile);
				break;

			case 'PNG':
			case IMAGETYPE_PNG:
				if (is_null($sFile) && !headers_sent())
					header('Content-type: image/png');

				@imagepng($this->_image, $sFile, round((9 / 100) * $nQuality));
				break;

			default:
				if (is_null($sFile) && !headers_sent())
					header('Content-type: image/jpeg');

				@imagejpeg($this->_image, $sFile, $nQuality);
				break;
		}

		imagedestroy($this->_image);
		ini_restore('memory_limit');

		return false;
	}

	/**
	 *  Fill an image with one solid color
	 *  @name    fill
	 *  @type    method
	 *  @access  public
	 *  @param   mixed  image (string filename or image resource)
	 *  @param   string hex backgroundcolor
	 *  @return  bool
	 */
	public function fill($mImage, $sColor)
	{
		if (!is_resource($mImage))
			$mImage = &$this->load($mImage);
		return imagefilledrectangle($mImage, 0, 0, imagesx($mImage), imagesy($mImage), $this->getColor($sColor, $mImage));
	}

	/**
	 *  Allocate a color
	 *  @name    getColor
	 *  @type    method
	 *  @access  public
	 *  @param   string   hex backgroundcolor
	 *  @param   resource image [optional]
	 *  @return  int      color
	 */
	public function getColor($sColor, $mImage=null)
	{
		if (is_null($mImage))
			$mImage = &$this->_image;

		if (substr($sColor, 0, 1) == '#')
			$sColor = substr($sColor, 1);

		if (strLen($sColor) == 3)
			$sColor = "{$sColor{0}}{$sColor{0}}{$sColor{1}}{$sColor{1}}{$sColor{2}}{$sColor{2}}";
		$sColor = str_pad($sColor, 6, '0', STR_PAD_RIGHT);

		$nDec = hexdec($sColor);
		return imagecolorallocate($mImage, 0xFF & ($nDec >> 0x10), 0xFF & ($nDec >> 0x8), 0xFF & $nDec);
	}

	/**
	 *  Calculate contrained dimensions
	 *  @name    getScaleDimension
	 *  @type    method
	 *  @access  public
	 *  @param   resource image
	 *  @param   int      width [optional, default 0]
	 *  @param   int      height [optional, default 0]
	 *  @return  int      array('width'=>W, 'height'=>H);
	 *  @note    provide 0 for either the width or the height to obtain it's constrained counterpart, provide 0 for both
	 *           to obtain the current dimensions
	 */
	public function getScaleDimension($mImage, $nWidth=0, $nHeight=0)
	{
		//  find out whether the programmer wants us to calculate either the width or the height
		if ($nWidth <= 0 && $nHeight > 0)
		{
			$nFactor = $nHeight / imagesy($mImage);
			$nWidth  = imagesx($mImage) * $nFactor;
		}
		else if ($nWidth > 0 && $nHeight <= 0)
		{
			$nFactor = $nWidth / imagesx($mImage);
			$nHeight = imagesy($mImage) * $nFactor;
		}
		else
		{
			$nWidth  = imagesx($mImage);
			$nHeight = imagesy($mImage);
		}

		return Array(
			'width'=>$nWidth,
			'height'=>$nHeight
		);
	}

	/**
	 *  Calculate and attempt to adjust the memory limit for specified size image
	 *  @name    adjustMemoryUsage
	 *  @type    method
	 *  @access  public
	 *  @param   int      width
	 *  @param   int      height
	 *  @param   int      bits [optional]
	 *  @param   int      channels [optional]
	 *  @return  bool
	 */
	public function adjustMemoryUsage($nWidth, $nHeight, $nBits=8, $nChannels=4)
	{
		$nBytes = ($nWidth * $nHeight * $nBits * $nChannels) / 8 + pow(2, 16) * 2.2;
		if (function_exists('memory_get_usage'))
		{
			if (memory_get_usage() + $nBytes > (int) ini_get('memory_limit') * pow(1024, 2))
				return ini_set('memory_limit', (int) ceil((memory_get_usage() + $nBytes) / pow(1024, 2)) . 'M');
			return true;
		}
		return false;
	}

	/**
	 *  Create a new image
	 *  @name    _create
	 *  @type    method
	 *  @access  protected
	 *  @param   int    width
	 *  @param   int    height
	 *  @param   string hex backgroundcolor [optional]
	 *  @return  resource image
	 */
	protected function _create($nWidth, $nHeight, $sBGColor=null)
	{
		$this->adjustMemoryUsage($nWidth, $nHeight);

		$oImage = null;
		if (function_exists('imagecreatetruecolor'))
		{
			$oImage = imagecreatetruecolor($nWidth, $nHeight);
			$nTrans = imagecolorallocate($oImage, 0, 0, 0);
			imagesavealpha($oImage, true);
			imagefill($oImage, 0, 0, imagecolorallocatealpha($oImage, 0, 0, 0, 127));
		}
		else if (function_exists('imagecreate'))
		{
			$oImage = imagecreate($nWidth, $nHeight);
		}

		if (!is_null($sBGColor) && is_resource($oImage))
			$this->fill($oImage, $sBGColor);

		return $oImage;
	}

	/**
	 *  Load an existing image
	 *  @name    _load
	 *  @type    method
	 *  @access  protected
	 *  @param   string filename
	 *  @return  resource image (bool false on error)
	 */
	protected function _load($sFile)
	{
		$oImage = null;
		if (file_exists($sFile))
		{
			$aFile = getimagesize($sFile);

			//  if getimagesize wasn't able to do its deed, return false
			if ($aFile === false)
				return false;

			$this->adjustMemoryUsage($aFile[0], $aFile[1]);
			switch ($aFile[2])
			{
				case IMAGETYPE_GIF:
					if (function_exists('imagecreatefromgif'))
						$oImage = imagecreatefromgif($sFile);
					break;

				case IMAGETYPE_PNG:
					if (function_exists('imagecreatefrompng'))
					{
						$oImage = imagecreatefrompng($sFile);
						imagealphablending($oImage, true);
						imagesavealpha($oImage, true);
					}
					break;

				case IMAGETYPE_JPEG:
					if (function_exists('imagecreatefromjpeg'))
						$oImage = imagecreatefromjpeg($sFile);
					break;
			}
		}
		return $oImage;
	}
}
