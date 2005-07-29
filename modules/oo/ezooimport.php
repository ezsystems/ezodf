<?php
//
// Definition of eZOoimport class
//
// Created on: <17-Jan-2005 09:11:41 bf>
//
// Copyright (C) 1999-2005 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*! \file ezooimport.php
 */

/*!
  \class eZOoimport ezooimport.php
  \brief The class eZOoimport does

*/

include_once( 'lib/ezxml/classes/ezxml.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );

class eZOOImport
{
    /*!
     Constructor
    */
    function eZOOImport()
    {
    }

    /*!
      Imports an OpenOffice.org document from the given file.
    */
    function import( $file, $placeNodeID, $originalFilename )
    {
        $importResult = array();
        include_once( "lib/ezfile/classes/ezdir.php" );
        $unzipResult = "";
        eZDir::mkdir( $this->ImportDir );

        $http =& eZHTTPTool::instance();

        // Check if zlib extension is loaded, if it's loaded use bundled ZIP library,
        // if not rely on the unzip commandline version.
        if ( !function_exists( 'gzopen' ) )
        {
            exec( "unzip -o $file -d " . $this->ImportDir, $unzipResult );
        }
        else
        {
            require_once('extension/oo/lib/pclzip.lib.php');
            $archive = new PclZip( $file );
            $archive->extract( PCLZIP_OPT_PATH, $this->ImportDir );
        }

        $fileName = $this->ImportDir . "content.xml";
        $xml = new eZXML();
        $dom =& $xml->domTree( file_get_contents( $fileName ) );


        if ( !is_object( $dom ) )
        {
            print( "Error: could not parse XML");
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

        $ooINI =& eZINI::instance( 'oo.ini' );
        $importClassIdentifier = $ooINI->variable( 'OOImport', 'DefaultImportClass' );
        $customClassFound = false;
        if ( count( $sectionNodeArray ) > 0 )
        {
            $registeredClassArray = $ooINI->variable( 'OOImport', 'RegisteredClassArray' );

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
                    $sectionName = strtolower( $sectionNode->attributeValueNS( 'name', 'http://openoffice.org/2000/text' ) );
                    $xmlText = "";
                    $level = 1;
                    foreach ( $sectionNode->children() as $childNode )
                    {
                        $xmlText .= eZOOImport::handleNode( $childNode, $level );
                    }
                    $endSectionPart = "";
                    $levelDiff = 1 - $level;
                    if ( $levelDiff < 0 )
                        $endSectionPart = str_repeat( "</section>", abs( $levelDiff ) );
                    $charset = eZTextCodec::internalCharset();

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
        $class = eZContentClass::fetchByIdentifier( $importClassIdentifier );
        {
            $creatorID = 14; // 14 == admin
            $parentNodeID = $placeNodeID;
            $contentObject =& $class->instantiate( $creatorID, 1 );

            $nodeAssignment =& eZNodeAssignment::create( array(
                                                             'contentobject_id' => $contentObject->attribute( 'id' ),
                                                             'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                             'parent_node' => $parentNodeID,
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

                        default:
                        {
                            eZDebug::writeError( "Unsupported datatype for OpenOffice.org import: " . $dataMap[$attributeIdentifier]->DataTypeString );
                        }break;
                    }
                }
            }
            else
            {
                $titleAttribute = $ooINI->variable( 'OOImport', 'DefaultImportTitleAttribute' );
                $bodyAttribute = $ooINI->variable( 'OOImport', 'DefaultImportBodyAttribute' );

                $objectName = basename( $originalFilename);

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
                                                                                         'version' => 1 ) );

            // Fetch object to get correct name
            $contentObject = eZContentObject::fetch( $contentObjectID );

            // Create image folder if it does not already exist
            {
                $mediaRootNodeID = 43;
                $node = eZContentObjectTreeNode::fetch( $mediaRootNodeID );

                $articleFolderName = $contentObject->attribute( 'name' );
                $importFolderName = $ooINI->variable( 'OOImport', 'ImportedImagesMediaNodeName' );
                $importNode = eZOOImport::createSubNode( $node, $importFolderName );

                $articleNode = eZOOImport::createSubNode( $importNode, $articleFolderName );
            }

            // Publish all embedded images as related objects
            foreach ( $this->RelatedImageArray as $image )
            {

                // Publish related images
                $nodeAssignment =& eZNodeAssignment::create( array(
                                                                 'contentobject_id' => $image['ID'],
                                                                 'contentobject_version' => 1,
                                                                 'parent_node' => $articleNode->attribute( "node_id" ),
                                                                 'is_main' => 1
                                                                 )
                                                             );
                $nodeAssignment->store();

                include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
                $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $image['ID'],
                                                                                             'version' => 1 ) );

                $contentObject->addContentObjectRelation( $image['ID'], 1 );
            }

            $mainNode = $contentObject->attribute( 'main_node' );
            // Create object stop.
            $importResult['Object'] = $contentObject;
            $importResult['MainNode'] = $mainNode;
            $importResult['URLAlias'] = $mainNode->attribute( 'url_alias' );
            $importResult['NodeName'] = $contentObject->attribute( 'name' );
            $importResult['ClassIdentifier'] = $importClassIdentifier;
        }

        // Clean up
        eZDir::recursiveDelete( $this->ImportDir );
        return $importResult;
    }


    /*!
      Handless DOM node in the OpenOffice.org writer docuemnt and returns the eZXMLText equivalent.
      If images are embedded in the document they will be imported as media objects in eZ publish.
     */
    function handleNode( $node, &$sectionLevel )
    {
        $xhtmlTextContent = "";
//    if ( $node->namespaceURI() == 'http://openoffice.org/2000/text' )
        {
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
                        print( "Unsupported header level $level<br>" . $node->textContent() . "<br>" );
                    }
                }break;

                case 'p' :
                {
                    $paragraphContent = "";
                    foreach ( $node->children() as $childNode )
                    {
                        $paragraphContent .= eZOOImport::handleInlineNode( $childNode );
                    }

                    if ( trim( $paragraphContent ) != "" )
                    {
                        $xhtmlTextContent .= '<paragraph>' . $paragraphContent . "</paragraph>\n";
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
                    $listType = "unordered";
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
                                }
                            }
                        }
                    }

                    $listContent = "";
                    foreach ( $node->children() as $itemNode )
                    {
                        if ( $itemNode->name() == 'list-item' )
                        {
                            foreach ( $itemNode->children() as $childNode )
                            {
                                $listContent .= "<li>" . strip_tags( eZOOImport::handleNode( $childNode, $sectionLevel ) ) . "</li>";
                            }
                        }
                    }

                    if ( $listType == "ordered" )
                        $xhtmlTextContent .= "<paragraph><ol>" . $listContent . "</ol></paragraph>\n";
                    else
                        $xhtmlTextContent .= "<paragraph><ul>" . $listContent . "</ul></paragraph>\n";
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
                                        if ( $tableCell->name() == 'table-cell' )
                                        {
                                            $cellContent = "";
                                            foreach ( $tableCell->children() as $tableContentNode )
                                            {
                                                $cellContent .= eZOOImport::handleNode( $tableContentNode, $sectionLevel );
                                            }
                                            $rowContent .= "<th>" . $cellContent . "</th>";
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
                                    foreach ( $tableCell->children() as $tableContentNode )
                                    {
                                        $cellContent .= eZOOImport::handleNode( $tableContentNode, $sectionLevel );
                                    }
                                    $rowContent .= "<td>" . $cellContent . "</td>";
                                }
                            }
                            $tableContent .= "<tr>" . $rowContent . "</tr>";
                        }


                    }
                    $xhtmlTextContent .= "<paragraph><table width='100%'>" . $tableContent . "</table></paragraph>";
                }break;


                default:
                {
                    print( "Unsupported top node " . $node->name() . "<br/>" );
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
                                // Import image
                                $classID = 5;
                                $class =& eZContentClass::fetch( $classID );
                                $creatorID = 14;

                                $contentObject =& $class->instantiate( $creatorID, 1 );

                                $version =& $contentObject->version( 1 );
                                $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
                                $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
                                $version->store();

                                $contentObjectID = $contentObject->attribute( 'id' );
                                $dataMap =& $contentObject->dataMap();

                                $dataMap['name']->setAttribute( 'data_text', "Imported Image" );
                                $dataMap['name']->store();

                                $imageContent =& $dataMap['image']->attribute( 'content' );
                                $imageContent->initializeFromFile( $href );
                                $dataMap['image']->store();

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
                $paragraphContent .= "&nbsp;";
            }break;

            case "a" :
            {
                $href = $childNode->attributeValueNS( 'href', 'http://www.w3.org/1999/xlink' );
                $paragraphContent .= "<link href='$href'>" . $childNode->textContent() . "</link>";
            }break;

            case "#text" :
            {
                $tagContent =& str_replace( "&", "&amp;", $childNode->content() );
                $tagContent =& str_replace( ">", "&gt;", $tagContent );
                $tagContent =& str_replace( "<", "&lt;", $tagContent );
                $tagContent =& str_replace( "'", "&apos;", $tagContent );
                $tagContent =& str_replace( '"', "&quot;", $tagContent );

                $paragraphContent .= $tagContent;
            }break;

            case "span" :
            {
                // Todo: do actual lookup of the style
                $styleName = $childNode->attributeValueNS( 'style-name', 'http://openoffice.org/2000/text' );

                switch ( $styleName )
                {
                    case 'T1':
                    {
                        $paragraphContent .= "<strong>" . $childNode->textContent() . "</strong>";
                    }break;

                    case 'T2':
                    {
                        $paragraphContent .= "<emphasize>" . $childNode->textContent() . "</emphasize>";
                    }break;

                    default:
                    {
                        $paragraphContent .= $childNode->textContent();
                    }break;
                }
            }break;


            default:
            {
                print( "Unsupported node: " . $childNode->name() . "<br>" );
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
        if ( count( $namedChildrenArray ) == 0 )
        {
            $class = eZContentClass::fetchByIdentifier( "folder" );
            {
                $creatorID = 14; // 14 == admin
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
    var $ImportDir = "var/cache/oo/import/";
}

?>
