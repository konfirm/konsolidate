<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreMediaImageFX
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Media/Image/FX
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Apply effects to Image resources, can be used without imagefilters (much slower!)
	 *  @name    CoreMediaImageFX
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreMediaImageFX extends Konsolidate
	{
		/**
		 *  Blur an image
		 *  @name    blur
		 *  @type    method
		 *  @access  public
		 *  @param   resource image (or null to create a new image)
		 *  @param   integer  amount (optional, default 1)
		 *  @return  resource image
		 *  @syntax  Object->blur( resource image, int amount )
		 */
		public function blur( $mImage, $nAmount=1 )
		{
			if ( !is_resource( $mImage ) )
				$mImage = $this->call( "../load", $mImage );

			if ( function_exists( "imagefilter" ) )
			{
				imagefilter( $mImage, IMG_FILTER_GAUSSIAN_BLUR );
				if ( --$nAmount > 0 )
					return $this->blur( $mImage, $nAmount );
			}
			else
			{
				$nWidth  = imagesx( $mImage );
				$nHeight = imagesy( $mImage );
				$nDist   = round( $nAmount / 3 );

				for ( $nX = 0; $nX < $nWidth; ++$nX )
					for ( $nY = 0; $nY < $nHeight; ++$nY )
					{
						$nRed          = 0;
						$nGreen        = 0;
						$nBlue         = 0;
						$aColor        = Array();
						$nCurrentColor = imagecolorat( $mImage, $nX, $nY );

						for ( $k = $nX - $nDist; $k <= $nX + $nDist; ++$k )
							for ( $l = $nY - $nDist; $l <= $nY + $nDist; ++$l )
								if ( $k < 0 || $k >= $nWidth || $l < 0 || $l >= $nHeight )
									$aColor[] = $nCurrentColor;
								else
									$aColor[] = imagecolorat( $mImage, $k, $l );

						foreach( $aColor as $nColor )
						{
							$nRed   += 0xFF & ( $nColor >> 16 );
							$nGreen += 0xFF & ( $nColor >> 8 );
							$nBlue  += 0xFF & $nColor;
						}

						$nCount = count( $aColor );
						$nRed   /= $nCount;
						$nGreen /= $nCount;
						$nBlue  /= $nCount;

						$nNewColor = imagecolorallocate( $mImage, $nRed, $nGreen, $nBlue );
						imagesetpixel( $mImage, $nX, $nY, $nNewColor );
					}
			}
			return $mImage;
		}


		/**
		 *  Invert colors of an image
		 *  @name    invert
		 *  @type    method
		 *  @access  public
		 *  @param   resource image (or null to create a new image)
		 *  @return  resource image
		 *  @syntax  Object->invert( resource image )
		 */
		public function invert( $mImage )
		{
			if ( !is_resource( $mImage ) )
				$mImage = $this->call( "../load", $mImage );

			if ( function_exists( "imagefilter" ) )
			{
				imagefilter( $mImage, IMG_FILTER_NEGATE );
			}
			else
			{
				$nWidth  = imagesx( $mImage );
				$nHeight = imagesy( $mImage );

				for ( $nX = 0; $nX < $nWidth; ++$nX )
					for ( $nY = 0; $nY < $nHeight; ++$nY )
					{
						$nColor = imagecolorat( $mImage, $nX, $nY );
						$nRed   = 255 - ( 0xFF & ( $nColor >> 16 ) );
						$nGreen = 255 - ( 0xFF & ( $nColor >> 8 ) );
						$nBlue  = 255 - ( 0xFF & $nColor );

						$nNewColor = imagecolorallocate( $mImage, $nRed, $nGreen, $nBlue );
						imagesetpixel( $mImage, $nX, $nY, $nNewColor );
					}
			}
			return $mImage;
		}

		/**
		 *  Convert an image to greyscale
		 *  @name    greyscale
		 *  @type    method
		 *  @access  public
		 *  @param   resource image (or null to create a new image)
		 *  @param   bool     correct for the human eye
		 *  @return  resource image
		 *  @syntax  Object->greyscale( resource image [, bool humaneye correction ] )
		 */
		public function greyscale( $mImage, $bHumanEyeCorrection=false )
		{
			if ( !is_resource( $mImage ) )
				$mImage = $this->call( "../load", $mImage );

			if ( function_exists( "imagefilter" ) && !$bHumanEyeCorrection )
			{
				imagefilter( $mImage, IMG_FILTER_GRAYSCALE );
			}
			else
			{
				$nWidth  = imagesx( $mImage );
				$nHeight = imagesy( $mImage );

				for ( $nX = 0; $nX < $nWidth; ++$nX )
					for ( $nY = 0; $nY < $nHeight; ++$nY )
					{
						$nColor   = imagecolorat( $mImage, $nX, $nY );
						if ( $bHumanEyeCorrection )
							$nAverage = ( ( 0xFF & ( $nColor >> 16 ) ) * .3 + ( 0xFF & ( $nColor >> 8 ) ) *.59 + ( 0xFF & $nColor ) *.11 ) / ( .3 + .59 + .11 );
						else
							$nAverage = ( ( 0xFF & ( $nColor >> 16 ) ) + ( 0xFF & ( $nColor >> 8 ) ) + ( 0xFF & $nColor ) ) / 3;

						$nRed   = $nAverage;
						$nGreen = $nAverage;
						$nBlue  = $nAverage;

						$nNewColor = imagecolorallocate( $mImage, $nRed, $nGreen, $nBlue );
						imagesetpixel( $mImage, $nX, $nY, $nNewColor );
					}
			}
			return $mImage;
		}
	}

?>