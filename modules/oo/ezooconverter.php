<?php
//
// Definition of eZOoconverter class
//
// Created on: <21-Jan-2005 09:52:07 bf>
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

/*! \file ezooconverter.php
*/
include_once( "extension/oo/modules/oo/ezoogenerator.php" );
include_once( "lib/ezxml/classes/ezxml.php" );

/*!
  \class eZOoconverter ezooconverter.php
  \brief The class handles the conversion from eZ publish objects to OO documents using the eZOOGenerator class

*/

class eZOOConverter
{
    /*!
     Constructor
    */
    function eZOOConverter()
    {
    }

    /*!
      Converts the eZ publish object with the given node id into an OpenOffice.org Writer document.
      The filename to the generated file is returned.
    */
    function objectToOO( $nodeID )
    {
        $ooGenerator = new eZOOGenerator();

        $node =& eZContentObjectTreeNode::fetch( $nodeID );

        if ( $node )
        {
            $object = $node->attribute( 'object' );
            $attributes = $object->contentObjectAttributes();
            $xml = new eZXML();

            foreach ( $attributes as $attribute )
            {
                switch ( $attribute->attribute( 'data_type_string' ) )
                {
                    case "ezstring":
                    {
                        $text = trim( $attribute->content() );
                        if ( $text != "" )
                        {
                            $ooGenerator->addHeader( $attribute->content() );
                        }
                    }break;

                    case "eztext":
                    {
                        $ooGenerator->addParagraph( $attribute->content() );
                    }break;

                    case "ezxmltext":
                    {
                        $xmlData = $attribute->attribute( 'data_text' );
                        $domTree =& $xml->domTree( $xmlData );
                        if ( $domTree )
                        {
                            $root = $domTree->root();
                            foreach ( $root->children() as $node )
                            {
                                eZOOConverter::handleNode( $node, $ooGenerator );
                            }
                        }
                    }break;

                    default:
                    {
                        eZDebug::writeError( "Unsupported attribute for OO conversion" . $attribute->attribute( 'data_type_string' ) );
                    }break;
                }
            }

            /*
            $ooGenerator->addHeader( "Test code from here" );

            $ooGenerator->startList( "unordered" );
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextListItem();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextListItem();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextListItem();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextListItem();
            $ooGenerator->endList( );

            $ooGenerator->startList( "ordered" );
            $ooGenerator->addParagraph( "Level 2" );
            $ooGenerator->nextListItem();
            $ooGenerator->addParagraph( "Level 2" );
            $ooGenerator->nextListItem();
            $ooGenerator->addParagraph( "Level 2" );
            $ooGenerator->nextListItem();
            $ooGenerator->addParagraph( "Level 2" );
            $ooGenerator->nextListItem();


            $ooGenerator->endList( );
            */
/*
            $ooGenerator->startTable();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();

            $ooGenerator->nextRow( "defaultstyle" );

            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );

            $ooGenerator->nextRow( "defaultstyle" );
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );

            $ooGenerator->endTable();

*/
            /*
            $ooGenerator->addHeader( "This is generated from PHP!!" );

            $ooGenerator->addParagraph( array( EZ_OO_TEXT, "Pent vaaaar i dag"),
                                        array( EZ_OO_LINK, "eZ systems", "http://ez.no"),
                                        array( EZ_OO_TEXT, "Test" ) );

            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->addHeader( "This is generated from PHP!!" );

            $ooGenerator->addImage( "documents/ooo_logo.gif" );

            $paragraph = $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );

            // Generating a list
            $ooGenerator->startList( "bullet/numbered" );

            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->addImage( "documents/ooo_logo.gif" );

            $ooGenerator->nextListItem();

            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->addHeader( "This is generated from PHP!!" );
            $ooGenerator->addImage( "documents/ooo_logo.gif" );

            $ooGenerator->endList();

            // Generate a table
            $ooGenerator->startTable();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();

            $ooGenerator->nextRow( "defaultstyle" );

            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell();
            $ooGenerator->nextCell();

            $ooGenerator->endTable();

            */

            $destFile = $ooGenerator->writeDocument();

            return $destFile;
        }
    }

    /*!
     \private
     Internal function to handle an eZXMLText node and convert it to OO format
    */
    function handleNode( $node, &$generator, $level = 0 )
    {
        switch ( $node->name() )
        {
            case "section":
            {
                foreach ( $node->children() as $childNode )
                {
                    eZOOConverter::handleNode( $childNode, $generator, $level + 1 );
                }
            }break;

            case "header":
            {
                if ( $level == 0 )
                    $level = 1;
                $generator->addHeader( trim( $node->textContent() ), $level );
            }break;

            case "paragraph":
            {
                $paragraphParameters = array();
                $imageArray = array();
                foreach ( $node->children() as $child )
                {
                    switch ( $child->name() )
                    {
                        case "line":
                        {
                            // Todo: support inline tags
                            $paragraphParameters[] = array( EZ_OO_TEXT, $child->textContent() );
                        }break;

                        case "#text":
                        {
                            $paragraphParameters[] = array( EZ_OO_TEXT, $child->content() );
                        }break;

                        case "link":
                        {
                            $paragraphParameters[] = array( EZ_OO_LINK, $child->attributeValue( "href" ), $child->textContent() );
                        }break;

                        case "ol":
                        case "ul":
                        {
                            if ( $child->name() == "ol" )
                                $generator->startList( "ordered" );
                            else
                                $generator->startList( "unordered" );

                            foreach ( $child->children() as $listItem )
                            {
                                foreach ( $listItem->children() as $childNode )
                                {
                                    if ( $childNode->name() == "#text" )
                                        $generator->addParagraph( $childNode->content() );
                                    else
                                        eZOOConverter::handleNode( $childNode, $generator, $level );
                                }
                                $generator->nextListItem();
                            }
                            $generator->endList();
                        }break;

                        case "table":
                        {
                            $generator->startTable();

                            foreach ( $child->children() as $row )
                            {
                                foreach ( $row->children() as $cell )
                                {
                                    foreach ( $cell->children() as $cellNode )
                                    {
                                        eZOOConverter::handleNode( $cellNode, $generator, $level );
                                    }
                                    $generator->nextCell();
                                }
                                $generator->nextRow();
                            }
                            $generator->endTable();
                        }break;

                        case "object":
                        {
                            // Only support objects of image class for now
                            $object = eZContentObject::fetch( $child->attributeValue( "id" ) );
                            if ( $object )
                            {
                                $classIdentifier = $object->attribute( "class_identifier" );

                                // Todo: read class identifiers from configuration
                                if ( $classIdentifier == "image" )
                                {
                                    $imageSize = $child->attributeValue( 'size' );
                                    $imageAlignment = $child->attributeValue( 'align' );

                                    $dataMap = $object->dataMap();
                                    $imageAttribute = $dataMap['image'];

                                    $imageHandler = $imageAttribute->content();
                                    $originalImage = $imageHandler->attribute( 'original' );
                                    $displayImage = $imageHandler->attribute( $imageSize );
                                    $displayWidth = $displayImage['width'];
                                    $displayHeight = $displayImage['height'];
                                    $imageArray[] = array( "FileName" => $originalImage['url'],
                                                           "Alignment" => $imageAlignment,
                                                           "DisplayWidth" => $displayWidth,
                                                           "DisplayHeight" => $displayHeight );
                                }
                            }

                        }break;

                        default:
                        {
                            eZDebug::writeError( "Unsupported node at this level" . $child->name() );

                        }break;

                    }
                }
                foreach ( $imageArray as $image )
                {
                    $generator->addImage( $image );
                }

                call_user_func_array( array( &$generator, "addParagraph" ), $paragraphParameters );

            }break;

            default:
            {
                eZDebug::writeError( "Unsupported node for document conversion: " . $node->name() );
            }break;
        }
    }
}

?>
