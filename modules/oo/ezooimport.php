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
    function import( $file, $placeNodeID )
    {
        $importResult = array();
        include_once( "lib/ezfile/classes/ezdir.php" );
        $unzipResult = "";
        eZDir::mkdir( "var/cache/oo" );

        $http =& eZHTTPTool::instance();
        $file = $http->sessionVariable( "oo_import_filename" );

        // Check if zlib extension is loaded, if it's loaded use bundled ZIP library,
        // if not rely on the unzip commandline version.
        if ( !function_exists( 'gzopen' ) )
        {
            exec( "unzip -o $file -d var/cache/oo", $unzipResult );
        }
        else
        {
            require_once('extension/oo/lib/pclzip.lib.php');
            $archive = new PclZip( $file );
            $archive->extract( PCLZIP_OPT_PATH, "var/cache/oo" );
        }

        $fileName = "var/cache/oo/content.xml";
        $xml = new eZXML();
        $dom =& $xml->domTree( file_get_contents( $fileName ) );

        // Fetch the body section content
        $sectionNodeArray =& $dom->elementsByNameNS( 'section', 'http://openoffice.org/2000/text' );

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
                $sectionNameArray[] = strtolower( $sectionNode->attributeValueNS( "name", "http://openoffice.org/2000/text" ) );
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
                    print( "Found Section: " . $sectionName );
                    $xmlText = "";
                    foreach ( $sectionNode->children() as $childNode )
                    {
                    $xmlText .= eZOOImport::handleNode( $childNode, $level );
                    }
                    $xmlTextArray[$sectionName] = "<?xml version='1.0' encoding='utf-8' ?>" .
                         "<section xmlns:image='http://ez.no/namespaces/ezpublish3/image/' " .
                         "  xmlns:xhtml='http://ez.no/namespaces/ezpublish3/xhtml/'>\n" . $xmlText . "</section>";
                }
            }
        }

        if ( $customClassFound == false )
        {
            // No defined sections. Do default import.
            $bodyNodeArray =& $dom->elementsByNameNS( 'body', 'http://openoffice.org/2000/office' );

            if ( count( $bodyNodeArray ) == 1 )
            {
                $xmlText = "";
                foreach ( $bodyNodeArray[0]->children() as $childNode )
                {
                    $xmlText .= eZOOImport::handleNode( $childNode, $level );
                }
                $xmlTextBody = "<?xml version='1.0' encoding='utf-8' ?>" .
                     "<section xmlns:image='http://ez.no/namespaces/ezpublish3/image/' " .
                     "  xmlns:xhtml='http://ez.no/namespaces/ezpublish3/xhtml/'>\n" . $xmlText . "</section>";
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
                        {
                            $dataMap[$attributeIdentifier]->setAttribute( 'data_text', trim( strip_tags( $xmlTextArray[$sectionName] ) ) );
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

                $objectName = basename( $http->sessionVariable( "oo_import_original_filename" ), ".sxw" );
                $dataMap[$titleAttribute]->setAttribute( 'data_text', $objectName );
                $dataMap[$titleAttribute]->store();

                $dataMap[$bodyAttribute]->setAttribute( 'data_text', $xmlTextBody );
                $dataMap[$bodyAttribute]->store();
            }

            include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
            $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                         'version' => 1 ) );

            // Publish all embedded images as related objects
            foreach ( $this->RelatedImageArray as $image )
            {
                $contentObject->addContentObjectRelation( $image['ID'], 1 );
                print( "adding related object<br>" );
            }

            $mainNode = $contentObject->attribute( 'main_node' );
            // Create object stop.
            $importResult['URLAlias'] = $mainNode->attribute( 'url_alias' );
            $importResult['ClassIdentifier'] = $importClassIdentifier;
        }

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
                {
                    // do nothing
                }break;

                case 'section' :
                {
                    foreach ( $node->children() as $childNode )
                    {
                        $xhtmlTextContent  .= eZOOImport::handleNode( $childNode );
                    }

                }break;

                case 'h' :
                {
                    $level = $node->attributeValueNS( 'level', 'http://openoffice.org/2000/text' );

                    if ( $level > 6 )
                        $level = 6;

                    if ( $level >= 1 && $level <= 6 )
                    {
                        $sectionLevel = $level;
                        $headerContent = "";
                        foreach ( $node->children() as $childNode )
                        {
                            $headerContent .= eZOOImport::handleInlineNode( $childNode );
                        }

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


                case 'ordered-list' :
                {
                    $listContent = "";
                    foreach ( $node->children() as $itemNode )
                    {
                        if ( $itemNode->name() == 'list-item' )
                        {
                            foreach ( $itemNode->children() as $childNode )
                            {
                                // Remove strip tags, since it's supported with paragraphs in trunk
                                $listContent .= "<li>" . strip_tags( eZOOImport::handleNode( $childNode ) ) . "</li>";
                            }
                        }
                    }
                    $xhtmlTextContent .= "<paragraph><ol>" . $listContent . "</ol></paragraph>\n";
                }break;


                case 'unordered-list' :
                {
                    $listContent = "";
                    foreach ( $node->children() as $itemNode )
                    {
                        if ( $itemNode->name() == 'list-item' )
                        {
                            foreach ( $itemNode->children() as $childNode )
                            {
                                // Remove strip tags, since it's supported with paragraphs in trunk
                                $listContent .= "<li>" . strip_tags( eZOOImport::handleNode( $childNode ) ) . "</li>";
                            }
                        }
                    }
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
                                                $cellContent .= eZOOImport::handleNode( $tableContentNode );
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
                                        $cellContent .= eZOOImport::handleNode( $tableContentNode );
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
                    print( "Unsupported top node " . $node->name() . "<br/" );
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
            case "text-box":
            {
                foreach ( $childNode->children() as $textBoxNode )
                {
                    $boxContent .= eZOOImport::handleNode( $textBoxNode );
                }

                // Textboxes are defined inside paragraphs.
                $paragraphContent .= "</paragraph>$boxContent<paragraph>";
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

            case "image" :
            {
                $href = ltrim( $childNode->attributeValueNS( 'href', 'http://www.w3.org/1999/xlink' ), '#' );

                $href = "var/cache/oo/" . $href;

                if ( file_exists( $href ) )
                {
                    // Import image
                    $classID = 5;
                    $class =& eZContentClass::fetch( $classID );
                    $creatorID = 14; // 14 == admin
                    $parentNodeID = 51;
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

                    $dataMap['name']->setAttribute( 'data_text', "Imported Image" );
                    $dataMap['name']->store();

                    $imageContent =& $dataMap['image']->attribute( 'content' );
                    $imageContent->initializeFromFile( $href );
                    $dataMap['image']->store();

                    $this->RelatedImageArray[] = array( "ID" => $contentObjectID );

                    include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
                    $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                                 'version' => 1 ) );

                    $paragraphContent .= "<object id='$contentObjectID' align='center' size='large' />";
                }

            }break;

            default:
            {
                print( "Unsupported node: " . $childNode->name() . "<br>" );
            }break;

        }

        return $paragraphContent;
    }

    var $RelatedImageArray = array();
}

?>
