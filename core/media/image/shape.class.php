<?php

	/*
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://www.konsolidate.nl
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreMediaImageShape
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: Media/Image/Shape
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev$
	 *          \___    ___\/         $Author$
	 *              \   \  /          $Date$
	 *               \___\/           
	 */


	/**
	 *  Basic shapes drawn in 2D or experimental 3D space
	 *  @name    CoreMediaImageShape
	 *  @type    class
	 *  @package Konsolidate
	 *  @author  Rogier Spieker <rogier@konsolidate.nl>
	 */
	class CoreMediaImageShape extends Konsolidate
	{
		/**
		 *  Swap Y=0 from top to bottom (flip vertical)
		 *  @name    _bottomIsY
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_bottomIsY;

		/**
		 *  The focal length
		 *  @name    _focalLength
		 *  @type    int
		 *  @access  protected
		 */
		protected $_focalLength;

		/**
		 *  The amount to rotate over the X-axis
		 *  @name    _rotationX
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_rotationX;

		/**
		 *  The amount to rotate over the Y-axis
		 *  @name    _rotationY
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_rotationY;

		/**
		 *  The amount to rotate over the Z-axis
		 *  @name    _rotationZ
		 *  @type    bool
		 *  @access  protected
		 */
		protected $_rotationZ;
				
		/**
		 *  constructor
		 *  @name    __construct
		 *  @type    constructor
		 *  @access  public
		 *  @param   object parent object
		 *  @return  object
		 *  @syntax  object = &new CoreMediaImageShape( object parent )
		 *  @note    This object is constructed by one of Konsolidates modules
		 */
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			$this->_bottomIsY   = false;
			$this->_focalLength = 1000;
			$this->_rotationX   = 0;
			$this->_rotationY   = 0;
			$this->_rotationZ   = 0;
		}

		/**
		 *  set rotation of the shape, maintaining the normal x,y(,z) coordinates
		 *  @name    rotate
		 *  @type    method
		 *  @access  public
		 *  @param   float  X
		 *  @param   float  Y (optional, default 0)
		 *  @param   float  Z (optional, default 0)
		 *  @return  void
		 *  @syntax  string CoreMediaImageShape->rotate( float X [, float Y [, float Z ] ] )
		 *  @note    3D rotation is considered experimental (and ugly), use with care
		 */
		public function rotate( $nX, $nY, $nZ=0 )
		{
			$this->_rotationX = $nX;
			$this->_rotationY = $nY;
			$this->_rotationZ = $nZ;
		}

		/**
		 *  flip the Y coordinate system (useful in case of charts/diagrams)
		 *  @name    buildBottomUp
		 *  @type    method
		 *  @access  public
		 *  @param   bool   bottom is base
		 *  @return  void
		 *  @syntax  string CoreMediaImageShape->buildBottomUp( bool bottom )
		 */
		public function buildBottomUp( $bBottomY=true )
		{
			$this->_bottomIsY = $bBottomY;
		}

		/**
		 *  draw a line
		 *  @name    line
		 *  @type    method
		 *  @access  public
		 *  @param   resource image
		 *  @param   int start X
		 *  @param   int start Y
		 *  @param   int end X
		 *  @param   int end Y
		 *  @param   string color
		 *  @return  bool
		 *  @syntax  string CoreMediaImageShape->line( resource image, int startX, int startY, int endX, int endY, string color )
		 */
		public function line( $mImage, $nSX, $nSY, $nDX, $nDY, $sColor )
		{
			return $this->_draw( $mImage, Array( 
				$this->_point( $nSX, $nSY ), 
				$this->_point( $nDX, $nDY )
			), $sColor );
		}

		/**
		 *  draw a rectangle
		 *  @name    rectangle
		 *  @type    method
		 *  @access  public
		 *  @param   resource image
		 *  @param   int X
		 *  @param   int Y
		 *  @param   int width
		 *  @param   int height
		 *  @param   string bordercolor (optional, default null)
		 *  @param   string fillcolor (optional, default null)
		 *  @return  bool
		 *  @syntax  string CoreMediaImageShape->rectangle( resource image, int x, int y, int width, int height [, string bordercolor [, string fillcolor ] ] )
		 */
		public function rectangle( $mImage, $nX, $nY, $nWidth, $nHeight, $sBorderColor=null, $sFillColor=null )
		{
			return $this->_draw( $mImage, Array( 
				$this->_point( $nX, $nY ), 
				$this->_point( $nX + $nWidth, $nY ), 
				$this->_point( $nX + $nWidth, $nY + $nHeight ), 
				$this->_point( $nX, $nY + $nHeight )
			), $sBorderColor, $sFillColor );
		}

		/**
		 *  draw a polygon
		 *  @name    polygon
		 *  @type    method
		 *  @access  public
		 *  @param   resource image
		 *  @param   int X
		 *  @param   int Y
		 *  @param   int radius
		 *  @param   int segments
		 *  @param   string bordercolor (optional, default null)
		 *  @param   string fillcolor (optional, default null)
		 *  @return  bool
		 *  @syntax  string CoreMediaImageShape->polygon( resource image, int x, int y, int radius, int segments [, string bordercolor [, string fillcolor ] ] )
		 */
		public function polygon( $mImage, $nX, $nY, $nRadius, $nSegment, $sBorderColor=null, $sFillColor=null )
		{
			return $this->_draw( $mImage, $this->_calculateRing( $nX, $nY, $nRadius, $nSegment ), $sBorderColor, $sFillColor );
		}

		/**
		 *  draw a star
		 *  @name    star
		 *  @type    method
		 *  @access  public
		 *  @param   resource image
		 *  @param   int X
		 *  @param   int Y
		 *  @param   int inner radius
		 *  @param   int outer radius
		 *  @param   int peaks
		 *  @param   string bordercolor (optional, default null)
		 *  @param   string fillcolor (optional, default null)
		 *  @return  bool
		 *  @syntax  string CoreMediaImageShape->star( resource image, int x, int y, int innerradius, int outerradius, int peaks [, string bordercolor [, string fillcolor ] ] )
		 */
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

		/**
		 *  draw all defined shapes
		 *  @name    _draw
		 *  @type    method
		 *  @access  protected
		 *  @param   resource image
		 *  @param   array points
		 *  @param   string bordercolor (optional, default null)
		 *  @param   string fillcolor (optional, default null)
		 *  @return  bool
		 *  @syntax  string CoreMediaImageShape->_draw( resource image, array points [, string bordercolor [, string fillcolor ] ] )
		 */
		protected function _draw( $mImage, $aPoint, $sBorderColor=null, $sFillColor=null )
		{
			if ( $this->_rotationX != 0 || $this->_rotationY != 0 || $this->_rotationZ != 0 )
				return $this->_draw3D( $mImage, $aPoint, $sBorderColor, $sFillColor );
			return $this->_draw2D( $mImage, $aPoint, $sBorderColor, $sFillColor );
		}

		/**
		 *  draw shapes as flat 2D image
		 *  @name    _draw3D
		 *  @type    method
		 *  @access  protected
		 *  @param   resource image
		 *  @param   array points
		 *  @param   string bordercolor (optional, default null)
		 *  @param   string fillcolor (optional, default null)
		 *  @return  bool
		 *  @syntax  string CoreMediaImageShape->_draw2D( resource image, array points [, string bordercolor [, string fillcolor ] ] )
		 */
		protected function _draw2D( $mImage, $aPoint, $sBorderColor=null, $sFillColor=null )
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

		/**
		 *  draw shapes as 3D image
		 *  @name    _draw3D
		 *  @type    method
		 *  @access  protected
		 *  @param   resource image
		 *  @param   array points
		 *  @param   string bordercolor (optional, default null)
		 *  @param   string fillcolor (optional, default null)
		 *  @return  bool
		 *  @syntax  string CoreMediaImageShape->_draw3D( resource image, array points [, string bordercolor [, string fillcolor ] ] )
		 *  @note    drawing 3D image is considered experimental (and ugly)
		 */
		protected function _draw3D( $mImage, $aPoint, $sBorderColor=null, $sFillColor=null )
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
		
		/**
		 *  calculate several points based on a circle
		 *  @name    _calculateRing
		 *  @type    method
		 *  @access  protected
		 *  @param   int x
		 *  @param   int y
		 *  @param   int inner radius
		 *  @param   int outer radius
		 *  @param   int angle (optional, default 0)
		 *  @return  array points
		 *  @syntax  array CoreMediaImageShape->_calculateRing( int x, int y, int innerradiuas, int outerradius [, int angle ] )
		 */
		protected function _calculateRing( $nX, $nY, $nRadius, $nSegment, $nRotation=0 )
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

		/**
		 *  create a point 'object' (array)
		 *  @name    _point
		 *  @type    method
		 *  @access  protected
		 *  @param   int x
		 *  @param   int y
		 *  @param   int z (optional, default 0)
		 *  @return  array point
		 *  @syntax  array CoreMediaImageShape->_point( int x, int y [, int z ] )
		 */
		protected function _point( $nX, $nY, $nZ=0 )
		{
			return Array( $nX, $nY, $nZ );
		}

		/**
		 *  convert points to 3D space
		 *  @name    _resolvePoints
		 *  @type    method
		 *  @access  protected
		 *  @param   array points
		 *  @param   resource image (optional, only used in case the image is build bottomUp)
		 *  @return  array points
		 *  @syntax  array CoreMediaImageShape->_resolvePoints( array points [, resource image ] )
		 */
		protected function _resolvePoints( $aPoint, $mImage=null )
		{
			if ( $this->_bottomIsY && is_resource( $mImage ) )
				$nHeight = imagesy( $mImage );

			$aPoint = $this->_applyRotation( $aPoint );

			$aReturn = Array();
			for ( $i = 0; $i < count( $aPoint ); ++$i )
				array_push( $aReturn, $aPoint[ $i ][ 0 ], isset( $nHeight ) ? $nHeight - $aPoint[ $i ][ 1 ] : $aPoint[ $i ][ 1 ] );
			return $aReturn;
		}

		/**
		 *  applyRotation to a point
		 *  @name    _applyRotation
		 *  @type    method
		 *  @access  protected
		 *  @param   array point
		 *  @param   int X (optional, default 0)
		 *  @param   int Y (optional, default 0)
		 *  @param   int Z (optional, default 0)
		 *  @return  array point
		 *  @syntax  array CoreMediaImageShape->_applyRotation( array point [, int X [, int Y [, int Z ] ] ] )
		 */
		protected function _applyRotation( $aPoint, $nRotationX=0, $nRotationY=0, $nRotationZ=0 )
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