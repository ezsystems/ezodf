<?php
//
// Definition of eZOoimport class
//
// Created on: <17-Jan-2005 09:11:41 bf>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.9.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
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

include_once( 'lib/ezxml/classes/ezxml.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );
include_once( 'lib/ezfile/classes/ezdir.php' );


define( "OOIMPORT_ERROR_NOERROR", 0 );
define( "OOIMPORT_ERROR_UNSUPPORTEDTYPE", 1 );
define( "OOIMPORT_ERROR_PARSEXML", 2 );
define( "OOIMPORT_ERROR_OPENSOCKET", 3 );
define( "OOIMPORT_ERROR_CONVERT", 4 );
define( "OOIMPORT_ERROR_DEAMONCALL", 5 );
define( "OOIMPORT_ERROR_DEAMON", 6 );
define( "OOIMPORT_ERROR_DOCNOTSUPPORTED", 7 );
define( "OOIMPORT_ERROR_FILENOTFOUND", 8 );
define( "OOIMPORT_ERROR_PLACEMENTINVALID", 9 );
define( "OOIMPORT_ERROR_CANNOTSTORE", 10 );
define( "OOIMPORT_ERROR_UNKNOWNNODE", 11 );
define( "OOIMPORT_ERROR_ACCESSDENIED", 12 );
define( "OOIMPORT_ERROR_IMPORTING", 13 );
define( "OOIMPORT_ERROR_UNKNOWNCLASS", 14 );
define( "OOIMPORT_ERROR_UNKNOWN", 127 );

class eZOOImport
{
    var $ERROR=array();
    var $currentUserID;

    /*!
     Constructor
    */
    function eZOOImport()
    {
        $this->ERROR['number'] = 0;
        $this->ERROR['value'] = '';
        $this->ERROR['description'] = '';
        $currentUser =& eZUser::currentUser();
        $this->currentUserID  = $currentUser->id();
        $this->ImportDir .= md5( mktime() ) . "/";
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
        switch( $errorNumber )
        {
            case OOIMPORT_ERROR_NOERROR :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = "";
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_UNSUPPORTEDTYPE :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "File extension or type is not allowed." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_PARSEXML :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Could not parse XML." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_OPENSOCKET :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Can not open socket. Please check if extension/ezodf/deamon.php is running." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_CONVERT :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Can not convert the given document." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_DEAMONCALL :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Unable to call deamon. Fork can not create child process." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_DEAMON :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Deamon reported error." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_UNKNOWNNODE:
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Unknown node." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_ACCESSDENIED:
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Access denied." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_IMPORTING:
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Error during import." );
                $this->ERROR['description'] = $errorDescription;
                break;
            case OOIMPORT_ERROR_UNKNOWNCLASS:
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Unknown content class specified in odf.ini:" );
                $this->ERROR['description'] = $errorDescription;
                break;
            default :
                $this->ERROR['number'] = $errorNumber;
                $this->ERROR['value'] = ezi18n( 'extension/ezodf/import/error', "Unknown error." );
                $this->ERROR['description'] = $errorDescription;
                break;
        }
    }

    /*!
      Connects to the eZ publish document conversion deamon and converts the document to OpenOffice.org Writer
    */
    function deamonConvert( $sourceFile, $destFile )
    {
        $ooINI =& eZINI::instance( 'odf.ini' );
        $server = $ooINI->variable( "ODFImport", "OOConverterAddress" );
        $port = $ooINI->variable( "ODFImport", "OOConverterPort" );
        $res = false;
        $fp = fsockopen( $server,
                         $port,
                         $errorNR,
                         $errorString,
                         0 );

        if ( $fp )
        {
            $welcome = fread( $fp, 1024 );

            $welcome = trim( $welcome );
            if ( $welcome == "eZ publish document conversion deamon" )
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
                    $this->setError( OOIMPORT_ERROR_DEAMON, $result );
                    $res = false;
                }
             }
             else
             {
                 $this->setError( OOIMPORT_ERROR_DEAMONCALL );
                 $res = false;
             }
             fclose( $fp );
        }
        else
        {
            $this->setError( OOIMPORT_ERROR_OPENSOCKET );
            $res = false;
        }

        return $res;
    }

    /*!
      Imports an OpenOffice.org document from the given file.
    */
    function import( $file, $placeNodeID, $originalFileName, $importType = "import", $upload = null )
    {
        $ooINI =& eZINI::instance( 'odf.ini' );
        //$tmpDir = $ooINI->variable( 'ODFSettings', 'TmpDir' );
        // Use var-directory as temporary directory
        $tmpDir = getcwd() . "/" . eZSys::cacheDirectory();

        $allowedTypes = $ooINI->variable( 'DocumentType', 'AllowedTypes' );
        $convertTypes = $ooINI->variable( 'DocumentType', 'ConvertTypes' );

        $originalFileType = array_slice( explode('.',  $originalFileName), -1, 1 );
        $originalFileType = strtolower( $originalFileType[0] );

        if ( !in_array( $originalFileType,$allowedTypes, false ) and !in_array( $originalFileType, $convertTypes, false ) )
        {
            $this->setError( OOIMPORT_ERROR_UNSUPPORTEDTYPE, ezi18n( 'extension/ezodf/import/error',"Filetype: " ). $originalFileType );
            return false;
        }

        // If replacing/updating the document we need the ID.
        if ( $importType == "replace" )
             $GLOBALS["OOImportObjectID"] = $placeNodeID;

        // Check if we have access to node
        include_once( 'kernel/content/ezcontentfunctioncollection.php' );
        $place_node = eZContentObjectTreeNode::fetch( $placeNodeID );

        $importClassIdentifier = $ooINI->variable( 'ODFImport', 'DefaultImportClass' );

        // Check if class exist
        $class = eZContentClass::fetchByIdentifier( $importClassIdentifier );
        if ( !is_object( $class ) )
        {
            eZDebug::writeError( "Content class <strong>$importClassIdentifier</strong> specified in odf.ini does not exist." );
            $this->setError( OOIMPORT_ERROR_UNKNOWNCLASS, $importClassIdentifier );
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
                $this->setError( OOIMPORT_ERROR_UNKNOWNNODE, ezi18n( 'extension/ezodf/import/error',"Unable to fetch node with id  ") . $placeNodeID );
                return false;
            }

            $placeNodeID = $parentMainNode;
            $place_node = eZContentObjectTreeNode::fetch( $placeNodeID );
        }
        if ( $importType == "replace" )
        {
            // Check if we are allowed to edit the node
            $access = eZContentFunctionCollection::checkAccess( 'edit', $place_node, false, false );
        }
        else
        {
            // Check if we are allowed to create a node under the node
            $access = eZContentFunctionCollection::checkAccess( 'create', $place_node, $importClassIdentifier, $place_node->attribute( 'class_identifier' ) );
        }

        if ( ! ( $access['result'] ) )
        {
            $this->setError( OOIMPORT_ERROR_ACCESSDENIED );
            return false;
        }
        //return false;

        // Check if document conversion is needed
        //
        if ( in_array( $originalFileType, $convertTypes, false ) )
        {
            $uniqueStamp = md5( mktime() );
            $tmpFromFile = $tmpDir . "/convert_from_$uniqueStamp.doc";
            $tmpToFile   = $tmpDir . "/ooo_converted_$uniqueStamp.odt";
            copy( realpath( $file ),  $tmpFromFile );

           /// Convert document using the eZ publish document conversion deamon
            if ( !$this->deamonConvert( $tmpFromFile, $tmpToFile ) )
            {
                if( $this->getErrorNumber() == 0 )
                    $this->setError( OOIMPORT_ERROR_CONVERT );
                return false;
            }
            // At this point we can unlink the sourcefile for conversion
            unlink( $tmpFromFile );

            // Overwrite the file location
            $file = $tmpToFile;
        }

        $importResult = array();
        include_once( "lib/ezfile/classes/ezdir.php" );
        $unzipResult = "";
        $uniqueImportDir = $this->ImportDir;
        // Need to create the directory in two steps. On Mac the recursive dir creation did not work
        eZDir::mkdir( $this->ImportBaseDir );
        eZDir::mkdir( $uniqueImportDir );

        $http =& eZHTTPTool::instance();

        // Check if zlib extension is loaded, if it's loaded use bundled ZIP library,
        // if not rely on the unzip commandline version.
        if ( !function_exists( 'gzopen' ) )
        {
            exec( "unzip -o $file -d " . $uniqueImportDir, $unzipResult );
        }
        else
        {
            require_once('extension/ezodf/lib/pclzip.lib.php');
            $archive = new PclZip( $file );
            $archive->extract( PCLZIP_OPT_PATH, $uniqueImportDir );
        }

        $fileName = $uniqueImportDir . "content.xml";
        $xml = new eZXML();
        $dom =& $xml->domTree( file_get_contents( $fileName ) );
        $sectionNodeHash = array();

        // At this point we could unlink the destination file from the conversion, if conversion was used
        if ( isset( $tmpToFile ) )
        {
            unlink( $tmpToFile );
        }

        if ( !is_object( $dom ) )
        {
            $this->setError( OOIMPORT_ERROR_PARSEXML );
            return false;
        }


        // Fetch the automatic document styles
        $automaticStyleArray =& $dom->elementsByNameNS( 'automatic-styles', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0' );
        if ( count( $automaticStyleArray ) == 1 )
        {
            $this->AutomaticStyles = $automaticStyleArray[0]->children();
        }

        // Fetch the body section content
        $sectionNodeArray =& $dom->elementsByNameNS( 'section', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0' );

        $customClassFound = false;
        if ( count( $sectionNodeArray ) > 0 )
        {
            $registeredClassArray = $ooINI->variable( 'ODFImport', 'RegisteredClassArray' );

            // Check the defined sections in OO document
            $sectionNameArray = array();
            foreach ( $sectionNodeArray as $sectionNode )
            {
                $sectionNameArray[] = strtolower( $sectionNode->attributeValueNS( "name", "urn:oasis:names:tc:opendocument:xmlns:text:1.0" ) );
            }

            // Check if there is a coresponding eZ publish class for this document
            foreach ( $registeredClassArray as $className )
            {
                $attributeArray = $ooINI->variable( $className, 'Attribute' );

                if ( count( $attributeArray ) > 0 )
                {
                    // Convert space to _ in section names
                    foreach ( $sectionNameArray as $key => $value )
                    {
                        $sectionNameArray[$key] = str_replace( " ", "_", $value );
                    }

                    sort( $attributeArray );
                    sort( $sectionNameArray );

                    $diff = array_diff( $attributeArray, $sectionNameArray );
                    if ( count( $diff ) == 0 )
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
                    $sectionName = str_replace( " ", "_", strtolower( $sectionNode->attributeValueNS( 'name', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0' ) ) );
                    $xmlText = "";
                    $level = 1;
                    $childArray = $sectionNode->children();
                    $nodeCount = 1;
                    foreach ( $childArray as $childNode )
                    {
                        $isLastTag = false;
                        if ( $nodeCount == count( $childArray ) )
                        {
                            $isLastTag = true;
                        }

                        $xmlText .= eZOOImport::handleNode( $childNode, $level, $isLastTag );
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
            $bodyNodeArray =& $dom->elementsByNameNS( 'text', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0' );

            if ( count( $bodyNodeArray ) == 1 )
            {
                $xmlText = "";
                $level = 1;
                foreach ( $bodyNodeArray[0]->children() as $childNode )
                {
                    $xmlText .= eZOOImport::handleNode( $childNode, $level );
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

        // Create object start
        {
            // Check if we should replace the current object or import a new
            if ( $importType !== "replace" )
            {
                $class = eZContentClass::fetchByIdentifier( $importClassIdentifier );

                $place_object = $place_node->attribute( 'object' );
                $sectionID = $place_object->attribute( 'section_id' );

                $creatorID = $this->currentUserID;
                $parentNodeID = $placeNodeID;
                $object = $class->instantiate( $creatorID, $sectionID );

                $nodeAssignment = eZNodeAssignment::create( array(
                                                                 'contentobject_id' => $object->attribute( 'id' ),
                                                                 'contentobject_version' => $object->attribute( 'current_version' ),
                                                                 'parent_node' => $parentNodeID,
                                                                 'is_main' => 1
                                                                 )
                                                             );
                $nodeAssignment->store();

                $version =& $object->version( 1 );
                $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
                $version->store();
                $dataMap =& $object->dataMap();
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
                        $this->setError( OOIMPORT_ERROR_IMPORTING, "Error in configuration for $placeClassIdentifier, please check configuration file." );
                        return false;
                    }
                    unset( $dataMap );
                }
                else
                {
                    $this->setError( OOIMPORT_ERROR_IMPORTING, "No settings for replacing node of type $placeClassIdentifier. Stopping.");
                    return false;
                }

                // Change class for importing
                $importClassIdentifier = $placeClassIdentifier;

                // already fetched: $node = eZContentObjectTreeNode::fetch( $placeNodeID );
                $object = $place_node->attribute( 'object' );
                $version = $object->createNewVersion();

                $dataMap = $object->fetchDataMap( $version->attribute( 'version' ) );
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
                            $dom =& $xml->domTree( $xmlTextArray[$sectionName] );
                            $text = eZOOImport::domToText( $dom->root() );
                            $dataMap[$attributeIdentifier]->setAttribute( 'data_text', trim( $text ) );
                            $dataMap[$attributeIdentifier]->store();
                        }break;

                        case "ezxmltext":
                        {
                            $dataMap[$attributeIdentifier]->setAttribute( 'data_text', $xmlTextArray[$sectionName] );
                            $dataMap[$attributeIdentifier]->store();
                        }break;


                        case "ezdate":
                        {
                            // Only support date formats as a single paragraph in a section with the format:
                            // day/month/year
                            $dateString = strip_tags( $xmlTextArray[$sectionName] );

                            $dateArray = explode( "/", $dateString );

                            if ( count( $dateArray ) == 3 )
                            {
                                    $year = $dateArray[2];
                                    $month = $dateArray[1];
                                    $day = $dateArray[0];

                                    $date = new eZDate();

                                    $contentClassAttribute =& $dataMap[$attributeIdentifier];

                                    $date->setMDY( $month, $day, $year );
                                    $dataMap[$attributeIdentifier]->setAttribute( 'data_int', $date->timeStamp()  );
                                    $dataMap[$attributeIdentifier]->store();
                            }
                        }break;

                        case "ezdatetime":
                        {
                            // Only support date formats as a single paragraph in a section with the format:
                            // day/month/year 14:00
                            $dateString = trim( strip_tags( $xmlTextArray[$sectionName] ) );

                            $dateTimeArray = split(  " ", $dateString );

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

                                    $contentClassAttribute =& $dataMap[$attributeIdentifier];

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
                                foreach ( $sectionNodeHash[$sectionName]->children() as $paragraph )
                                {
                                    // Look for frame node
                                    foreach ( $paragraph->children() as $frame )
                                    {
                                        // finally look for the image node
                                        $children = $frame->children();

                                        if ( $children[0]->name() == "image" )
                                        {
                                            $imageNode = $children[0];
                                            $fileName = $imageNode->attributeValue( "href" );

                                            $filePath = $this->ImportDir . $fileName;

                                            if ( file_exists( $filePath ) )
                                            {
                                                $imageContent =& $dataMap[$attributeIdentifier]->attribute( 'content' );
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
                                $imageHandler =& $dataMap[$attributeIdentifier]->attribute( 'content' );
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
                                foreach ( $sectionNodeHash[$sectionName]->children() as $table )
                                {
                                    if ( $table->name() == "table" )
                                    {
                                        // Loop the rows in the table
                                        foreach ( $table->children() as $row )
                                        {
                                            // Check the headers and compare with the defined matrix
                                            if ( $row->name() == "table-header-rows" )
                                            {
                                                $rowArray = $row->children();
                                                if ( count( $rowArray ) == 1  )
                                                {
                                                    foreach ( $rowArray[0]->children() as $headerCell )
                                                    {
                                                        if ( $headerCell->name() == "table-cell" )
                                                        {
                                                            $paragraphArray = $headerCell->children();

                                                            if ( count( $paragraphArray ) == 1 )
                                                            {
                                                                $headerName = $paragraphArray[0]->textContent();
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
                                            if ( $row->name() == "table-row" )
                                            {
                                                foreach ( $row->children() as $cell )
                                                {
                                                    if ( count( $cell->children() ) >= 1 )
                                                    {
                                                        $firstParagraph = $cell->children();
                                                        $firstParagraph = $firstParagraph[0];
                                                        $cellContent = $firstParagraph->textContent();

                                                        $cellArray[] = $cellContent;

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

                $objectName = basename( $originalFileName);

                // Remove extension from name
                $objectName = preg_replace( "/(\....)$/", "", $objectName );
                // Convert _ to spaces and upcase the first character
                $objectName = ucfirst( str_replace( "_", " ", $objectName ) );

                $dataMap[$titleAttribute]->setAttribute( 'data_text', $objectName );
                $dataMap[$titleAttribute]->store();

                $dataMap[$bodyAttribute]->setAttribute( 'data_text', $xmlTextBody );
                $dataMap[$bodyAttribute]->store();
            }

            include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
            $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                         'version' => $version->attribute( 'version' ) ) );

            $storeImagesInMedia = $ooINI->variable( "ODFImport", "PlaceImagesInMedia" ) == "true";
            if ( $storeImagesInMedia == true )
            {
                // Fetch object to get correct name
                $object = eZContentObject::fetch( $contentObjectID );

                // Create image folder if it does not already exist
                {
                    $contentINI =& eZINI::instance( 'content.ini' );
                    $mediaRootNodeID = $contentINI->variable( "NodeSettings", "MediaRootNode" );

                    $node = eZContentObjectTreeNode::fetch( $mediaRootNodeID );

                    $articleFolderName = $object->attribute( 'name' );
                    $importFolderName = $ooINI->variable( 'ODFImport', 'ImportedImagesMediaNodeName' );
                    $importNode = eZOOImport::createSubNode( $node, $importFolderName );

                    $articleNode = eZOOImport::createSubNode( $importNode, $articleFolderName );
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

                include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
                $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $image['ID'],
                                                                                             'version' => 1 ) );

                $object->addContentObjectRelation( $image['ID'], 1 );
            }

            $mainNode = $object->attribute( 'main_node' );
            // Create object stop.
            $importResult['Object'] = $object;
            $importResult['MainNode'] = $mainNode;
            $importResult['URLAlias'] = $mainNode->attribute( 'url_alias' );
            $importResult['NodeName'] = $mainNode->attribute( 'name' );
            $importResult['ClassIdentifier'] = $importClassIdentifier;
        }

        // Clean up
        eZDir::recursiveDelete( $uniqueImportDir );
        return $importResult;
    }


    /*!
      Handless DOM node in the OpenOffice.org writer docuemnt and returns the eZXMLText equivalent.
      If images are embedded in the document they will be imported as media objects in eZ publish.
     */
    function handleNode( $node, &$sectionLevel, $isLastTag = false )
    {
        $xhtmlTextContent = "";
//    if ( $node->namespaceURI() == 'http://openoffice.org/2000/text' )
        {

            // If another tag than paragraph comes then terminate collapsing tags, if any
            if ( $node->name() != "p" and $this->CollapsingTagName != false )
            {
                $xhtmlTextContent .= '<paragraph>' . '<' . $this->CollapsingTagName . ' ' . $this->CollapsingTagAttribute . ' >' . $this->CollapsingTagContent . "</" . $this->CollapsingTagName . ">\n</paragraph>\n";
                $this->CollapsingTagContent = false;
                $this->CollapsingTagAttribute = false;
                $this->CollapsingTagName = false;
            }

            switch ( $node->name() )
            {
                case 'sequence-decls' :
                case 'forms' :
                {
                    // do nothing
                }break;


                case 'section' :
                {
                    foreach ( $node->children() as $childNode )
                    {
                        $xhtmlTextContent  .= eZOOImport::handleNode( $childNode, $sectionLevel );
                    }
                }break;

                case 'h' :
                {
                    $level = $node->attributeValueNS( 'outline-level', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0' );

                    if ( $level > 6 )
                        $level = 6;

                    if ( $level >= 1 && $level <= 6 )
                    {
                        $levelDiff = $level - $sectionLevel;
                        $sectionLevel = $level;
                        $headerContent = "";
                        foreach ( $node->children() as $childNode )
                        {
                            $headerContent .= eZOOImport::handleInlineNode( $childNode );
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
                        eZDebug::writeError( "Unsupported header level $level<br>" . $node->textContent() . "<br>" );
                    }
                }break;

                case 'p' :
                {
                    $styleName = $node->attributeValueNS( 'style-name', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0' );

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
                        $tmpStyleName = $style->attributeValueNS( "name", "urn:oasis:names:tc:opendocument:xmlns:style:1.0" );

                        if ( $styleName == $tmpStyleName )
                        {
                            if ( count( $style->children() >= 1 ) )
                            {
                                $children = $style->children();

                                foreach ( $children as $styleChild )
                                {
                                    $fontWeight = $styleChild->attributeValue( 'font-weight' );
                                    $fontStyle = $styleChild->attributeValue( 'font-style' );
                                }
                            }

                            // Get the parent style name, it's used to see if it's a
                            // header which comes from Word conversion
                            $parentStyleName = $style->attributeValueNS( "parent-style-name", "urn:oasis:names:tc:opendocument:xmlns:style:1.0" );

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
                    foreach ( $node->children() as $childNode )
                    {
                        $paragraphContent .= eZOOImport::handleInlineNode( $childNode );
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
                    foreach ( $node->children() as $itemNode )
                    {
                        if ( $itemNode->name() == 'p' )
                        {
                            $listContent .= "<li>" . strip_tags( eZOOImport::handleNode( $itemNode, $sectionLevel ) ) . "</li>";
                        }
                    }

                    $xhtmlTextContent .= "<paragraph><ul>" . $listContent . "</ul></paragraph>\n";
                }break;

                case 'list' :
                {
                    $styleName = $node->attributeValueNS( 'style-name', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0' );

                    // Check list style for unordered/ordered list
                    $listType = false;
                    foreach ( $this->AutomaticStyles as $style )
                    {
                        $tmpStyleName = $style->attributeValueNS( "name", "urn:oasis:names:tc:opendocument:xmlns:style:1.0" );

                        if ( $styleName == $tmpStyleName )
                        {
                            if ( count( $style->children() >= 1 ) )
                            {
                                $children = $style->children();

                                if ( $children[0]->name() == "list-level-style-number" )
                                {
                                    $listType = "ordered";
                                    $this->InsideListType = "ordered";
                                }

                                if ( $children[0]->name() == "list-level-style-bullet" )
                                {
                                    $listType = "unordered";
                                    $this->InsideListType = "unordered";
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
                    foreach ( $node->children() as $itemNode )
                    {
                        if ( $itemNode->name() == 'list-item' )
                        {
                            foreach ( $itemNode->children() as $childNode )
                            {
                                $listItemContent = eZOOImport::handleNode( $childNode, $sectionLevel );

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
                    foreach ( $node->children() as $itemNode )
                    {
                        if ( $itemNode->name() == 'table-header-rows' )
                        {
                            foreach ( $itemNode->children() as $headerRow )
                            {
                                if ( $headerRow->name() == 'table-row' )
                                {
                                    $rowContent = "";
                                    foreach ( $headerRow->children() as $tableCell )
                                    {
                                        $colSpan = $tableCell->attributeValueNS( 'number-columns-spanned', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0' );

                                        if ( $tableCell->name() == 'table-cell' )
                                        {
                                            $cellContent = "";
                                            foreach ( $tableCell->children() as $tableContentNode )
                                            {
                                                $cellContent .= eZOOImport::handleNode( $tableContentNode, $sectionLevel );
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
                        else if ( $itemNode->name() == 'table-row' )
                        {
                            $rowContent = "";
                            foreach ( $itemNode->children() as $tableCell )
                            {
                                if ( $tableCell->name() == 'table-cell' )
                                {
                                    $cellContent = "";
                                    $colSpan = $tableCell->attributeValueNS( 'number-columns-spanned', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0' );
                                    foreach ( $tableCell->children() as $tableContentNode )
                                    {
                                        $cellContent .= eZOOImport::handleNode( $tableContentNode, $sectionLevel );
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
                    eZDebug::writeError( "Unsupported top node " . $node->name() . "<br/>" );
                }break;
            }
        }
        return $xhtmlTextContent;
    }

    /*!
      Handles the rendering of line nodes, e.g. inside paragraphs and headers.
     */
    function handleInlineNode( $childNode )
    {
        $paragraphContent = "";
        switch ( $childNode->name() )
        {
            case "frame":
            {
                $frameContent = "";
                foreach ( $childNode->children() as $imageNode )
                {
                    switch ( $imageNode->name() )
                    {

                        case "image" :
                        {
                            $href = ltrim( $imageNode->attributeValueNS( 'href', 'http://www.w3.org/1999/xlink' ), '#' );

                            if ( 0 < preg_match( '@^(?:http://)([^/]+)@i', $href ) ) //if image is specified with url
                            {
                                eZDebug::writeDebug( "handling http url: $href", 'ezooimage::handleInlineNode()' );
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
                                            eZDebug::writeNotice( "External image stored in $href", "ezooimage::handleInlineNode()" );
                                        }
                                        else
                                            eZDebug::writeError( "Could not save file $href", "ezooimage::handleInlineNode()" );
                                        fclose( $fileOut );
                                    }
                                    else
                                        eZDebug::writeError( "Downloading external image from $href has failed, broken link?", "ezooimage::handleInlineNode()" );
                                }
                                else
                                    eZDebug::writeError( "Could not match filename in $href", "ezooimage::handleInlineNode()" );
                            }
                            else
                                $href = $this->ImportDir . $href;

                            $imageSize = "medium";
                            $imageAlignment = "center";

                            // Check image size
                            $imageSize = "large";
                            $pageWidth = 6;
                            $width = $childNode->attributeValueNS( 'width', 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0' );

                            $sizePercentage = $width / $pageWidth * 100;

                            if ( $sizePercentage < 80 and $sizePercentage > 30 )
                                $imageSize = 'medium';

                            if ( $sizePercentage <= 30 )
                                $imageSize = 'small';

                            // Check if image should be set to original
                            $sizeArray = getimagesize( $href );
                            if ( $imageSize != "small" and $sizeArray[0] < 650 )
                                $imageSize = "original";

                            $styleName = $childNode->attributeValueNS( 'style-name', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0' );

                            // Check for style definitions
                            $imageAlignment = "center";
                            foreach ( $this->AutomaticStyles as $style )
                            {
                                $tmpStyleName = $style->attributeValueNS( "name", "urn:oasis:names:tc:opendocument:xmlns:style:1.0" );

                                if ( $styleName == $tmpStyleName )
                                {
                                    if ( count( $style->children() == 1 ) )
                                    {
                                        $children = $style->children();
                                        $properties = $children[0];
                                        $alignment = $properties->attributeValueNS( "horizontal-pos", "urn:oasis:names:tc:opendocument:xmlns:style:1.0" );
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
                                $db =& eZDB::instance();
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

                                $contentObject =& eZContentObject::fetchByRemoteID( $remoteID );

                                // If image does not already exist, create it as an object
                                if ( !$contentObject )
                                {

                                    // Import image
                                    $ooINI =& eZINI::instance( 'odf.ini' );
                                    $imageClassIdentifier = $ooINI->variable( "ODFImport", "DefaultImportImageClass" );
                                    $class = eZContentClass::fetchByIdentifier( $imageClassIdentifier );
                                    $creatorID = $this->currentUserID;

                                    $contentObject = $class->instantiate( $creatorID, 1 );
                                    $contentObject->setAttribute( "remote_id",  $remoteID );
                                    $contentObject->store();

                                    $version =& $contentObject->version( 1 );
                                    $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                                    $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
                                    $version->store();

                                    $contentObjectID = $contentObject->attribute( 'id' );
                                    $dataMap =& $contentObject->dataMap();

                                    $dataMap['name']->setAttribute( 'data_text', "Imported Image" );
                                    $dataMap['name']->store();

                                    $imageContent =& $dataMap['image']->attribute( 'content' );
                        //echo "Initializing Image from $href<br />";
                                    $imageContent->initializeFromFile( $href, false, basename( $href ) );
                                    $dataMap['image']->store();
                                    $this->RelatedImageArray[] = array( "ID" => $contentObjectID,
                                                                        "ContentObject" => $contentObject );
                                }
                                else
                                    $contentObjectID =& $contentObject->attribute( 'id' );


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
                foreach ( $childNode->children() as $textBoxNode )
                {
                    $boxContent .= eZOOImport::handleNode( $textBoxNode, $sectionLevel );
                }

                // Textboxes are defined inside paragraphs.
                $paragraphContent .= "</paragraph>$boxContent<paragraph>";
            }break;

            case "sequence" :
            {
                $paragraphContent .= $childNode->textContent();
            }break;

            case "date" :
            {
                $paragraphContent .= $childNode->textContent();
            }break;

            case "initial-creator" :
            {
                $paragraphContent .= $childNode->textContent();
            }break;

            case "s" :
            {
                $paragraphContent .= " ";
            }break;

            case "a" :
            {
                $href = $childNode->attributeValueNS( 'href', 'http://www.w3.org/1999/xlink' );
                $paragraphContent .= "<link href='$href'>" . $childNode->textContent() . "</link>";
            }break;

            case "#text" :
            {
                $tagContent = str_replace( "&", "&amp;", $childNode->content() );
                $tagContent = str_replace( ">", "&gt;", $tagContent );
                $tagContent = str_replace( "<", "&lt;", $tagContent );
                $tagContent = str_replace( "'", "&apos;", $tagContent );
                $tagContent = str_replace( '"', "&quot;", $tagContent );

                $paragraphContent .= $tagContent;
            }break;

            case "span" :
            {
                // Fetch the style from the span
                $styleName = $childNode->attributeValueNS( 'style-name', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0' );

                // Check for bold and italic styles
                $fontWeight = false;
                $fontStyle = false;
                foreach ( $this->AutomaticStyles as $style )
                {
                    $tmpStyleName = $style->attributeValueNS( "name", "urn:oasis:names:tc:opendocument:xmlns:style:1.0" );

                    if ( $styleName == $tmpStyleName )
                    {
                        if ( count( $style->children() >= 1 ) )
                        {
                            $children = $style->children();

                            foreach ( $children as $styleChild )
                            {
                                $fontWeight = $styleChild->attributeValue( 'font-weight' );
                                $fontStyle = $styleChild->attributeValue( 'font-style' );
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
                $paragraphContent .= $childNode->textContent();

                if ( $fontStyle == "italic" )
                    $paragraphContent .= "</emphasize>";
                if ( $fontWeight == "bold" )
                    $paragraphContent .= "</strong>";

                if ( $inlineCustomTagName != false )
                    $paragraphContent .= "</custom>";

            }break;


            default:
            {
                eZDebug::writeError( "Unsupported node: " . $childNode->name() . "<br>" );
            }break;

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
            $this->setError( OOIMPORT_ERROR_ACCESSDENIED, ezi18n( 'extension/ezodf/import/error', "Folder for images could not be created, access denied." ) );
            return false;
        }

        if ( count( $namedChildrenArray ) == 0 )
        {
            $class = eZContentClass::fetchByIdentifier( "folder" );
            {
                $creatorID = $this->currentUserID;
                //$creatorID = 14; // 14 == admin
                $parentNodeID = $placeNodeID;
                $contentObject =& $class->instantiate( $creatorID, 1 );

                $nodeAssignment =& eZNodeAssignment::create( array(
                                                                 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                                 'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                                 'parent_node' => $node->attribute( 'node_id' ),
                                                                 'is_main' => 1
                                                                 )
                                                             );
                $nodeAssignment->store();

                $version =& $contentObject->version( 1 );
                $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
                $version->store();

                $contentObjectID = $contentObject->attribute( 'id' );
                $dataMap =& $contentObject->dataMap();

                $titleAttribudeIdentifier = 'name';

                $dataMap[$titleAttribudeIdentifier]->setAttribute( 'data_text', $name );
                $dataMap[$titleAttribudeIdentifier]->store();

                include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
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
      Converts a dom node/tree to a plain ascii string
    */
    function domToText( $node )
    {
        $textContent = "";

        foreach ( $node->children() as $childNode )
        {
            $textContent .= eZOOImport::domToText( $childNode );
        }

        if  ( $node->name() == "#text" )
        {
            $textContent .= $node->content();
        }
        return $textContent;
    }


    var $RelatedImageArray = array();
    var $AutomaticStyles = array();
    var $ImportDir = "var/cache/ezodf/import/";
    var $ImportBaseDir = "var/cache/ezodf/import/";
    var $InsideListType = false;

    var $IsSubList = false;

    // Variable containing collapsing tag name.
    // E.g. preformatted text is tagged on each paragraph,
    // in eZ publish we make a <literal> tag around the text instead
    var $CollapsingTagName = false;
    var $CollapsingTagContent = false;
    var $CollapsingTagAttribute = false;

}

?>
