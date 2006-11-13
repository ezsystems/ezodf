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
include_once( "extension/ezodf/modules/odf/ezoogenerator.php" );
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

        $node = eZContentObjectTreeNode::fetch( $nodeID );

        if ( $node )
        {
            $object = $node->attribute( 'object' );
            $attributes = $object->contentObjectAttributes();
            $xml = new eZXML();

            // Clear the view cache when exporting, for some reason images are re-generated and the resolution is becomming poor
            include_once( "kernel/classes/ezcontentcachemanager.php" );
            eZContentCacheManager::clearObjectViewCache( $object->attribute( "id" ) );

            foreach ( $attributes as $attribute )
            {
                switch ( $attribute->attribute( 'data_type_string' ) )
                {
                    case "ezstring":
                    {
                        $text = trim( $attribute->content() );
                        if ( $text != "" )
                        {
                            $ooGenerator->startSection( $attribute->attribute( "contentclass_attribute_identifier" ) );
                            $ooGenerator->addHeader( $attribute->content() );
                            $ooGenerator->endSection( );
                        }
                    }break;

                    case "eztext":
                    {
                        $ooGenerator->startSection( $attribute->attribute( "contentclass_attribute_identifier" ) );
                        $ooGenerator->addParagraph( $attribute->content() );
                        $ooGenerator->endSection();

                    }break;

                    case "ezxmltext":
                    {
                        $ooGenerator->startSection( $attribute->attribute( "contentclass_attribute_identifier" ) );

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
                        $ooGenerator->endSection( );
                    }break;
                    
                    
                    case "ezimage":
                    {
                        $ooGenerator->startSection( $attribute->attribute( "contentclass_attribute_identifier" ) );

                        $imageHandler = $attribute->content();
                        $originalImage = $imageHandler->attribute( 'original' );
                        $displayImage = $imageHandler->attribute( 'original' );
                        $displayWidth = $displayImage['width'];
                        $displayHeight = $displayImage['height'];

                        $imageArray = array( "FileName" => $originalImage['url'],
                                               "Alignment" => "center",
                                               "DisplayWidth" => $displayWidth,
                                               "DisplayHeight" => $displayHeight );
                                               
			            $ooGenerator->addImage( $imageArray);
                        
						$ooGenerator->endSection();

                    }break;

                    case "ezdate":
                    {
                        $ooGenerator->startSection( $attribute->attribute( "contentclass_attribute_identifier" ) );

						$date = $attribute->content();						
	                    $ooGenerator->addParagraph( $date->attribute( "day" ) . "/" . $date->attribute( "month" ) . "/" . $date->attribute( "year" ) );
					
						$ooGenerator->endSection();

                    }break;


                    case "ezdatetime":
                    {
                        $ooGenerator->startSection( $attribute->attribute( "contentclass_attribute_identifier" ) );

						$date = $attribute->content();						
	                    $ooGenerator->addParagraph( $date->attribute( "day" ) . "/" . $date->attribute( "month" ) . "/" . $date->attribute( "year" ) . " " . $date->attribute( "hour" )  . ":" . $date->attribute( "minute" )  );
										
						$ooGenerator->endSection();

                    }break;
                    
                    case "ezmatrix":
                    {
                        $ooGenerator->startSection( $attribute->attribute( "contentclass_attribute_identifier" ) );
						
						$matrix = $attribute->content();
						
						$columns = $matrix->attribute( "columns" );
						
   					    $ooGenerator->startTable();
   					    
						foreach ( $columns['sequential'] as $column )
						{
			            	$ooGenerator->addParagraph( $column['name'] );
			            	$ooGenerator->nextCell();           
						}
						
	     	            $ooGenerator->nextRow( "defaultstyle" );
	     	            	     	            
						$rows = $matrix->attribute( "rows" );
						
						foreach ( $rows['sequential'] as $row )
						{
							foreach ( $row['columns'] as $cell )
						    {
								$ooGenerator->addParagraph( $cell );
			            		$ooGenerator->nextCell();         
							}
		     	            $ooGenerator->nextRow( "defaultstyle" );
						}
						
                        $ooGenerator->endTable();
					
						$ooGenerator->endSection();

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
            $ooGenerator->setCurrentColSpan( 2 );
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->addParagraph( "This is just a sample paragraph. And it's of course added via PHP." );
            $ooGenerator->nextCell(2);
            $ooGenerator->setCurrentColSpan( 2 );

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

            $ooGenerator->addParagraph( array( EZ_OO_STYLE_START, "bold" ),
                                        array( EZ_OO_TEXT, "Pent vaaaar i dag"),
                                        array( EZ_OO_STYLE_STOP ),
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

            */
            /*
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

            $ooGenerator->addParagraph( "Preformatted_20_Text", array( EZ_OO_TEXT, "This is just a sample paragraph. And it's of course added via PHP." ) );
            $ooGenerator->addParagraph( "eZ_PRE_style", "Normal text here." );

            */


            $destFile = $ooGenerator->writeDocument();

            return $destFile;
        }

        // Conversion failed
        return false;
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

            case "ul":
            case "ol":
            {
                eZOOConverter::handleInlineNode( $node, $generator, $level );
            }break;

            case "paragraph":
            {
                $paragraphParameters = array();
                $imageArray = array();
                foreach ( $node->children() as $child )
                {
                    $return = eZOOConverter::handleInlineNode( $child, $generator );
                    $paragraphParameters = array_merge( $paragraphParameters,  $return['paragraph_parameters'] );
                    $imageArray = $return['image_array'];
                }
                foreach ( $imageArray as $image )
                {
                    $generator->addImage( $image );
                }

                if ( isset( $GLOBALS['CustomTagStyle'] ) and $GLOBALS['CustomTagStyle'] != false )
                    call_user_func_array( array( &$generator, "addParagraph" ), array_merge( $GLOBALS['CustomTagStyle'], $paragraphParameters ) );
                else
                    call_user_func_array( array( &$generator, "addParagraph" ), $paragraphParameters );
            }break;

            default:
            {

                eZDebug::writeError( "Unsupported node for document conversion: " . $node->name() );
            }break;
        }
    }

    function handleInlineNode( $child, &$generator )
    {
        $paragraphParameters = array();
        $imageArray = array();

        switch ( $child->name() )
        {
            case "line":
            {
                // Todo: support inline tags
                $paragraphParameters[] = array( EZ_OO_TEXT, $child->textContent() );

                foreach ( $child->children() as $lineChild )
                {
                    switch ( $lineChild->name() )
                    {
                        case "embed":
                        {
                            // Only support objects of image class for now
                            $object = eZContentObject::fetch( $lineChild->attributeValue( "object_id" ) );
                            if ( $object && $object->canRead() )
                            {

                                $classIdentifier = $object->attribute( "class_identifier" );

                                // Todo: read class identifiers from configuration
                                if ( $classIdentifier == "image" )
                                {
                                    $imageSize = $lineChild->attributeValue( 'size' );
                                    if ( $imageSize == "" )
                                        $imageSize = "large";
                                    $imageAlignment = $lineChild->attributeValue( 'align' );
                                    if ( $imageAlignment == "" )
                                        $imageAlignment = "center";

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
                            else
                            {
                                eZDebug::writeError( "Image (object_id = " . $child->attributeValue( 'object_id' ) . " ) could not be used (does not exist or due to insufficient privileges)" );
                            }
                        }break;
                    }
                }
            }break;

            case "#text":
            {
                $paragraphParameters[] = array( EZ_OO_TEXT, $child->content() );
            }break;

            case "link":
            {
                $paragraphParameters[] = array( EZ_OO_LINK, $child->attributeValue( "href" ), $child->textContent() );
            }break;

            case "emphasize":
            {
                $paragraphParameters[] = array( EZ_OO_STYLE_START, "italic" );

                foreach ( $child->children() as $inlineNode )
                {
                    $return = eZOOConverter::handleInlineNode( $inlineNode );
                    $paragraphParameters = array_merge( $paragraphParameters, $return['paragraph_parameters'] );
                }

                $paragraphParameters[] = array( EZ_OO_STYLE_STOP );
            }break;

            case "strong":
            {
                $paragraphParameters[] = array( EZ_OO_STYLE_START, "bold" );

                foreach ( $child->children() as $inlineNode )
                {
                    $return = eZOOConverter::handleInlineNode( $inlineNode );
                    $paragraphParameters = array_merge( $paragraphParameters, $return['paragraph_parameters'] );
                }
                $paragraphParameters[] = array( EZ_OO_STYLE_STOP );
            }break;

            case "literal":
            {
                $literalContent = $child->textContent();

                $literalContentArray = explode( "\n", $literalContent );
                foreach ( $literalContentArray as $literalLine )
                {
                    $generator->addParagraph( "Preformatted_20_Text", htmlspecialchars( $literalLine ) );
                }

            }break;

            case "custom":
            {
                $customTagName = $child->attributeValue( 'name' );

                // Check if the custom tag is inline
                $isInline = false;
                include_once( "lib/ezutils/classes/ezini.php" );
                $ini =& eZINI::instance( 'content.ini' );

                $isInlineTagList =& $ini->variable( 'CustomTagSettings', 'IsInline' );
                foreach ( array_keys ( $isInlineTagList ) as $key )
                {
                    $isInlineTagValue =& $isInlineTagList[$key];
                    if ( $isInlineTagValue )
                    {
                        if ( $customTagName == $key )
                            $isInline = true;
                    }
                }

                // Handle inline custom tags
                if ( $isInline == true )
                {
                    $paragraphParameters[] = array( EZ_OO_STYLE_START, "eZCustominline_20_inlineliteral" );
                    $paragraphParameters[] = array( EZ_OO_TEXT, $child->textContent() );
                    $paragraphParameters[] = array( EZ_OO_STYLE_STOP );
                }
                else
                {
                    $GLOBALS['CustomTagStyle'] = "eZCustom_20_$customTagName";

                    foreach ( $child->children() as $customParagraph )
                    {
                        eZOOConverter::handleNode( $customParagraph, $generator, $level );
                    }

                    $GLOBALS['CustomTagStyle'] = false;
                }
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
                        {
                            eZOOConverter::handleNode( $childNode, $generator, $level );
                        }
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
                        // Set the correct col span
                        $colSpan = $cell->attributeValue( "colspan" );
                        if ( is_numeric( $colSpan ) )
                        {
                            $generator->setCurrentColSpan( $colSpan );
                        }

                        foreach ( $cell->children() as $cellNode )
                        {
                            eZOOConverter::handleNode( $cellNode, $generator, $level );
                        }
                        // If the cell is empty, create a dummy so the cell is properly exported
                        if ( count( $cell->children() ) == 0 )
                        {
                            $n = new eZDOMNode();
                            $n->setType( 1 );
                            $n->setName( "paragraph" );
                            eZOOConverter::handleNode( $n, $generator, $level );
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

            case "embed":
            {
                // Only support objects of image class for now and those we can read
                $object = eZContentObject::fetch( $child->attributeValue( "object_id" ) );
                if ( $object && $object->canRead() )
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
                else
                {
                    eZDebug::writeError( "Image (object_id = " . $child->attributeValue( 'object_id' ) . " ) could not be used (does not exist or insufficient privileges)");
                }
            }break;

            default:
            {
                eZDebug::writeError( "Unsupported node at this level" . $child->name() );

            }break;

        }


        return array ( "paragraph_parameters" => $paragraphParameters,
                       "image_array" =>  $imageArray );

    }
}

?>
