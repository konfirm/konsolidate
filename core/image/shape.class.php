<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreImageShape
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Image/Shape
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */
	class CoreImageShape extends Konsolidate
	{
		private $_bottomIsY;
		private $_focalLength;
		private $_rotationX;
		private $_rotationY;
		private $_rotationZ;
				
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->_bottomIsY   = false;
			$this->_focalLength = 1000;
			$this->_rotationX   = 0;
			$this->_rotationY   = 0;
			$this->_rotationZ   = 0;
		}

		public function rotate( $nX, $nY, $nZ )
		{
			$this->_rotationX = $nX;
			$this->_rotationY = $nY;
			$this->_rotationZ = $nZ;
		}

		public function buildBottomUp( $bBottomY=true )
		{
			$this->_bottomIsY = $bBottomY;
		}

		public function line( $mImage, $nSX, $nSY, $nDX, $nDY, $sColor )
		{
			return $this->_draw( $mImage, Array( 
				$this->_point( $nSX, $nSY ), 
				$this->_point( $nDX, $nDY )
			), $sColor );
		}

		public function rectangle( $mImage, $nX, $nY, $nWidth, $nHeight, $sBorderColor=null, $sFillColor=null )
		{
			return $this->_draw( $mImage, Array( 
				$this->_point( $nX, $nY ), 
				$this->_point( $nX + $nWidth, $nY ), 
				$this->_point( $nX + $nWidth, $nY + $nHeight ), 
				$this->_point( $nX, $nY + $nHeight )
			), $sBorderColor, $sFillColor );
		}

		public function polygon( $mImage, $nX, $nY, $nRadius, $nSegment, $sBorderColor=null, $sFillColor=null )
		{
			return $this->_draw( $mImage, $this->_calculateRing( $nX, $nY, $nRadius, $nSegment ), $sBorderColor, $sFillColor );
		}

		public function star( $mImage, $nX, $nY, $nInnerRadius, $nOuterRadius, $nPeak, $sBorderColor=null, $sFillColor=null )
		{
			$nInnerOffset = 360 / ( $nPeak * 2 );
			$aOuterCircle = $this->_calculateRing( $nX, $nY, $nOuterRadius, $nPeak );
			$aInnerCircle = $this->_calculateRing( $nX, $nY, $nInnerRadius, $nPeak, $nInnerOffset );
			$aStar        = Array();
			for ( $i = 0; $i < $nPeak; ++$i )
				array_push( $aStar, $aOuterCircle[ $i ], $aInnerCircle[ $i ] );

			return $this->_draw( $mImage, $aStar, $sBorderColor, $sFillColor );
		}

		private function _draw( $mImage, $aPoint, $sBorderColor=null, $sFillColor=null )
		{
			if ( $this->_rotationX != 0 || $this->_rotationY != 0 || $this->_rotationZ != 0 )
				return $this->_draw3D( $mImage, $aPoint, $sBorderColor, $sFillColor );
			return $this->_draw2D( $mImage, $aPoint, $sBorderColor, $sFillColor );
		}

		private function _draw2D( $mImage, $aPoint, $sBorderColor=null, $sFillColor=null )
		{
			if ( !is_resource( $mImage ) )
				$mImage = &$this->call( "../load", $mImage );

			$nSegment   = count( $aPoint );
			$aFlatPoint = $this->_resolvePoints( $aPoint, $mImage );

			$bReturn = true;

			if ( !is_null( $sFillColor ) )
				$bReturn &= imagefilledpolygon( $mImage, $aFlatPoint, $nSegment, $this->call( "../getColor", $sFillColor ) );

			if ( !is_null( $sBorderColor ) )
			{
				if ( count( $aPoint ) > 2 )
					$bReturn &= imagepolygon( $mImage, $aFlatPoint, $nSegment, $this->call( "../getColor", $sBorderColor ) );
				else if ( count( $aPoint ) > 1 )
					$bReturn &= imageline( $mImage, $aFlatPoint[ 0 ], $aFlatPoint[ 1 ], $aFlatPoint[ 2 ], $aFlatPoint[ 3 ], $this->call( "../getColor", $sBorderColor ) );
				else 
					$bReturn &= imagesetpixel( $mImage, $aFlatPoint[ 0 ], $aFlatPoint[ 1 ], $this->call( "../getColor", $sBorderColor ) );
			}

			return $bReturn;
		}

		private function _draw3D( $mImage, $aPoint, $sBorderColor=null, $sFillColor=null )
		{
			$aBack = Array();
			for ( $i = 0; $i < count( $aPoint ); ++$i )
				array_push( $aBack, $this->_point( $aPoint[ $i ][ 0 ], $aPoint[ $i ][ 1 ], $aPoint[ $i ][ 2 ] + 30 ) );

			$this->_draw2D( $mImage, $aBack, $sBorderColor, $sFillColor );

			$nPoint = count( $aPoint );
			for ( $i = 0; $i < $nPoint; ++$i )
				$this->_draw2D( $mImage, Array( 
					$this->_point( $aPoint[ $i ][ 0 ], $aPoint[ $i ][ 1 ], $aPoint[ $i ][ 2 ] ),
					$this->_point( $aBack[ $i ][ 0 ], $aBack[ $i ][ 1 ], $aBack[ $i ][ 2 ] ),
					$this->_point( $aBack[ ( $i + 1 ) % $nPoint ][ 0 ], $aBack[ ( $i + 1 ) % $nPoint ][ 1 ], $aBack[ ( $i + 1 ) % $nPoint ][ 2 ] ),
					$this->_point( $aPoint[ ( $i + 1 ) % $nPoint ][ 0 ], $aPoint[ ( $i + 1 ) % $nPoint ][ 1 ], $aPoint[ ( $i + 1 ) % $nPoint ][ 2 ] )
				), $sBorderColor, $sFillColor );
			
			$this->_draw2D( $mImage, $aPoint, $sBorderColor, $sFillColor );

			return true;
		}
		
		private function _calculateRing( $nX, $nY, $nRadius, $nSegment, $nRotation=0 )
		{
			$nCycle    = deg2rad( 360 / $nSegment );
			$nRotation = deg2rad( $nRotation );
			$aReturn   = Array();

			for ( $i = 0; $i < $nSegment; ++$i )
			{
				$nDelta = $nRotation + ( $i * $nCycle );
				array_push( $aReturn, $this->_point( $nX + cos( $nDelta ) * $nRadius, $nY + sin( $nDelta ) * $nRadius ) );
			}
			return $aReturn;
		}

		private function _point( $nX, $nY, $nZ=0 )
		{
			return Array( $nX, $nY, $nZ );
		}

		private function _resolvePoints( $aPoint, $mImage=null )
		{
			if ( $this->_bottomIsY && is_resource( $mImage ) )
				$nHeight = imagesy( $mImage );

			$aPoint = $this->_applyRotation( $aPoint );

			$aReturn = Array();
			for ( $i = 0; $i < count( $aPoint ); ++$i )
				array_push( $aReturn, $aPoint[ $i ][ 0 ], isset( $nHeight ) ? $nHeight - $aPoint[ $i ][ 1 ] : $aPoint[ $i ][ 1 ] );
			return $aReturn;
		}

		private function _applyRotation( $aPoint, $nRotationX=0, $nRotationY=0, $nRotationZ=0 )
		{
			$aReturn = Array();
			$nSX = sin( deg2rad( $this->_rotationX ) );
			$nCX = cos( deg2rad( $this->_rotationX ) );
			$nSY = sin( deg2rad( $this->_rotationY ) );
			$nCY = cos( deg2rad( $this->_rotationY ) );
			$nSZ = sin( deg2rad( $this->_rotationZ ) );
			$nCZ = cos( deg2rad( $this->_rotationZ ) );

			for ( $i = 0; $i < count( $aPoint ); ++$i )
			{
				$nX = $aPoint[ $i ][ 0 ];
				$nY = $aPoint[ $i ][ 1 ];
				$nZ = $aPoint[ $i ][ 2 ];

				//  rotation X
				$nXY = $nCX * $nY - $nSX * $nZ;
				$nXZ = $nSX * $nY + $nCX * $nZ;

				//  rotation Y
				$nYZ = $nCY * $nXZ - $nSY * $nX;
				$nYX = $nSY * $nXZ + $nCY * $nX;

				//  rotation Z
				$nZX = $nCZ * $nYX - $nSZ * $nXY;
				$nZY = $nSZ * $nYX + $nCZ * $nXY;

				$nScale = $this->_focalLength / ( $this->_focalLength + $nYZ );
				array_push( $aReturn, Array( $nZX * $nScale, $nZY * $nScale, $nYZ ) );
			}
			return $aReturn;
		}
	}

?>
