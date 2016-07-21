<?php

  /*
   *            ________ ___
   *           /   /   /\  /\       Konsolidate
   *      ____/   /___/  \/  \
   *     /           /\      /      http://www.konsolidate.nl
   *    /___     ___/  \    /
   *    \  /   /\   \  /    \       Class:  CoreConfigYAML
   *     \/___/  \___\/      \      Tier:   Core
   *      \   \  /\   \  /\  /      Module: Config/YAML
   *       \___\/  \___\/  \/
   *         \          \  /
   *          \___    ___\/
   *              \   \  /
   *               \___\/
   */


  /**
   *  Read yaml files and store yml-sections/variables for re-use in the Config Module
   *  @name    CoreConfigYAML
   *  @type    class
   *  @package Konsolidate
   *  @author  John Beitler <john@konsolidate.nl>
   */
  class CoreConfigYAML extends Konsolidate
  {
    /**
     *  Load and parse a YAML file and store it's sections/variables in the Config Module
     *  @name    load
     *  @type    method
     *  @access  public
     *  @param   string  YAML file
     *  @param   string  segment
     *  @param   int     Document to extract from stream (-1 for all documents, 0 for first document, ...).
     *  @param   int     If ndocs is provided, then it is filled with the number of documents found in stream.
     *  @param   array   Content handlers for YAML nodes.
     *  @returns array
     *  @syntax  Object->load( string YAML file [, string segment = null [, int pos = 0 [, int &docs [, array $callbacks]]]] )
     */
    public function load( $sFile, $sSegment=null, $nPos=0, &$nDocs=null, &$aCallbacks=array())
    {
      if ( !function_exists( "yaml_parse_file" ) ) {
        Throw new Exception( "To use YAML files, please install the YAML extension." );
        return false;
      }

      $aConfig = yaml_parse_file( $sFile, $nPos, $nDocs, $aCallbacks );
      $aReturn = Array();
      foreach( $aConfig as $sPrefix=>$mValue )
      {
        if ( is_array( $mValue ) )
        {
          $aReturn[ $sPrefix ] = array_key_exists( "default", $aReturn ) ? $aReturn[ "default" ] : Array();
          foreach( $mValue as $sKey=>$sValue )
          {
            $aReturn[ $sPrefix ][ $sKey ] = $sValue;
            $this->set( "/Config/{$sPrefix}/$sKey", $sValue );
          }
        }
        else
        {
          $aReturn[ $sPrefix ] = $mValue;
          $this->set( "/Config/{$sPrefix}", $mValue );
        }
      }

      if ( !is_null( $sSegment ) && array_key_exists( $sSegment, $aReturn ) )
        return $aReturn[ $sSegment ];

      return $aReturn;
    }

    /**
     *  Load and parse a YAML file and create defines
     *  @name    loadAndDefine
     *  @type    method
     *  @param   string  YAML file
     *  @param   string  segment
     *  @param   int     Document to extract from stream (-1 for all documents, 0 for first document, ...).
     *  @param   int     If ndocs is provided, then it is filled with the number of documents found in stream.
     *  @param   array   Content handlers for YAML nodes.
     *  @returns void
     *  @syntax  Object->loadAndDefine( string YAML file [, string segment = null [, int pos = 0 [, int &docs [, array $callbacks]]]] )
     *  @note    defines are formatted like [SECTION]_[KEY]=[VALUE]
     */
    public function loadAndDefine( $sFile, $sSegment=null, $nPos=0, &$nDocs=null, &$aCallbacks=array())
    {
      $aConfig = $this->load( $sFile, $sSegment );
      foreach( $aConfig as $sPrefix=>$aValue )
        foreach( $aValue as $sKey=>$sValue )
        {
          $sConstant = strToUpper( "{$sPrefix}_{$sKey}" );
          if ( !defined( $sConstant ) )
            define( $sConstant, $sValue );
        }
    }
  }

?>