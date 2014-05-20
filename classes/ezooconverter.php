<?php
//
// Definition of eZOoconverter class
//
// Created on: <21-Jan-2005 09:52:07 bf>
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

/*! \file ezooconverter.php
*/

/*!
  \class eZOoconverter ezooconverter.php
  \brief The class handles the conversion from eZ Publish objects to OO documents using the eZOOGenerator class

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
      Converts the eZ Publish object with the given node id into an OpenOffice.org Writer document.
      The filename to the generated file is returned.
    */
    static function objectToOO( $nodeID )
    {
        $node = eZContentObjectTreeNode::fetch( $nodeID );

        if ( !is_object( $node ) )
        {
            return false;
        }

        $ooGenerator = new eZOOGenerator();

        $object = $node->attribute( 'object' );
        $attributes = $object->contentObjectAttributes();

        $supportedDatatypes = array( 'ezstring', 'eztext', 'ezxmltext', 'ezimage', 'ezdate', 'ezdatetime', 'ezmatrix' );

        $odfINI = eZINI::instance( 'odf.ini' );
        $ClassMappingToHeader = ( $odfINI->variable( 'ODFExport', 'ClassAttributeMappingToHeader' ) == 'enabled' ) ? true : false;

        // @bf 2008-08-21: Fetch the current class identifier and the .ini settings for the enabled attributes for this
        $classIdentifier = $object->contentClassIdentifier();
        $enabledClassAttributes = $odfINI->hasVariable( $classIdentifier, 'Attribute' ) ? $odfINI->variable( $classIdentifier, 'Attribute' ) : array();

        foreach ( $attributes as $attribute )
        {
            $datatype = $attribute->attribute( 'data_type_string' );
            $identifier = $attribute->attribute( "contentclass_attribute_identifier" );

            if ( !in_array( $datatype, $supportedDatatypes ) )
            {
                eZDebug::writeError( "Attribute '$identifier' with unsupported datatype '$datatype' for OO conversion" );
                continue;
            }

            // @bf 2008-08-21: Only export attribute if it is enabled
            if ( !array_key_exists( $identifier, $enabledClassAttributes ) )
            {
                continue;
            }

            if( !$ClassMappingToHeader )
            {
                $ooGenerator->startSection( $identifier );
            }
            else
            {
                $ooGenerator->startClassMapHeader( $identifier );
            }

            switch ( $datatype )
            {
                case "ezstring":
                {
                    $text = trim( $attribute->content() );
                    if ( $text != "" )
                    {
                        $ooGenerator->addHeader( $text );
                    }
                } break;

                case "eztext":
                {
                    $ooGenerator->addParagraph( $attribute->content() );

                } break;

                case "ezxmltext":
                {
                    $xmlData = $attribute->attribute( 'data_text' );
                    $dom = new DOMDocument( '1.0', 'UTF-8' );
                    $dom->preserveWhiteSpace = false;
                    $success = $dom->loadXML( $xmlData );
                    if ( $success )
                    {
                        $root = $dom->documentElement;
                        foreach ( $root->childNodes as $node )
                        {
                            self::handleNode( $node, $ooGenerator );
                        }
                    }
                    else
                    {
                        eZDebug::writeError( "Unable to load XML data for attribute '$identifier'" );
                    }
                } break;

                case "ezimage":
                {
                    $originalImage = $attribute->content()->attribute( 'original' );

                    $fileHandler = eZClusterFileHandler::instance( $originalImage['url'] );
                    $uniqueFile = $fileHandler->fetchUnique();

                    $imageArray = array( "FileName" => $uniqueFile,
                                         "Alignment" => "center",
                                         "DisplayWidth" => $originalImage['width'],
                                         "DisplayHeight" => $originalImage['height'] );

                    $ooGenerator->addImage( $imageArray );
                } break;

                case "ezdate":
                {
                    $date = $attribute->content();
                    $ooGenerator->addParagraph( $date->attribute( "day" ) . "/" . $date->attribute( "month" ) . "/" . $date->attribute( "year" ) );
                } break;

                case "ezdatetime":
                {
                    $date = $attribute->content();
                    $ooGenerator->addParagraph( $date->attribute( "day" ) . "/" . $date->attribute( "month" ) . "/" . $date->attribute( "year" ) . " " . $date->attribute( "hour" )  . ":" . $date->attribute( "minute" )  );
                } break;

                case "ezmatrix":
                {
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
                } break;
            }

            if( !$ClassMappingToHeader )
            {
                $ooGenerator->endSection();
            }
        }

        return $ooGenerator->writeDocument();
    }

    /*!
     \private
     Internal function to handle an eZXMLText node and convert it to OO format
    */
    static function handleNode( $node, $generator, $level = 0 )
    {
        switch ( $node->localName )
        {
            case "section":
            {
                foreach ( $node->childNodes as $childNode )
                {
                    self::handleNode( $childNode, $generator, $level + 1 );
                }
            } break;

            case "header":
            {
                if ( $level == 0 )
                    $level = 1;

                $paragraphParameters = array();

                $prevLineBreak = false;
                foreach ( $node->childNodes as $childNode )
                {
                    $return = eZOOConverter::handleInlineNode( $childNode, $generator, $prevLineBreak );
                    $paragraphParameters = array_merge( $paragraphParameters,  $return['paragraph_parameters'] );
                    $prevLineBreak = ( $childNode->nodeType === XML_ELEMENT_NODE &&
                                       $childNode->localName === 'line' );
                }
                $generator->addHeader( trim( $node->nodeValue ), $level, $paragraphParameters );
            } break;

            case "ul":
            case "ol":
            {
                self::handleInlineNode( $node, $generator, $level );
            } break;

            case "paragraph":
            {
                $paragraphParameters = array();
                $imageArray = array();

                $prevLineBreak = false;
                foreach ( $node->childNodes as $child )
                {
                    $return = self::handleInlineNode( $child, $generator, $prevLineBreak );
                    $paragraphParameters = array_merge( $paragraphParameters, $return['paragraph_parameters'] );
                    $imageArray = array_merge( $imageArray, $return['image_array'] );

                    $prevLineBreak = ( $child->nodeType === XML_ELEMENT_NODE &&
                                       $child->localName === 'line' );
                }

                foreach ( $imageArray as $image )
                {
                    $generator->addImage( $image );
                }

                if ( !empty( $paragraphParameters ) && isset( $GLOBALS['CustomTagStyle'] ) and $GLOBALS['CustomTagStyle'] != false )
                {
                    array_unshift( $paragraphParameters, $GLOBALS['CustomTagStyle'] );
                }

                call_user_func_array( array( $generator, "addParagraph" ), $paragraphParameters );
            } break;

            default:
            {
                eZDebug::writeError( "Unsupported node for document conversion: " . $node->localName );
            }
        }
    }

    /*!
     Handles an inline node.

     Parameter $prevLineBreak added by Alex because it was used but not defined.
     */
    static function handleInlineNode( $child, $generator, $prevLineBreak = false )
    {
        $paragraphParameters = array();
        $imageArray = array();

        switch ( $child->localName )
        {
            case "line":
            {
                // @todo: (Alex) check why this is needed here and not after the next line
                if ( $prevLineBreak )
                {
                    $paragraphParameters[] = array( eZOOGenerator::LINE, '' );
                }

                // Todo: support inline tags
                $paragraphParameters[] = array( eZOOGenerator::TEXT, $child->textContent );

                foreach ( $child->childNodes as $lineChild )
                {
                    if ( $lineChild->localName == 'embed' )
                    {
                        // Only support objects of image class for now
                        $object = eZContentObject::fetch( $lineChild->getAttribute( "object_id" ) );
                        if ( $object && $object->canRead() )
                        {
                            $classIdentifier = $object->attribute( "class_identifier" );

                            // Todo: read class identifiers from configuration
                            if ( $classIdentifier == "image" )
                            {
                                $imageSize = $lineChild->getAttribute( 'size' );
                                if ( $imageSize == "" )
                                    $imageSize = "large";
                                $imageAlignment = $lineChild->getAttribute( 'align' );
                                if ( $imageAlignment == "" )
                                    $imageAlignment = "center";

                                $dataMap = $object->dataMap();
                                $imageAttribute = $dataMap['image'];

                                $imageHandler = $imageAttribute->content();
                                $originalImage = $imageHandler->attribute( 'original' );

                                $fileHandler = eZClusterFileHandler::instance( $originalImage['url'] );
                                $uniqueFile = $fileHandler->fetchUnique();

                                $displayImage = $imageHandler->attribute( $imageSize );
                                $displayWidth = $displayImage['width'];
                                $displayHeight = $displayImage['height'];
                                $imageArray[] = array( "FileName" => $uniqueFile,
                                                       "Alignment" => $imageAlignment,
                                                       "DisplayWidth" => $displayWidth,
                                                       "DisplayHeight" => $displayHeight );
                            }
                        }
                        else
                        {
                            eZDebug::writeError( "Image (object_id = " . $child->getAttribute( 'object_id' ) . " ) could not be used (does not exist or due to insufficient privileges)" );
                        }
                    }
                }
            } break;

            // text nodes
            case "":
            {
                $paragraphParameters[] = array( eZOOGenerator::TEXT, $child->textContent );
            } break;

            case "link":
            {
                $href = $child->getAttribute( 'href' );
                if ( !$href )
                {
                    $url_id = $child->getAttribute( 'url_id' );
                    if ( $url_id )
                    {
                        $eZUrl = eZURL::fetch( $url_id );
                        if ( is_object( $eZUrl ) )
                        {
                            $href = $eZUrl->attribute( 'url' );
                        }
                    }
                }

                $paragraphParameters[] = array( eZOOGenerator::LINK, $href, $child->textContent );
            } break;

            case "emphasize":
            case "strong":
            {
                $style = $child->localName == 'strong' ? 'bold' : 'italic';
                $paragraphParameters[] = array( eZOOGenerator::STYLE_START, $style );

                foreach ( $child->childNodes as $inlineNode )
                {
                    $return = self::handleInlineNode( $inlineNode );
                    $paragraphParameters = array_merge( $paragraphParameters, $return['paragraph_parameters'] );
                }

                $paragraphParameters[] = array( eZOOGenerator::STYLE_STOP );
            } break;

            case "literal":
            {
                $literalContent = $child->textContent;

                $literalContentArray = explode( "\n", $literalContent );
                foreach ( $literalContentArray as $literalLine )
                {
                    $generator->addParagraph( "Preformatted_20_Text", htmlspecialchars( $literalLine ) );
                }

            } break;

            case "custom":
            {
                $customTagName = $child->getAttribute( 'name' );

                // Check if the custom tag is inline
                $ini = eZINI::instance( 'content.ini' );

                $isInlineTagList = $ini->variable( 'CustomTagSettings', 'IsInline' );
                $isInline = ( array_key_exists( $customTagName, $isInlineTagList ) && $isInlineTagList[$customTagName] );

                // Handle inline custom tags
                if ( $isInline )
                {
                    $paragraphParameters[] = array( eZOOGenerator::STYLE_START, "eZCustominline_20_$customTagName" );
                    $paragraphParameters[] = array( eZOOGenerator::TEXT, $child->textContent );
                    $paragraphParameters[] = array( eZOOGenerator::STYLE_STOP );
                }
                else
                {
                    $GLOBALS['CustomTagStyle'] = "eZCustom_20_$customTagName";
                    foreach ( $child->childNodes as $customParagraph )
                    {
                        // Alex 2008-06-03: changed $level (3rd argument) to $prevLineBreak
                        self::handleNode( $customParagraph, $generator, $prevLineBreak );
                    }
                    $GLOBALS['CustomTagStyle'] = false;
                }
            } break;

            case "ol":
            case "ul":
            {
                $listType = $child->localName == "ol" ? "ordered" : "unordered";
                $generator->startList( $listType );

                foreach ( $child->childNodes as $listItem )
                {
                    foreach ( $listItem->childNodes as $childNode )
                    {
                        if ( $childNode->nodeType == XML_TEXT_NODE )
                        {
                            $generator->addParagraph( $childNode->textContent );
                        }
                        else
                        {
                            // Alex 2008-06-03: changed $level (3rd argument) to $prevLineBreak
                            self::handleNode( $childNode, $generator, $prevLineBreak );
                        }
                    }
                    $generator->nextListItem();
                }
                $generator->endList();
            } break;

            case "table":
            {
                $generator->startTable();
                $rows = 1;
                foreach ( $child->childNodes as $row )
                {
                    foreach ( $row->childNodes as $cell )
                    {
                        // Set the correct col span
                        $colSpan = $cell->getAttribute( "colspan" );
                        if ( is_numeric( $colSpan ) )
                        {
                            $generator->setCurrentColSpan( $colSpan );
                        }
                        // Check for table header
                        if ( $cell->localName == 'th' and $rows == 1 )
                        {
                            $generator->setIsInsideTableHeading( true );
                        }

                        // If the cell is empty, create a dummy so the cell is properly exported
                        if ( !$cell->hasChildNodes() )
                        {
                            $dummy = $cell->ownerDocument->createElement( "paragraph" );
                            $cell->appendChild( $dummy );
                            // Alex 2008-06-03: changed $level (3rd argument) to $prevLineBreak
                            eZOOConverter::handleNode( $dummy, $generator, $prevLineBreak );
                        }

                        eZDebug::writeDebug( $cell->ownerDocument->saveXML( $cell ), 'ezxmltext table cell' );
                        foreach ( $cell->childNodes as $cellNode )
                        {
                            // Alex 2008-06-03: changed $level (3rd argument) to $prevLineBreak
                            self::handleNode( $cellNode, $generator, $prevLineBreak );
                        }

                        $generator->nextCell();
                    }
                    $generator->nextRow();
                    ++$rows;
                }
                $generator->endTable();
            } break;

            case 'object':
            case 'embed':
            {
                $objectID = $child->localName == 'embed' ? $child->getAttribute( "object_id" ) : $child->getAttribute( "id" );

                // Only support objects of image class for now
                $object = eZContentObject::fetch( $objectID );
                if ( $object && $object->canRead() )
                {
                    $classIdentifier = $object->attribute( "class_identifier" );

                    // Todo: read class identifiers from configuration
                    if ( $classIdentifier == "image" )
                    {
                        $imageSize = $child->getAttribute( 'size' );
                        $imageAlignment = $child->getAttribute( 'align' );

                        $dataMap = $object->dataMap();
                        $imageAttribute = $dataMap['image'];

                        $imageHandler = $imageAttribute->content();
                        $originalImage = $imageHandler->attribute( 'original' );

                        $fileHandler = eZClusterFileHandler::instance( $originalImage['url'] );
                        $uniqueFile = $fileHandler->fetchUnique();

                        $displayImage = $imageHandler->attribute( $imageSize );
                        $displayWidth = $displayImage['width'];
                        $displayHeight = $displayImage['height'];
                        $imageArray[] = array( "FileName" => $uniqueFile,
                                               "Alignment" => $imageAlignment,
                                               "DisplayWidth" => $displayWidth,
                                               "DisplayHeight" => $displayHeight );
                    }
                }
                else
                {
                    eZDebug::writeError( "Image (object_id = " . $child->getAttribute( 'object_id' ) . " ) could not be used (does not exist or insufficient privileges)");
                }
            } break;

            default:
            {
                eZDebug::writeError( "Unsupported node at this level" . $child->localName );
            }
        }

        return array ( "paragraph_parameters" => $paragraphParameters,
                       "image_array" =>  $imageArray );
    }
}

?>
