<?php
//
// Definition of eZOoimport class
//
// Created on: <17-Jan-2005 09:11:41 bf>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 3.9.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2014 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezooimport.php
 */

/*!
  \class eZOoimport ezooimport.php
  \brief The class eZOoimport does

*/

class eZOOImport
{
    const ERROR_NOERROR = 0;
    const ERROR_UNSUPPORTEDTYPE = 1;
    const ERROR_PARSEXML = 2;
    const ERROR_OPENSOCKET = 3;
    const ERROR_CONVERT = 4;
    const ERROR_DAEMONCALL = 5;
    const ERROR_DAEMON = 6;
    const ERROR_DOCNOTSUPPORTED = 7;
    const ERROR_FILENOTFOUND = 8;
    const ERROR_PLACEMENTINVALID = 9;
    const ERROR_CANNOTSTORE = 10;
    const ERROR_UNKNOWNNODE = 11;
    const ERROR_ACCESSDENIED = 12;
    const ERROR_IMPORTING = 13;
    const ERROR_UNKNOWNCLASS = 14;
    const ERROR_UNKNOWN = 127;

    const NAMESPACE_OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    const NAMESPACE_TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    const NAMESPACE_STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    const NAMESPACE_TABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    const NAMESPACE_DRAWING = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    const NAMESPACE_SVG_COMPATIBLE = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';
    const NAMESPACE_FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    const NAMESPACE_XLINK = 'http://www.w3.org/1999/xlink';

    var $ERROR = array();
    var $currentUserID;

    /*!
     Constructor
    */
    function eZOOImport()
    {
        $this->ERROR['number'] = 0;
        $this->ERROR['value'] = '';
        $this->ERROR['description'] = '';
        $currentUser = eZUser::currentUser();
        $this->currentUserID  = $currentUser->id();
        $this->ImportDir .= md5( time() ) . "/";
    }

    /*!
     Get error message
    */
    function getErrorMessage()
    {
        return $this->ERROR['value'] . " " . $this->ERROR['description'];
    }

    /*!
     Get number of the error that occured. If 0 is returned, no error occured
    */
    function getErrorNumber()
    {
        return $this->ERROR['number'];
    }

    /*!
    Set the errormessage when one occur.
    */
    function setError( $errorNumber = 0, $errorDescription = "" )
    {
        $this->ERROR['number'] = $errorNumber;
        $this->ERROR['description'] = $errorDescription;

        switch( $errorNumber )
        {
            case self::ERROR_NOERROR :
                $this->ERROR['value'] = "";
                break;
            case self::ERROR_UNSUPPORTEDTYPE :
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "File extension or type is not allowed." );
                break;
            case self::ERROR_PARSEXML :
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Could not parse XML." );
                break;
            case self::ERROR_OPENSOCKET :
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Can not open socket. Please check if extension/ezodf/daemon.php is running." );
                break;
            case self::ERROR_CONVERT :
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Can not convert the given document." );
                break;
            case self::ERROR_DAEMONCALL :
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Unable to call daemon. Fork can not create child process." );
                break;
            case self::ERROR_DAEMON :
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Daemon reported error." );
                break;
            case self::ERROR_UNKNOWNNODE:
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Unknown node." );
                break;
            case self::ERROR_ACCESSDENIED:
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Access denied." );
                break;
            case self::ERROR_IMPORTING:
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Error during import." );
                break;
            case self::ERROR_UNKNOWNCLASS:
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Unknown content class specified in odf.ini:" );
                break;
            default :
                $this->ERROR['value'] = ezpI18n::tr( 'extension/ezodf/import/error', "Unknown error." );
                break;
        }
    }

    /*!
      Connects to the eZ Publish document conversion daemon and converts the document to OpenOffice.org Writer
    */
    function daemonConvert( $sourceFile, $destFile )
    {
        $ooINI = eZINI::instance( 'odf.ini' );
        $server = $ooINI->variable( "ODFImport", "OOConverterAddress" );
        $port = $ooINI->variable( "ODFImport", "OOConverterPort" );
        $res = false;
        $fp = fsockopen( $server,
                         $port,
                         $errorNR,
                         $errorString,
                         10 ); // @as 2008-11-25: change the timeout from 0 to 10 to avoid problems with connection

        if ( $fp )
        {
            $welcome = fread( $fp, 1024 );

            $welcome = trim( $welcome );
            if ( $welcome == "eZ Publish document conversion daemon" )
            {
                $commandString = "convertToOOo $sourceFile $destFile";

                fputs( $fp, $commandString, strlen( $commandString ) );
                $result = fread( $fp, 1024 );
                $result = trim( $result );
//              print( "client got: $result\n" );
                if( substr( $result, 0, 5 ) != "Error" )
                {
                    $res = true;
                }
                else
                {
                    $this->setError( self::ERROR_DAEMON, $result );
                    $res = false;
                }
             }
             else
             {
                 $this->setError( self::ERROR_DAEMONCALL );
                 $res = false;
             }
             fclose( $fp );
        }
        else
        {
            $this->setError( self::ERROR_OPENSOCKET );
            $res = false;
        }

        return $res;
    }

    /*!
     \deprecated
     Deprecated due to spelling mistake in the name. Left for backward compatibility. Should be removed in the future versions.
     Please use eZOOImport::daemonConvert() method instead.
    */
    function deamonConvert( $sourceFile, $destFile )
    {
        return $this->daemonConvert( $sourceFile, $destFile );
    }

    /**
     * Creates a new content or updates an existing one based on $file
     *
     * @param string $file the file to import
     * @param int $placeNodeID the node id where to place the new document or
     *        the node of the document to update
     * @param string $originalFileName
     * @param string $importType "import" or "replace"
     * @param eZContentUpload|null $upload (not used in this method)
     * @param string|false $locale the locale to use while creating/updating
     *        the content
     * @return array|false false if something went wrong
     */
    function import( $file, $placeNodeID, $originalFileName, $importType = "import", $upload = null, $locale = false )
    {
        $ooINI = eZINI::instance( 'odf.ini' );
        //$tmpDir = $ooINI->variable( 'ODFSettings', 'TmpDir' );
        // Use var-directory as temporary directory
        $tmpDir = getcwd() . "/" . eZSys::cacheDirectory();

        $allowedTypes = $ooINI->variable( 'DocumentType', 'AllowedTypes' );
        $convertTypes = $ooINI->variable( 'DocumentType', 'ConvertTypes' );

        $originalFileType = array_slice( explode('.',  $originalFileName), -1, 1 );
        $originalFileType = strtolower( $originalFileType[0] );

        if ( !in_array( $originalFileType,$allowedTypes, false ) and !in_array( $originalFileType, $convertTypes, false ) )
        {
            $this->setError( self::ERROR_UNSUPPORTEDTYPE, ezpI18n::tr( 'extension/ezodf/import/error',"Filetype: " ). $originalFileType );
            return false;
        }

        // If replacing/updating the document we need the ID.
        if ( $importType == "replace" )
             $GLOBALS["OOImportObjectID"] = $placeNodeID;

        // Check if we have access to node
        $place_node = eZContentObjectTreeNode::fetch( $placeNodeID );

        $importClassIdentifier = $ooINI->variable( 'ODFImport', 'DefaultImportClass' );

        // Check if class exist
        $class = eZContentClass::fetchByIdentifier( $importClassIdentifier );
        if ( !is_object( $class ) )
        {
            eZDebug::writeError( "Content class <strong>$importClassIdentifier</strong> specified in odf.ini does not exist." );
            $this->setError( self::ERROR_UNKNOWNCLASS, $importClassIdentifier );
            return false;
        }

        if ( !is_object( $place_node ) )
        {
            $locationOK = false;

            if ( $upload !== null )
            {
                $parentNodes = false;
                $parentMainNode = false;
                $locationOK = $upload->detectLocations( $importClassIdentifier, $class, $placeNodeID, $parentNodes, $parentMainNode );
            }

            if ( $locationOK === false || $locationOK === null )
            {
                $this->setError( self::ERROR_UNKNOWNNODE, ezpI18n::tr( 'extension/ezodf/import/error', "Unable to fetch node with id ") . $placeNodeID );
                return false;
            }

            $placeNodeID = $parentMainNode;
            $place_node = eZContentObjectTreeNode::fetch( $placeNodeID );
        }


        // Check if document conversion is needed
        //
        // Alex 2008/04/21 - added !== false
        if ( in_array( $originalFileType, $convertTypes, false ) !== false )
        {
            $uniqueStamp = md5( time() );
            $tmpFromFile = $tmpDir . "/convert_from_$uniqueStamp.doc";
            $tmpToFile   = $tmpDir . "/ooo_converted_$uniqueStamp.odt";
            copy( realpath( $file ),  $tmpFromFile );

           /// Convert document using the eZ Publish document conversion daemon
            if ( !$this->daemonConvert( $tmpFromFile, $tmpToFile ) )
            {
                if( $this->getErrorNumber() == 0 )
                    $this->setError( self::ERROR_CONVERT );
                return false;
            }
            // At this point we can unlink the sourcefile for conversion
            unlink( $tmpFromFile );

            // Overwrite the file location
            $file = $tmpToFile;
        }

        $importResult = array();
        $unzipResult = "";
        $uniqueImportDir = $this->ImportDir;
        eZDir::mkdir( $uniqueImportDir, false, true );

        $http = eZHTTPTool::instance();

        $archiveOptions = new ezcArchiveOptions( array( 'readOnly' => true ) );
        $archive = ezcArchive::open( $file, null, $archiveOptions );
        $archive->extract( $uniqueImportDir );

        $fileName = $uniqueImportDir . "content.xml";
        $dom = new DOMDocument( '1.0', 'UTF-8' );
        $success = $dom->load( $fileName );
        $sectionNodeHash = array();

        // At this point we could unlink the destination file from the conversion, if conversion was used
        if ( isset( $tmpToFile ) )
        {
            unlink( $tmpToFile );
        }

        if ( !$success )
        {
            $this->setError( self::ERROR_PARSEXML );
            return false;
        }


        // Fetch the automatic document styles
        $automaticStyleArray = $dom->getElementsByTagNameNS( self::NAMESPACE_OFFICE, 'automatic-styles' );
        if ( $automaticStyleArray->length == 1 )
        {
            $this->AutomaticStyles = $automaticStyleArray->item( 0 )->childNodes;
        }

        // Fetch the body section content
        $sectionNodeArray = $dom->getElementsByTagNameNS( self::NAMESPACE_TEXT, 'section' );

        $customClassFound = false;
        if ( $sectionNodeArray->length > 0 )
        {
            $registeredClassArray = $ooINI->variable( 'ODFImport', 'RegisteredClassArray' );

            // Check the defined sections in OO document
            $sectionNameArray = array();
            foreach ( $sectionNodeArray as $sectionNode )
            {
                $sectionNameArray[] = strtolower( $sectionNode->getAttributeNS( self::NAMESPACE_TEXT, "name" ) );
            }

            // Check if there is a coresponding eZ Publish class for this document
            foreach ( $registeredClassArray as $className )
            {
                $attributeArray = $ooINI->variable( $className, 'Attribute' );

                if ( !empty( $attributeArray ) )
                {
                    // Convert space to _ in section names
                    foreach ( $sectionNameArray as $key => $value )
                    {
                        $sectionNameArray[$key] = str_replace( " ", "_", $value );
                    }

                    sort( $attributeArray );
                    sort( $sectionNameArray );

                    $diff = array_diff( $attributeArray, $sectionNameArray );
                    if ( empty( $diff ) )
                    {
                        $importClassIdentifier = $className;
                        $customClassFound = true;
                        break;
                    }
                }
            }

            if ( $customClassFound == true )
            {
                foreach ( $sectionNodeArray as $sectionNode )
                {
                    $sectionName = str_replace( " ", "_", strtolower( $sectionNode->getAttributeNS( self::NAMESPACE_TEXT, 'name' ) ) );
                    $xmlText = "";
                    $level = 1;
                    $childArray = $sectionNode->childNodes;
                    $nodeCount = 1;
                    foreach ( $childArray as $childNode )
                    {
                        if ( $childNode->nodeType === XML_ELEMENT_NODE )
                        {
                            $isLastTag = ( $nodeCount == $childArray->length );
                            $xmlText .= self::handleNode( $childNode, $level, $isLastTag );
                        }

                        $nodeCount++;
                    }
                    $endSectionPart = "";
                    $levelDiff = 1 - $level;
                    if ( $levelDiff < 0 )
                        $endSectionPart = str_repeat( "</section>", abs( $levelDiff ) );
                    $charset = eZTextCodec::internalCharset();

                    // Store the original XML for each section, since some datatypes needs to handle the XML specially
                    $sectionNodeHash[$sectionName] = $sectionNode;

                    $xmlTextArray[$sectionName] = "<?xml version='1.0' encoding='$charset' ?>" .
                         "<section xmlns:image='http://ez.no/namespaces/ezpublish3/image/' " .
                         "  xmlns:xhtml='http://ez.no/namespaces/ezpublish3/xhtml/'><section>" . $xmlText . $endSectionPart . "</section></section>";
                }
            }
        }

        if ( $customClassFound == false )
        {
            // No defined sections. Do default import.
            $bodyNodeArray = $dom->getElementsByTagNameNS( self::NAMESPACE_OFFICE, 'text' );

            // Added by Soushi
            // check the parent-style-name [ eZSectionDefinition ]
            $eZSectionDefinitionStyleName = array();
            foreach( $automaticStyleArray->item( 0 )->childNodes as $child )
            {
                if( $child->nodeType === XML_ELEMENT_NODE && 
                        $child->getAttributeNS( self::NAMESPACE_STYLE, 'parent-style-name' ) === 'eZSectionDefinition' )
                {
                     $eZSectionDefinitionStyleName[] = $child->getAttributeNS( self::NAMESPACE_STYLE, 'name' );
                }
            }

            $sectionNameArray = array();
            $sectionNodeArray = array();
            $paragraphSectionName = NULL;
            $firstChildFlag = false;
            $paragraphSectionNodeArray = array();
            foreach ( $bodyNodeArray->item( 0 )->childNodes as $childNode )
            {
                $firstChildFlag = true;
                if ( $childNode->nodeType === XML_ELEMENT_NODE &&
                     ( in_array( $childNode->getAttributeNS( self::NAMESPACE_TEXT, 'style-name' ), $eZSectionDefinitionStyleName ) ||
                       $childNode->getAttributeNS( self::NAMESPACE_TEXT, 'style-name' ) === 'eZSectionDefinition' )
                   )
                {
                    $firstChildFlag = false;

                    $childNodeChildren = $childNode->childNodes;
                    $paragraphSectionName = trim( $childNodeChildren->item( 0 )->textContent );
                    $sectionNameArray[] = $paragraphSectionName;
                }

                if ( $paragraphSectionName && $firstChildFlag )
                {
                    $paragraphSectionNodeArray[$paragraphSectionName][] = $childNode;
                }
            }

            $sectionNodeArray = array();
            foreach ( $paragraphSectionNodeArray as $key => $childNodes )
            {
                $sectionNode = $dom->createElement( 'section' );

                foreach ( $childNodes as $childNode )
                {
                    $sectionNode->appendChild( $childNode );
                }

                $sectionNodeArray[$key] = $sectionNode;
            }

            $customClassFound = false;
            if ( $sectionNameArray )
            {
                $registeredClassArray = $ooINI->variable( 'ODFImport', 'RegisteredClassArray' );

                // Check if there is a coresponding eZ Publish class for this document
                foreach ( $registeredClassArray as $className )
                {
                    $attributeArray = $ooINI->variable( $className, 'Attribute' );

                    if ( !empty( $attributeArray ) )
                    {
                        // Convert space to _ in section names
                        foreach ( $sectionNameArray as $key => $value )
                        {
                            $sectionNameArray[$key] = str_replace( " ", "_", $value );
                        }

                        sort( $attributeArray );
                        sort( $sectionNameArray );

                        $diff = array_diff( $attributeArray, $sectionNameArray );
                        if ( empty( $diff ) )
                        {
                            $importClassIdentifier = $className;
                            $customClassFound = true;
                            break;
                        }
                    }
                }
            }

            if ( $sectionNameArray && $customClassFound == true )
            {
                foreach ( $sectionNodeArray as $key => $sectionNode )
                {
                    $sectionName = str_replace( " ", "_", $key );
                    $xmlText = "";
                    $level = 1;
                    foreach ( $sectionNode->childNodes as $childNode )
                    {
                        $isLastTag = !isset( $childNode->nextSibling );
                        $xmlText .= self::handleNode( $childNode, $level, $isLastTag );
                    }
                    $endSectionPart = "";
                    $levelDiff = 1 - $level;
                    if ( $levelDiff < 0 )
                        $endSectionPart = str_repeat( "</section>", abs( $levelDiff ) );
                    $charset = eZTextCodec::internalCharset();

                    // Store the original XML for each section, since some datatypes needs to handle the XML specially
                    $sectionNodeHash[$sectionName] = $sectionNode;

                    $xmlTextArray[$sectionName] = "<?xml version='1.0' encoding='$charset' ?>" .
                        "<section xmlns:image='http://ez.no/namespaces/ezpublish3/image/' " .
                        "  xmlns:xhtml='http://ez.no/namespaces/ezpublish3/xhtml/'><section>" . $xmlText . $endSectionPart . "</section></section>";
                }
            }
            else if ( $bodyNodeArray->length === 1 )
            {
                $xmlText = "";
                $level = 1;
                foreach ( $bodyNodeArray->item( 0 )->childNodes as $childNode )
                {
                    $xmlText .= self::handleNode( $childNode, $level );
                }

                $endSectionPart = "";
                $levelDiff = 1 - $level;
                if ( $levelDiff < 0 )
                    $endSectionPart = str_repeat( "</section>", abs( $levelDiff ) );

                $charset = eZTextCodec::internalCharset();
                $xmlTextBody = "<?xml version='1.0' encoding='$charset' ?>" .
                     "<section xmlns:image='http://ez.no/namespaces/ezpublish3/image/' " .
                     "  xmlns:xhtml='http://ez.no/namespaces/ezpublish3/xhtml/'><section>" . $xmlText . $endSectionPart . "</section></section>";
            }
        }

        if ( $importType == "replace" )
        {
            // Check if we are allowed to edit the node
            $functionCollection = new eZContentFunctionCollection();
            $access = $functionCollection->checkAccess(
                'edit', $place_node, false, false, $locale
            );
        }
        else
        {
            // Check if we are allowed to create a node under the node
            $functionCollection = new eZContentFunctionCollection();
            $access = $functionCollection->checkAccess(
                'create', $place_node, $importClassIdentifier,
                $place_node->attribute( 'class_identifier' ), $locale
            );
        }

        if ( $access['result'] )
        {
            // Check if we should replace the current object or import a new
            if ( $importType !== "replace" )
            {
                $class = eZContentClass::fetchByIdentifier( $importClassIdentifier );

                $place_object = $place_node->attribute( 'object' );
                $sectionID = $place_object->attribute( 'section_id' );

                $creatorID = $this->currentUserID;
                $parentNodeID = $placeNodeID;

                if ( !is_object( $class ) )
                {
                    eZDebug::writeError( "Content class <strong>$importClassIdentifier</strong> specified in odf.ini does not exist." );
                    $this->setError( self::ERROR_UNKNOWNCLASS, $importClassIdentifier );
                    return false;
                }

                $object = $class->instantiate( $creatorID, $sectionID, false, $locale );

                $nodeAssignment = eZNodeAssignment::create( array(
                                                                 'contentobject_id' => $object->attribute( 'id' ),
                                                                 'contentobject_version' => $object->attribute( 'current_version' ),
                                                                 'parent_node' => $parentNodeID,
                                                                 'is_main' => 1
                                                                 )
                                                             );
                $nodeAssignment->store();

                $version = $object->version( 1 );
                $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
                $version->store();
                $dataMap = $object->dataMap();
            }
            else
            {
                // Check if class is supported before we start changing anything
                $placeClassIdentifier = $place_node->attribute( 'class_identifier' );
                if ( $ooINI->hasVariable( $placeClassIdentifier, 'DefaultImportTitleAttribute' ) &&
                     $ooINI->hasVariable( $placeClassIdentifier, 'DefaultImportBodyAttribute' ) )
                {
                    $titleAttribute = $ooINI->variable( $placeClassIdentifier, 'DefaultImportTitleAttribute');
                    $bodyAttribute = $ooINI->variable( $placeClassIdentifier, 'DefaultImportBodyAttribute' );

                    // Extra check to see if attributes exist in dataMap (config is not wrong)
                    $dataMap = $place_node->attribute( 'data_map' );
                    if ( (!isset( $dataMap[ $titleAttribute ] ) ) || ( !isset( $dataMap[ $bodyAttribute ] ) ) )
                    {
                        $this->setError( self::ERROR_IMPORTING, "Error in configuration for $placeClassIdentifier, please check configuration file." );
                        return false;
                    }
                    unset( $dataMap );
                }
                else
                {
                    $this->setError( self::ERROR_IMPORTING, "No settings for replacing node of type $placeClassIdentifier. Stopping.");
                    return false;
                }

                // Change class for importing
                $importClassIdentifier = $placeClassIdentifier;

                // already fetched: $node = eZContentObjectTreeNode::fetch( $placeNodeID );
                $object = $place_node->attribute( 'object' );
                $version = $object->createNewVersionIn( $locale );

                $dataMap = $version->dataMap();
            }
            $contentObjectID = $object->attribute( 'id' );

            if ( $customClassFound == true )
            {
                // Initialize the actual object attributes
                $attributeArray = $ooINI->variable( $importClassIdentifier, 'Attribute' );
                foreach ( $attributeArray as $attributeIdentifier => $sectionName  )
                {
                    switch( $dataMap[$attributeIdentifier]->DataTypeString )
                    {
                        case "ezstring":
                        case "eztext":
                        {
                            if ( !isset( $xmlTextArray[$sectionName] ) )
                            {
                                continue;
                            }
                            $eztextDom = new DOMDOcument( '1.0', 'UTF-8' );
                            $eztextDom->loadXML( $xmlTextArray[$sectionName] );
                            $text = $eztextDom->documentElement->textContent;
                            $dataMap[$attributeIdentifier]->setAttribute( 'data_text', trim( $text ) );
                            $dataMap[$attributeIdentifier]->store();
                        }break;

                        case "ezxmltext":
                        {
                            if ( !isset( $xmlTextArray[$sectionName] ) )
                            {
                                continue;
                            }
                            $dataMap[$attributeIdentifier]->setAttribute( 'data_text', $xmlTextArray[$sectionName] );
                            $dataMap[$attributeIdentifier]->store();
                        }break;


                        case "ezdate":
                        {
                            // Only support date formats as a single paragraph in a section with the format:
                            // day/month/year
                            if ( !isset( $xmlTextArray[$sectionName] ) )
                            {
                                continue;
                            }
                            $dateString = strip_tags( $xmlTextArray[$sectionName] );

                            $dateArray = explode( "/", $dateString );

                            if ( count( $dateArray ) == 3 )
                            {
                                    $year = $dateArray[2];
                                    $month = $dateArray[1];
                                    $day = $dateArray[0];

                                    $date = new eZDate();

                                    $contentClassAttribute = $dataMap[$attributeIdentifier];

                                    $date->setMDY( $month, $day, $year );
                                    $dataMap[$attributeIdentifier]->setAttribute( 'data_int', $date->timeStamp()  );
                                    $dataMap[$attributeIdentifier]->store();
                            }
                        }break;

                        case "ezdatetime":
                        {
                            // Only support date formats as a single paragraph in a section with the format:
                            // day/month/year 14:00
                            if ( !isset( $xmlTextArray[$sectionName] ) )
                            {
                                continue;
                            }
                            $dateString = trim( strip_tags( $xmlTextArray[$sectionName] ) );

                            $dateTimeArray = explode(  " ", $dateString );

                            $dateArray = explode( "/", $dateTimeArray[0] );
                            $timeArray = explode( ":", $dateTimeArray[1] );

                            if ( count( $dateArray ) == 3 and count( $timeArray ) == 2 )
                            {
                                $year = $dateArray[2];
                                $month = $dateArray[1];
                                $day = $dateArray[0];

                                $hour = $timeArray[0];
                                $minute = $timeArray[1];

                                $dateTime = new eZDateTime();

                                $contentClassAttribute = $dataMap[$attributeIdentifier];

                                $dateTime->setMDYHMS( $month, $day, $year, $hour, $minute, 0 );
                                $dataMap[$attributeIdentifier]->setAttribute( 'data_int', $dateTime->timeStamp()  );
                                $dataMap[$attributeIdentifier]->store();
                            }
                        }break;

                        case "ezimage":
                        {
                            $hasImage = false;

                            // Images are treated as an image object inside a paragrah.
                            // We fetch the first image object if there are multiple and ignore the rest
                            if ( is_object( $sectionNodeHash[$sectionName] ) )
                            {
                                // Look for paragraphs in the section
                                foreach ( $sectionNodeHash[$sectionName]->childNodes as $paragraph )
                                {
                                    if( !$paragraph->hasChildNodes() ) 
                                    {
                                        continue;
                                    }
                                    // Look for frame node
                                    foreach ( $paragraph->childNodes as $frame )
                                    {
                                        // finally look for the image node
                                        $children = $frame->childNodes;

                                        $imageNode = $children->item( 0 );
                                        if ( $imageNode && $imageNode->localName == "image" )
                                        {
                                            $fileName = ltrim( $imageNode->getAttributeNS( self::NAMESPACE_XLINK, 'href' ), '#' );
                                            $filePath = $this->ImportDir . $fileName;

                                            if ( file_exists( $filePath ) )
                                            {
                                                $imageContent = $dataMap[$attributeIdentifier]->attribute( 'content' );
                                                $imageContent->initializeFromFile( $filePath, false, basename( $filePath ) );
                                                $imageContent->store( $dataMap[$attributeIdentifier] );
                                                $dataMap[$attributeIdentifier]->store();
                                            }

                                            $hasImage = true;
                                        }
                                    }
                                }
                            }

                            if ( !$hasImage )
                            {
                                $imageHandler = $dataMap[$attributeIdentifier]->attribute( 'content' );
                                if ( $imageHandler )
                                    $imageHandler->removeAliases( $dataMap[$attributeIdentifier] );
                            }

                        }break;

                        case "ezmatrix":
                        {
                            $matrixHeaderArray = array();
                            // Fetch the current defined columns in the matrix
                            $matrix = $dataMap[$attributeIdentifier]->content();
                            $columns = $matrix->attribute( "columns" );

                            foreach ( $columns['sequential'] as $column )
                            {
                                $matrixHeaderArray[] = $column['name'];
                            }

                            $headersValid = true;
                            $originalHeaderCount = count( $matrixHeaderArray );
                            $headerCount = 0;
                            $rowCount = 0;
                            $cellArray = array();
                            // A matrix is supported as a table inside sections. If multiple tables are present we take the first.
                            if ( is_object( $sectionNodeHash[$sectionName] ) )
                            {
                                // Look for paragraphs in the section
                                foreach ( $sectionNodeHash[$sectionName]->childNodes as $table )
                                {
                                    if ( $table->localName == "table" )
                                    {
                                        // Loop the rows in the table
                                        foreach ( $table->childNodes as $row )
                                        {
                                            // Check the headers and compare with the defined matrix
                                            if ( $row->localName == "table-header-rows" )
                                            {
                                                $rowArray = $row->childNodes;
                                                if ( $rowArray->length == 1  )
                                                {
                                                    foreach ( $rowArray->item( 0 )->childNodes as $headerCell )
                                                    {
                                                        if ( $headerCell->localName == "table-cell" )
                                                        {
                                                            $paragraphArray = $headerCell->childNodes;

                                                            if ( $paragraphArray->length == 1 )
                                                            {
                                                                $headerName = $paragraphArray->item( 0 )->textContent;
                                                                if ( $matrixHeaderArray[$headerCount] != $headerName )
                                                                {
                                                                    $headersValid = false;
                                                                }
                                                                $headerCount++;
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            // Check the rows
                                            if ( $row->localName == "table-row" )
                                            {
                                                foreach ( $row->childNodes as $cell )
                                                {
                                                    if ( $cell->childNodes->length >= 1 )
                                                    {
                                                        $firstParagraph = $cell->childNodes->item( 0 );
                                                        $cellArray[] = $firstParagraph->textContent;
                                                    }
                                                }
                                                $rowCount++;
                                            }
                                        }
                                    }
                                }
                            }

                            if ( $headerCount == $originalHeaderCount and
                                 $headersValid == true )
                            {
                                // Remove all existing rows
                                for ( $i=0; $i < $matrix->attribute( "rowCount" ); $i++ )
                                {
                                    $matrix->removeRow( $i );
                                }

                                // Insert new rows
                                $matrix->addRow( false, $rowCount );
                                $matrix->Cells = $cellArray;

                                $dataMap[$attributeIdentifier]->setAttribute( 'data_text', $matrix->xmlString() );

                                $matrix->decodeXML( $dataMap[$attributeIdentifier]->attribute( 'data_text' ) );
                                $dataMap[$attributeIdentifier]->setContent( $matrix );
                                $dataMap[$attributeIdentifier]->store();
                            }

                        }break;

                        default:
                        {
                            eZDebug::writeError( "Unsupported datatype for OpenOffice.org import: " . $dataMap[$attributeIdentifier]->DataTypeString );
                        }break;
                    }
                }
            }
            else
            {
                // Check if attributes are already fetched
                if ( ( !isset ( $titleAttribute ) ) || ( !isset ( $bodyAttribute ) ) )
                {
                    // Set attributes accorring to import class
                    $titleAttribute = $ooINI->variable( $importClassIdentifier, 'DefaultImportTitleAttribute');
                    $bodyAttribute = $ooINI->variable( $importClassIdentifier, 'DefaultImportBodyAttribute' );
                }

                $objectName = basename( $originalFileName );

                // Remove extension from name
                $objectName = preg_replace( "/(\....)$/", "", $objectName );
                // Convert _ to spaces and upcase the first character
                $objectName = ucfirst( str_replace( "_", " ", $objectName ) );

                $dataMap[$titleAttribute]->setAttribute( 'data_text', $objectName );
                $dataMap[$titleAttribute]->store();

                $dataMap[$bodyAttribute]->setAttribute( 'data_text', $xmlTextBody );
                $dataMap[$bodyAttribute]->store();
            }

            $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                         'version' => $version->attribute( 'version' ) ) );

            $storeImagesInMedia = $ooINI->variable( "ODFImport", "PlaceImagesInMedia" ) == "true";
            if ( $storeImagesInMedia == true )
            {
                // Fetch object to get correct name
                $object = eZContentObject::fetch( $contentObjectID );

                // Create image folder if it does not already exist
                {
                    $contentINI = eZINI::instance( 'content.ini' );
                    $mediaRootNodeID = $contentINI->variable( "NodeSettings", "MediaRootNode" );

                    $node = eZContentObjectTreeNode::fetch( $mediaRootNodeID );

                    $articleFolderName = $object->attribute( 'name' );
                    $importFolderName = $ooINI->variable( 'ODFImport', 'ImportedImagesMediaNodeName' );
                    $importNode = self::createSubNode( $node, $importFolderName );

                    $articleNode = self::createSubNode( $importNode, $articleFolderName );
                    $imageRootNode = $articleNode->attribute( "node_id" );
                }
            }
            else
            {
                $imageRootNode = $object->attribute( "main_node_id" );
            }

            // Publish all embedded images as related objects
            foreach ( $this->RelatedImageArray as $image )
            {

                // Publish related images
                $nodeAssignment = eZNodeAssignment::create( array(
                                                                 'contentobject_id' => $image['ID'],
                                                                 'contentobject_version' => 1,
                                                                 'parent_node' => $imageRootNode,
                                                                 'is_main' => 1
                                                                 )
                                                             );
                $nodeAssignment->store();

                eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $image['ID'],
                                                                                             'version' => 1 ) );

                $object->addContentObjectRelation( $image['ID'], 1 );
            }

            $mainNode = $object->attribute( 'main_node' );
            // Create object stop.
            $importResult['Object'] = $object;
            $importResult['Published'] = ( $operationResult['status'] == eZModuleOperationInfo::STATUS_CONTINUE );
            if ( $mainNode instanceof eZContentObjectTreeNode )
            {
                $importResult['MainNode'] = $mainNode;
                $importResult['URLAlias'] = $mainNode->attribute( 'url_alias' );
                $importResult['NodeName'] = $mainNode->attribute( 'name' );
            }
            else
            {
                $importResult['MainNode'] = false;
                $importResult['URLAlias'] = false;
                $importResult['NodeName'] = false;
            }
            $importResult['ClassIdentifier'] = $importClassIdentifier;
        }
        else
        {
            $this->setError( self::ERROR_ACCESSDENIED );
            return false;
        }


        // Clean up
        eZDir::recursiveDelete( $uniqueImportDir );
        return $importResult;
    }


    /*!
      Handles DOM node in the OpenOffice.org writer document and returns the eZXMLText equivalent.
      If images are embedded in the document they will be imported as media objects in eZ Publish.
     */
    function handleNode( $node, &$sectionLevel, $isLastTag = false )
    {
        $xhtmlTextContent = "";
//    if ( $node->namespaceURI() == 'http://openoffice.org/2000/text' )
        {

            // If another tag than paragraph comes then terminate collapsing tags, if any
            if ( $node->localName != "p" and $this->CollapsingTagName != false )
            {
                $xhtmlTextContent .= '<paragraph>' . '<' . $this->CollapsingTagName . ' ' . $this->CollapsingTagAttribute . ' >' . $this->CollapsingTagContent . "</" . $this->CollapsingTagName . ">\n</paragraph>\n";
                $this->CollapsingTagContent = false;
                $this->CollapsingTagAttribute = false;
                $this->CollapsingTagName = false;
            }

            switch ( $node->localName )
            {
                case 'sequence-decls' :
                case 'forms' :
                {
                    // do nothing
                }break;


                case 'section' :
                {
                    foreach ( $node->childNodes as $childNode )
                    {
                        $xhtmlTextContent  .= self::handleNode( $childNode, $sectionLevel );
                    }
                }break;

                case 'h' :
                {
                    $level = $node->getAttributeNS( self::NAMESPACE_TEXT, 'outline-level' );

                    if ( $level > 6 )
                        $level = 6;

                    if ( $level >= 1 && $level <= 6 )
                    {
                        $levelDiff = $level - $sectionLevel;
                        $sectionLevel = $level;
                        $headerContent = "";

                        foreach ( $node->childNodes as $childNode )
                        {
                            // Alex 2008/04/21 - added initializations for $nextlineBreak and $prevlineBreak
                            $nextLineBreak = ( isset( $childNode->nextSibling ) &&
                                               $childNode->nextSibling->localName == 'line-break' );

                            $prevLineBreak = ( isset( $childNode->previousSibling ) &&
                                               $childNode->previousSibling->localName == 'line-break' );

                            $headerContent .= self::handleInlineNode( $childNode, $nextLineBreak, $prevLineBreak );
                        }
                        $sectionLevel = $level;

                        if ( $levelDiff > 0 )
                            $xhtmlTextContent .= str_repeat( "<section>", $levelDiff );

                        if ( $levelDiff < 0 )
                            $xhtmlTextContent .= str_repeat( "</section>", abs( $levelDiff ) );

                        $xhtmlTextContent .= "<header>" . $headerContent . "</header>\n";
                    }
                    else
                    {
                        eZDebug::writeError( "Unsupported header level $level<br>" . $node->textContent . "<br>" );
                    }
                }break;

                case 'p' :
                {
                    $styleName = $node->getAttributeNS( self::NAMESPACE_TEXT, 'style-name' );

                    $lastCollapsingTagName = $this->CollapsingTagName;

                    // Check for custom tags

                    if ( substr( $styleName, 0, 12 ) == "eZCustom_20_" )
                    {
                        $customName = substr( $styleName, 12, strlen( $styleName ) - 12 );
                        $this->CollapsingTagName = "custom";
                        $this->CollapsingTagAttribute = "name='$customName'";
                    }
                    else
                    {
                        switch ( $styleName )
                        {
                            case "Preformatted_20_Text" :
                            {
                                $this->CollapsingTagName = "literal";
                            }break;
                            default:
                            {
                                $this->CollapsingTagName = false;
                            }break;
                        }
                    }

                    // Check for bold and italic styles
                    // OOo does not have tags for these styles but set the style on the whole paragraph
                    $fontWeight = false;
                    $fontStyle = false;
                    $headerLevel = false;
                    foreach ( $this->AutomaticStyles as $style )
                    {
                        if ( $style->nodeType !== XML_ELEMENT_NODE )
                        {
                            continue;
                        }

                        $tmpStyleName = $style->getAttributeNS( self::NAMESPACE_STYLE, "name" );

                        if ( $styleName == $tmpStyleName )
                        {
                            foreach ( $style->childNodes as $styleChild )
                            {
                                $fontWeight = $styleChild->getAttributeNS( self::NAMESPACE_FO, 'font-weight' );
                                $fontStyle = $styleChild->getAttributeNS( self::NAMESPACE_FO, 'font-style' );
                            }

                            // Get the parent style name, it's used to see if it's a
                            // header which comes from Word conversion
                            $parentStyleName = $style->getAttributeNS( self::NAMESPACE_STYLE, "parent-style-name" );

                            // Check if we've got a header definition and which level Heading_20
                            // Header styles is either defined in style-name or parent-style-name when
                            // converting a Word document to OOo, here we check the parent-style-name
                            if ( substr( $parentStyleName, 0, 11 ) == "Heading_20_" )
                            {
                                $level = substr( $parentStyleName, 11, strlen( $parentStyleName )  );
                                if ( is_numeric( $level ) )
                                    $headerLevel = $level;
                            }

                        }
                    }

                    // Check if paragraph style is set to a header in the style-name
                    if ( substr( $styleName, 0, 11 ) == "Heading_20_" )
                    {
                        $level = substr( $styleName, 11, strlen( $styleName )  );
                        if ( is_numeric( $level ) )
                            $headerLevel = $level;
                    }

                    $preStyles = "";
                    if ( $fontWeight == "bold" )
                        $preStyles .= "<strong>";
                    if ( $fontStyle == "italic" )
                        $preStyles .= "<emphasize>";

                    $postStyles = "";
                    if ( $fontStyle == "italic" )
                        $postStyles .= "</emphasize>";
                    if ( $fontWeight == "bold" )
                        $postStyles .= "</strong>";

                    $paragraphContent = "";
                    foreach ( $node->childNodes as $childNode )
                    {
                        $nextLineBreak = ( isset( $childNode->nextSibling ) &&
                                           $childNode->nextSibling->localName == 'line-break' );

                        $prevLineBreak = ( isset( $childNode->previousSibling ) &&
                                           $childNode->previousSibling->localName == 'line-break' );

                        $paragraphContent .= self::handleInlineNode( $childNode, $nextLineBreak, $prevLineBreak );
                    }

                    // If current paragraph is actually a header, then skip sub-formatting
                    if ( $headerLevel !== false )
                    {
                        if ( $level > 6 )
                            $level = 6;

                        if ( $level >= 1 && $level <= 6 )
                        {
                            $levelDiff = $level - $sectionLevel;
                            $sectionLevel = $level;

                            if ( $levelDiff > 0 )
                                $xhtmlTextContent .= str_repeat( "<section>", $levelDiff );

                            if ( $levelDiff < 0 )
                                $xhtmlTextContent .= str_repeat( "</section>", abs( $levelDiff ) );

                            $xhtmlTextContent .= "<header>" . $paragraphContent . "</header>\n";
                        }
                    }
                    else
                    {
                        // Handle normal paragraphs

                        if ( $this->CollapsingTagName == false )
                        {
                            // Add collapsed tag, if beyond the last collapsing tag
                            if ( $lastCollapsingTagName !== false )
                            {
                                // Content should be quoted because it breaks the XML.
                                $tagContent = str_replace( "&", "&amp;", $this->CollapsingTagContent );
                                $tagContent = str_replace( ">", "&gt;", $tagContent );
                                $tagContent = str_replace( "<", "&lt;", $tagContent );
                                $tagContent = str_replace( "'", "&apos;", $tagContent );
                                $tagContent = str_replace( '"', "&quot;", $tagContent );

                                $collapsingTagAttrText = $this->CollapsingTagAttribute ? ' ' . $this->CollapsingTagAttribute : '';
                                $xhtmlTextContent .= '<paragraph>' . '<' . $lastCollapsingTagName . $collapsingTagAttrText . '>' . $tagContent . "</" . $lastCollapsingTagName . ">\n</paragraph>\n";
                                $this->CollapsingTagContent = false;
                                $this->CollapsingTagAttribute = false;
                            }

                            if ( trim( $paragraphContent ) != "" )
                            {
                                $xhtmlTextContent .= '<paragraph>' . $preStyles . $paragraphContent . $postStyles . "</paragraph>\n";
                            }
                        }
                        else
                        {
                            if ( $isLastTag == true )
                            {
                                if ( $this->CollapsingTagName != false )
                                    $lastCollapsingTagName = $this->CollapsingTagName;
                                $xhtmlTextContent .= '<paragraph>' . '<' . $lastCollapsingTagName . ' ' . $this->CollapsingTagAttribute . '>' . $paragraphContent . "</" . $lastCollapsingTagName . ">\n</paragraph>\n";
                                $this->CollapsingTagContent = false;
                                $this->CollapsingTagAttribute = false;
                                $this->CollapsingTagName = false;
                            }
                            else
                            {
                                if ( $this->CollapsingTagName == "custom" )
                                {
                                    if ( trim( $paragraphContent ) != "" )
                                    {
                                        $this->CollapsingTagContent .= '<paragraph>' . $preStyles . $paragraphContent . $postStyles . "</paragraph>\n";
                                    }
                                }
                                else
                                {
                                    $this->CollapsingTagContent .= $paragraphContent . "\n";
                                }
                            }
                        }

                    }
                }break;

                case 'numbered-paragraph' :
                {
                    $listContent = "";
                    foreach ( $node->childNodes as $itemNode )
                    {
                        if ( $itemNode->localName == 'p' )
                        {
                            $listContent .= "<li>" . strip_tags( self::handleNode( $itemNode, $sectionLevel ) ) . "</li>";
                        }
                    }

                    $xhtmlTextContent .= "<paragraph><ul>" . $listContent . "</ul></paragraph>\n";
                }break;

                case 'list' :
                {
                    $styleName = $node->getAttributeNS( self::NAMESPACE_TEXT, 'style-name' );

                    // Check list style for unordered/ordered list
                    $listType = false;
                    foreach ( $this->AutomaticStyles as $style )
                    {
                        if ( $style->nodeType !== XML_ELEMENT_NODE )
                        {
                            continue;
                        }

                        $tmpStyleName = $style->getAttributeNS( self::NAMESPACE_STYLE, "name" );

                        if ( $styleName == $tmpStyleName )
                        {
                            if ( $style->childNodes->length >= 1 )
                            {
                                $children = $style->childNodes;

                                switch ( $children->item( 0 )->localName )
                                {
                                    case "list-level-style-number":
                                    {
                                        $listType = "ordered";
                                        $this->InsideListType = "ordered";
                                    } break;

                                    case "list-level-style-bullet":
                                    {
                                        $listType = "unordered";
                                        $this->InsideListType = "unordered";
                                    } break;
                                }
                            }
                        }
                    }

                    if ( $listType == false )
                        $listType = $this->InsideListType;

                    $listItemCount = 0;
                    $listContent = "";

                    $isSubList = $this->IsSubList;
                    $this->IsSubList = true;
                    foreach ( $node->childNodes as $itemNode )
                    {
                        if ( $itemNode->localName == 'list-item' )
                        {
                            foreach ( $itemNode->childNodes as $childNode )
                            {
                                $listItemContent = self::handleNode( $childNode, $sectionLevel );

                                if ( substr( $listItemContent, 0, 4 ) == "<ol>" or
                                     substr( $listItemContent, 0, 4 ) == "<ul>" )
                                {
                                    $listContent .= $listItemContent;
                                }
                                else
                                {
                                    $endItemTag = "</li>";
                                    if ( $listItemCount == 0 )
                                        $endItemTag = "";
                                    $listContent .= "$endItemTag<li>" . $listItemContent;
                                }

                                $listItemCount++;
                            }
                        }
                    }

                    $this->IsSubList = $isSubList;

                    $paragraphPreTag = "<paragraph>";
                    $paragraphPostTag = "</paragraph>";

                    // If we are inside a list, ommit paragraph tag
                    if ( $this->IsSubList != false )
                    {
                        $paragraphPreTag = "";
                        $paragraphPostTag = "";
                    }

                    // Do not add empty lists
                    if ( $listItemCount > 0 )
                    {
                        if ( $listType == "ordered" )
                            $xhtmlTextContent .= "$paragraphPreTag<ol>" . $listContent . "</li></ol>$paragraphPostTag\n";
                        else
                        {
                            $xhtmlTextContent .= "$paragraphPreTag<ul>" . $listContent . "</li></ul>$paragraphPostTag\n";
                        }
                    }
                }break;

                case 'table' :
                {
                    $tableContent = "";
                    foreach ( $node->childNodes as $itemNode )
                    {
                        if ( $itemNode->localName == 'table-header-rows' )
                        {
                            foreach ( $itemNode->childNodes as $headerRow )
                            {
                                if ( $headerRow->localName == 'table-row' )
                                {
                                    $rowContent = "";
                                    foreach ( $headerRow->childNodes as $tableCell )
                                    {
                                        $colSpan = $tableCell->getAttributeNS( self::NAMESPACE_TABLE, 'number-columns-spanned' );

                                        if ( $tableCell->localName == 'table-cell' )
                                        {
                                            $cellContent = "";
                                            foreach ( $tableCell->childNodes as $tableContentNode )
                                            {
                                                $cellContent .= self::handleNode( $tableContentNode, $sectionLevel );
                                            }
                                            $colSpanXML = "";
                                            if ( is_numeric( $colSpan ) and $colSpan > 1 )
                                            {
                                                $colSpanXML = " xhtml:colspan='$colSpan' ";
                                            }
                                            $rowContent .= "<th $colSpanXML>" . $cellContent . "</th>";
                                        }
                                    }
                                    $tableContent .= "<tr>" . $rowContent . "</tr>";
                                }
                            }
                        }
                        else if ( $itemNode->localName == 'table-row' )
                        {
                            $rowContent = "";
                            foreach ( $itemNode->childNodes as $tableCell )
                            {
                                if ( $tableCell->localName == 'table-cell' )
                                {
                                    $cellContent = "";
                                    $colSpan = $tableCell->getAttributeNS( self::NAMESPACE_TABLE, 'number-columns-spanned' );
                                    foreach ( $tableCell->childNodes as $tableContentNode )
                                    {
                                        $cellContent .= self::handleNode( $tableContentNode, $sectionLevel );
                                    }
                                    $colSpanXML = "";
                                    if ( is_numeric( $colSpan ) and $colSpan > 1 )
                                    {
                                        $colSpanXML = " xhtml:colspan='$colSpan' ";
                                    }
                                    $rowContent .= "<td $colSpanXML>" . $cellContent . "</td>";
                                }
                            }
                            $tableContent .= "<tr>" . $rowContent . "</tr>";
                        }
                    }
                    $xhtmlTextContent .= "<paragraph><table width='100%'>" . $tableContent . "</table></paragraph>";
                }break;


                default:
                {
                    eZDebug::writeError( "Unsupported top node " . $node->localName . "<br/>" );
                }break;
            }
        }
        return $xhtmlTextContent;
    }

    /*!
      Handles the rendering of line nodes, e.g. inside paragraphs and headers.

      $nextLineBreak and $prevLineBreak attributes added by Alex (they were used but not defined)
     */
    function handleInlineNode( $childNode, $nextLineBreak = false, $prevLineBreak = false )
    {
        $localName = $childNode->localName;

        if ( $localName == '' )
            $localName = $childNode->nodeName;

        // Get the real name
        $paragraphContent = '';
        switch ( $localName )
        {
            case "frame":
            {
                $frameContent = "";
                foreach ( $childNode->childNodes as $imageNode )
                {
                    switch ( $imageNode->localName )
                    {
                        case "image" :
                        {
                            $href = ltrim( $imageNode->getAttributeNS( self::NAMESPACE_XLINK, 'href' ), '#' );

                            if ( 0 < preg_match( '@^(?:http://)([^/]+)@i', $href ) ) //if image is specified with url
                            {
                                eZDebug::writeDebug( "handling http url: $href", __METHOD__ );
                                $matches = array();
                                if ( 0 < preg_match( '/.*\/(.*)?/i', $href, $matches ) )
                                {
                                    $fileName = $matches[1];
                                    if ( false != ( $imageData = file_get_contents( $href ) ) )
                                    {
                                        $href = $this->ImportDir . $fileName;
                                        $fileOut = fopen( $href, "wb" );
                                        if ( fwrite( $fileOut, $imageData ) )
                                        {
                                            eZDebug::writeNotice( "External image stored in $href", __METHOD__ );
                                        }
                                        else
                                            eZDebug::writeError( "Could not save file $href", __METHOD__ );
                                        fclose( $fileOut );
                                    }
                                    else
                                        eZDebug::writeError( "Downloading external image from $href has failed, broken link?", __METHOD__ );
                                }
                                else
                                    eZDebug::writeError( "Could not match filename in $href", __METHOD__ );
                            }
                            else
                                $href = $this->ImportDir . $href;

                            // Check image name
                            $imageName = $childNode->getAttributeNS( self::NAMESPACE_DRAWING, 'name' );
                            if ( !$imageName )
                            {
                                // set default image name
                                $imageName = "Imported Image";
                            }


                            $imageSize = "medium";
                            $imageAlignment = "center";

                            // Check image size
                            $imageSize = "large";
                            $pageWidth = 6;
                            $width = $childNode->getAttributeNS( self::NAMESPACE_SVG_COMPATIBLE, 'width' );

                            $sizePercentage = $width / $pageWidth * 100;

                            if ( $sizePercentage < 80 and $sizePercentage > 30 )
                                $imageSize = 'medium';

                            if ( $sizePercentage <= 30 )
                                $imageSize = 'small';

                            // Check if image should be set to original
                            $sizeArray = getimagesize( $href );
                            if ( $imageSize != "small" and $sizeArray[0] < 650 )
                                $imageSize = "original";

                            $styleName = $childNode->getAttributeNS( self::NAMESPACE_DRAWING, 'style-name' );

                            // Check for style definitions
                            $imageAlignment = "center";
                            foreach ( $this->AutomaticStyles as $style )
                            {
                                if ( $style->nodeType !== XML_ELEMENT_NODE )
                                {
                                    continue;
                                }

                                $tmpStyleName = $style->getAttributeNS( self::NAMESPACE_STYLE, "name" );

                                if ( $styleName == $tmpStyleName )
                                {
                                    if ( $style->childNodes->length == 1 )
                                    {
                                        $properties = $style->childNodes->item( 0 );
                                        $alignment = $properties->getAttributeNS( self::NAMESPACE_STYLE, "horizontal-pos" );
                                    }

                                    // Check image alignment
                                    switch ( $alignment )
                                    {
                                        case "left":
                                        {
                                            $imageAlignment = "left";
                                        }break;

                                        case "right":
                                        {
                                            $imageAlignment = "right";
                                        }break;

                                        default:
                                        {
                                            $imageAlignment = "center";
                                        }break;
                                    }
                                    break;
                                }
                            }

                            if ( file_exists( $href ) )
                            {
                                // Calculate RemoteID based on image md5:
                                $remoteID = "ezoo-" . md5( file_get_contents( $href ) );


/*

                                // Check if an image with the same remote ID already exists
                                $db = eZDB::instance();
                                $imageParentNodeID = $GLOBALS["OOImportObjectID"];
                                $resultArray = $db->arrayQuery( 'SELECT id, node_id, ezcontentobject.remote_id
                                                                 FROM  ezcontentobject, ezcontentobject_tree
                                                                 WHERE ezcontentobject.remote_id = "' . $remoteID. '" AND
                                                                       ezcontentobject.id=ezcontentobject_tree.contentobject_id AND
                                                                       ezcontentobject_tree.parent_node_id=' . $imageParentNodeID );

                                $contentObject = false;
                                if ( count( $resultArray ) >= 1 )
                                {
                                    $contentObject = eZContentObject::fetch( $resultArray[0]['id'], true );
                                    $contentObjectID = $resultArray[0]['id'];
                                }

*/

                                $contentObject = eZContentObject::fetchByRemoteID( $remoteID );

                                // If image does not already exist, create it as an object
                                if ( !$contentObject )
                                {

                                    // Import image
                                    $ooINI = eZINI::instance( 'odf.ini' );
                                    $imageClassIdentifier = $ooINI->variable( "ODFImport", "DefaultImportImageClass" );
                                    $class = eZContentClass::fetchByIdentifier( $imageClassIdentifier );
                                    $creatorID = $this->currentUserID;

                                    $contentObject = $class->instantiate( $creatorID, 1 );
                                    $contentObject->setAttribute( "remote_id",  $remoteID );
                                    $contentObject->store();

                                    $version = $contentObject->version( 1 );
                                    $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                                    $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
                                    $version->store();

                                    $contentObjectID = $contentObject->attribute( 'id' );
                                    $dataMap = $contentObject->dataMap();

                                    // set image name
                                    $dataMap['name']->setAttribute( 'data_text', $imageName );
                                    $dataMap['name']->store();

                                    // set image caption
                                    if ( isset( $dataMap['caption'] ) )
                                    {
                                        $captionContentAttibute = $dataMap['caption'];
                                        $captionText = "$imageName";

                                        // create new xml for caption
                                        $xmlInputParser = new eZXMLInputParser();
                                        $dom = $xmlInputParser->createRootNode();

                                        $captionNode = $dom->createElement( 'paragraph', $captionText );
                                        $dom->documentElement->appendChild( $captionNode );

                                        $xmlString = $dom->saveXML();

                                        $captionContentAttibute->setAttribute( 'data_text', $xmlString );
                                        $captionContentAttibute->store();
                                    }
                                    else
                                    {
                                        eZDebug::writeWarning( "The image class does not have 'caption' attribute", 'ODF import' );
                                    }

                                    // set image
                                    $imageContent = $dataMap['image']->attribute( 'content' );
                                    //echo "Initializing Image from $href<br />";
                                    $imageContent->initializeFromFile( $href, false, basename( $href ) );
                                    $dataMap['image']->store();

                                }
                                else
                                    $contentObjectID = $contentObject->attribute( 'id' );

                                $this->RelatedImageArray[] = array( "ID" => $contentObjectID,
                                                                    "ContentObject" => $contentObject );

                                $frameContent .= "<embed object_id='$contentObjectID' align='$imageAlignment' size='$imageSize' />";

                            }

                        }break;

                    }
                }

                // Textboxes are defined inside paragraphs.
                $paragraphContent .= "$frameContent";
            }break;

            case "text-box":
            {
                foreach ( $childNode->childNodes as $textBoxNode )
                {
                    $boxContent .= self::handleNode( $textBoxNode, $sectionLevel );
                }

                // Textboxes are defined inside paragraphs.
                $paragraphContent .= "</paragraph>$boxContent<paragraph>";
            }break;

            case 'sequence':
            case 'date':
            case 'initial-creator':
            {
                $paragraphContent .= $childNode->textContent;
            }break;

            case "s" :
            {
                $paragraphContent .= " ";
            }break;

            case "a" :
            {
                $href = $childNode->getAttributeNS( self::NAMESPACE_XLINK, 'href' );
                $paragraphContent .= "<link href='$href'>" . $childNode->textContent . "</link>";
            }break;

            case "#text" :
            {
                $tagContent = str_replace( "&", "&amp;", $childNode->textContent );
                $tagContent = str_replace( ">", "&gt;", $tagContent );
                $tagContent = str_replace( "<", "&lt;", $tagContent );
                $tagContent = str_replace( "'", "&apos;", $tagContent );
                $tagContent = str_replace( '"', "&quot;", $tagContent );

                $paragraphContent .= $tagContent;
            }break;

            case "span" :
            {
                // Fetch the style from the span
                $styleName = $childNode->getAttributeNS( self::NAMESPACE_TEXT, 'style-name' );

                // Check for bold and italic styles
                $fontWeight = false;
                $fontStyle = false;
                foreach ( $this->AutomaticStyles as $style )
                {
                    if ( $style->nodeType !== XML_ELEMENT_NODE )
                    {
                        continue;
                    }

                    $tmpStyleName = $style->getAttributeNS( self::NAMESPACE_STYLE, "name" );

                    if ( $styleName == $tmpStyleName )
                    {
                        if ( $style->childNodes->length >= 1 )
                        {
                            foreach ( $style->childNodes as $styleChild )
                            {
                                if ( $styleChild->nodeType !== XML_ELEMENT_NODE
                                    || !$styleChild->hasAttributes() )
                                {
                                    continue;
                                }

                                $fontWeight = $styleChild->getAttributeNS( self::NAMESPACE_FO, 'font-weight' );
                                $fontStyle = $styleChild->getAttributeNS( self::NAMESPACE_FO, 'font-style' );
                            }
                        }
                    }
                }

                $inlineCustomTagName = false;
                if ( substr( $styleName, 0, 18 ) == "eZCustominline_20_" )
                    $inlineCustomTagName = substr( $styleName, 18 );

                if ( $inlineCustomTagName != false )
                    $paragraphContent .= "<custom name='$inlineCustomTagName'>";

                if ( $fontWeight == "bold" )
                    $paragraphContent .= "<strong>";
                if ( $fontStyle == "italic" )
                    $paragraphContent .= "<emphasize>";

                $paragraphContent .= $childNode->textContent;

                if ( $fontStyle == "italic" )
                    $paragraphContent .= "</emphasize>";
                if ( $fontWeight == "bold" )
                    $paragraphContent .= "</strong>";

                if ( $inlineCustomTagName != false )
                    $paragraphContent .= "</custom>";

            }break;


            default:
            {
                eZDebug::writeError( "Unsupported node: '" . $localName . "'" );
            }break;

        }

        if ( $nextLineBreak )
        {
            $paragraphContent = '<line>' . $paragraphContent . '</line>';
        }
        elseif ( $prevLineBreak && $paragraphContent )
        {
            $paragraphContent = '<line>' . $paragraphContent . '</line>';
        }

        return $paragraphContent;
    }

    /*!
      \private
      Creates a sub node of a given node by name, if it does not already exist.
      If it does exist the node is created.
    */
    function createSubNode( $node, $name )
    {
        $namedChildrenArray = $node->childrenByName( $name );
        $subNode = false;

        //pk
        if ( !$node->canCreate() )
        {
            $this->setError( self::ERROR_ACCESSDENIED, ezpI18n::tr( 'extension/ezodf/import/error', "Folder for images could not be created, access denied." ) );
            return false;
        }

        if ( empty( $namedChildrenArray ) )
        {
            $class = eZContentClass::fetchByIdentifier( "folder" );
            {
                $creatorID = $this->currentUserID;
                //$creatorID = 14; // 14 == admin
                $parentNodeID = $placeNodeID;
                $contentObject = $class->instantiate( $creatorID, 1 );

                $nodeAssignment = eZNodeAssignment::create( array(
                                                                 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                                 'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                                 'parent_node' => $node->attribute( 'node_id' ),
                                                                 'is_main' => 1
                                                                 )
                                                             );
                $nodeAssignment->store();

                $version = $contentObject->version( 1 );
                $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
                $version->store();

                $contentObjectID = $contentObject->attribute( 'id' );
                $dataMap = $contentObject->dataMap();

                $titleAttribudeIdentifier = 'name';

                $dataMap[$titleAttribudeIdentifier]->setAttribute( 'data_text', $name );
                $dataMap[$titleAttribudeIdentifier]->store();

                $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                             'version' => 1 ) );

                $subNode = $contentObject->mainNode();
            }
        }
        else
        {
            if ( count( $namedChildrenArray ) == 1 )
            {
                $subNode = $namedChildrenArray[0];
            }
        }

        return $subNode;
    }

    /*!
      \private
      \deprecated use $node->textContent directly
      Converts a dom node/tree to a plain ascii string
    */
    function domToText( $node )
    {
        return $node->textContent;
    }


    var $RelatedImageArray = array();
    var $AutomaticStyles = array();
    var $ImportDir = "var/cache/ezodf/import/";
    var $ImportBaseDir = "var/cache/ezodf/import/";
    var $InsideListType = false;

    var $IsSubList = false;

    // Variable containing collapsing tag name.
    // E.g. preformatted text is tagged on each paragraph,
    // in eZ Publish we make a <literal> tag around the text instead
    var $CollapsingTagName = false;
    var $CollapsingTagContent = false;
    var $CollapsingTagAttribute = false;

}

?>
