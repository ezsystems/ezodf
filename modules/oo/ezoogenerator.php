<?php
//
// Definition of eZOOGenerator class
//
// Created on: <17-Nov-2004 10:11:05 bf>
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

/*! \file ezoogenerator.php
*/

include_once( "lib/ezfile/classes/ezfilehandler.php" );

/*!
  \class eZOOGenerator ezoogenerator.php
  \brief The class eZOOGenerator does

*/

define( "EZ_OO_TEXT", 1001 );
define( "EZ_OO_LINK", 1002 );

define( "EZ_OO_ERROR_TEMPLATE_NOT_READABLE", 1010 );
define( "EZ_OO_ERROR_COULD_NOT_COPY", 1011 );

class eZOOGenerator
{
    /*!
     Constructor
    */
    function eZOOGenerator()
    {
    }

    function writeDocument( )
    {
        $ooINI =& eZINI::instance( 'oo.ini' );

        // Initalize directories
        include_once( "lib/ezfile/classes/ezdir.php" );
        eZDir::mkdir( $this->OORootDir );
        eZDir::mkdir( $this->OOExportDir );
        eZDir::mkdir( $this->OOTemplateDir );

        // Write meta XML file
        $metaXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                   "<!DOCTYPE office:document-meta PUBLIC '-//OpenOffice.org//DTD OfficeDocument 1.0//EN' 'office.dtd'>" .
                   "<office:document-meta xmlns:office='http://openoffice.org/2000/office' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:dc='http://purl.org/dc/elements/1.1/' xmlns:meta='http://openoffice.org/2000/meta' office:version='1.0'>" .
                    "<office:meta>" .
                     "<meta:generator>eZ publish 3.5</meta:generator>" .
                     " <meta:creation-date>2004-11-10T11:39:50</meta:creation-date>" .
                     "  <dc:date>2004-11-10T11:40:15</dc:date>" .
                     "  <dc:language>en-US</dc:language>" .
                     "  <meta:editing-cycles>3</meta:editing-cycles>" .
                     "  <meta:editing-duration>PT26S</meta:editing-duration>" .
                     "  <meta:user-defined meta:name='Info 1'/>" .
                     "  <meta:user-defined meta:name='Info 2'/>" .
                     "  <meta:user-defined meta:name='Info 3'/>" .
                     "  <meta:user-defined meta:name='Info 4'/>" .
                     " <meta:document-statistic meta:table-count='0' meta:image-count='0' meta:object-count='0' meta:page-count='1' meta:paragraph-count='1' meta:word-count='2' meta:character-count='10'/>" .
                     " </office:meta>" .
                     "</office:document-meta>";

        $fileName = $this->OOExportDir . "meta.xml";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $metaXML );
        fclose( $fp );

        // Write settings XML file

        $settingsXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                       "<!DOCTYPE office:document-settings PUBLIC '-//OpenOffice.org//DTD OfficeDocument 1.0//EN' 'office.dtd'>" .
                       " <office:document-settings xmlns:office='http://openoffice.org/2000/office' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:config='http://openoffice.org/2001/config' office:version='1.0'>" .
                       "  <office:settings>" .
                       " </office:settings>" .
                       "</office:document-settings>";

        $fileName = $this->OOExportDir . "settings.xml";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $settingsXML );
        fclose( $fp );

        $useTemplate = ( $ooINI->variable( 'OOExport', 'UseTemplate' ) == "true" );
        $templateName = $ooINI->variable( 'OOExport', 'TemplateName' );
        if ( $useTemplate == true )
        {
            $templateFile = "extension/oo/templates/" . $templateName;
            // Check if zlib extension is loaded, if it's loaded use bundled ZIP library,
            // if not rely on the unzip commandline version.
            if ( !function_exists( 'gzopen' ) )
            {
                exec( "unzip -o $templateFile -d $this->OOTemplateDir", $unzipResult );
            }
            else
            {
                require_once('extension/oo/lib/pclzip.lib.php');
                $templateArchive = new PclZip( $templateFile );
                $templateArchive->extract( PCLZIP_OPT_PATH, $this->OOTemplateDir );

                if ( $templateArchive->errorCode() <> 0 )
                {
                    return array( EZ_OO_ERROR_TEMPLATE_NOT_READABLE, "Could not read template file" );
                }
            }

            // Copy styles.xml and images, if any to the document beeing generated
            if ( !copy( $this->OOTemplateDir . "styles.xml", $this->OOExportDir . "styles.xml" ) )
            {
                return array( EZ_OO_ERROR_COULD_NOT_COPY, "Could not copy the styles.xml file." );
            }

            $sourceDir = $this->OOTemplateDir . "Pictures";
            $destDir = $this->OOExportDir . "Pictures";
            eZDir::mkdir( $destDir );
            eZDir::copy( $sourceDir, $destDir, false, true );
        }
        else
        {
            // Generate a default empty styles.xml file

            $stylesXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                 "<!DOCTYPE office:document-styles PUBLIC '-//OpenOffice.org//DTD OfficeDocument 1.0//EN' 'office.dtd'>" .
                 "<office:document-styles xmlns:office='http://openoffice.org/2000/office' xmlns:style='http://openoffice.org/2000/style' xmlns:text='http://openoffice.org/2000/text' xmlns:table='http://openoffice.org/2000/table' xmlns:draw='http://openoffice.org/2000/drawing' xmlns:fo='http://www.w3.org/1999/XSL/Format' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:number='http://openoffice.org/2000/datastyle' xmlns:svg='http://www.w3.org/2000/svg' xmlns:chart='http://openoffice.org/2000/chart' xmlns:dr3d='http://openoffice.org/2000/dr3d' xmlns:math='http://www.w3.org/1998/Math/MathML' xmlns:form='http://openoffice.org/2000/form' xmlns:script='http://openoffice.org/2000/script' office:version='1.0'>" .
                 "   <office:styles>" .
                 "  </office:styles>" .
                 "</office:document-styles>";

            $fileName = $this->OOExportDir . "styles.xml";
            $fp = fopen( $fileName, "w" );
            fwrite( $fp, $stylesXML );
            fclose( $fp );
        }


        // Write mimetype file
        $mimeType = "application/vnd.sun.xml.writer";

        $fileName = $this->OOExportDir . "mimetype";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $mimeType );
        fclose( $fp );

        // Write content XML file
        $contentXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                      "<!DOCTYPE office:document-content PUBLIC '-//OpenOffice.org//DTD OfficeDocument1.0//EN' 'office.dtd'>" .
                      "<office:document-content xmlns:office='http://openoffice.org/2000/office' xmlns:style='http://openoffice.org/2000/style' xmlns:text='http://openoffice.org/2000/text' xmlns:table='http://openoffice.org/2000/table' xmlns:draw='http://openoffice.org/2000/drawing' xmlns:fo='http://www.w3.org/1999/XSL/Format' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:number='http://openoffice.org/2000/datastyle' xmlns:svg='http://www.w3.org/2000/svg' xmlns:chart='http://openoffice.org/2000/chart' xmlns:dr3d='http://openoffice.org/2000/dr3d' xmlns:math='http://www.w3.org/1998/Math/MathML' xmlns:form='http://openoffice.org/2000/form' xmlns:script='http://openoffice.org/2000/script' office:class='text' office:version='1.0'>" .
                     " <office:script/>" .
                     " <office:font-decls/>" .
                     " <office:automatic-styles>" .
                     "   <style:style style:name='imageright' style:family='graphics' style:parent-style-name='Graphics'>" .
                     "     <style:properties style:wrap='left' style:number-wrapped-paragraphs='no-limit' style:wrap-contour='false' style:vertical-pos='top' style:vertical-rel='paragraph' style:horizontal-pos='right' style:horizontal-rel='paragraph' style:mirror='none' fo:clip='rect(0inch 0inch 0inch 0inch)' draw:luminance='0%' draw:contrast='0%' draw:red='0%' draw:green='0%' draw:blue='0%' draw:gamma='1' draw:color-inversion='false' draw:transparency='0%' draw:color-mode='standard'/>" .
                     "  </style:style>\n" .
                     "  <style:style style:name='imageleft' style:family='graphics' style:parent-style-name='Graphics'>" .
                     "     <style:properties style:wrap='right' style:number-wrapped-paragraphs='no-limit' style:wrap-contour='false' style:horizontal-pos='left' style:horizontal-rel='paragraph' style:mirror='none' fo:clip='rect(0inch 0inch 0inch 0inch)' draw:luminance='0%' draw:contrast='0%' draw:red='0%' draw:green='0%' draw:blue='0%' draw:gamma='1' draw:color-inversion='false' draw:transparency='0%' draw:color-mode='standard'/>" .
                     "  </style:style>" .
                     " </office:automatic-styles>" .
                     " <office:body>" .
                     "  <text:sequence-decls>" .
                     "  <text:sequence-decl text:display-outline-level='0' text:name='Illustration'/>" .
                     "  <text:sequence-decl text:display-outline-level='0' text:name='Table'/>" .
                     "  <text:sequence-decl text:display-outline-level='0' text:name='Text'/>" .
                     "  <text:sequence-decl text:display-outline-level='0' text:name='Drawing'/>" .
                     "</text:sequence-decls>";

        // Add body contents
        foreach ( $this->DocumentArray as $element )
        {
            $contentXML .= $this->handleElement( $element );
        }

        // Add the content end
        $contentXML .= "</office:body></office:document-content>";

        $fileName = $this->OOExportDir . "content.xml";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $contentXML );
        fclose( $fp );

        // Write the manifest file
        $manifestXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                       "<!DOCTYPE manifest:manifest PUBLIC '-//OpenOffice.org//DTD Manifest 1.0//EN' 'Manifest.dtd'>" .
                       "<manifest:manifest xmlns:manifest='http://openoffice.org/2001/manifest'>" .
                       " <manifest:file-entry manifest:media-type='application/vnd.sun.xml.writer' manifest:full-path='/'/>" .
                       " <manifest:file-entry manifest:media-type='' manifest:full-path='Pictures/'/>" .
                       " <manifest:file-entry manifest:media-type='text/xml' manifest:full-path='content.xml'/>" .
                       " <manifest:file-entry manifest:media-type='text/xml' manifest:full-path='styles.xml'/>" .
                       " <manifest:file-entry manifest:media-type='text/xml' manifest:full-path='meta.xml'/>" .
                       " <manifest:file-entry manifest:media-type='text/xml' manifest:full-path='settings.xml'/>" .
                       "</manifest:manifest>";

        $fileName = $this->OOExportDir . "META-INF/manifest.xml";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $manifestXML );
        fclose( $fp );

        // Check if zlib extension is loaded, if it's loaded use bundled ZIP library,
        // if not rely on the zip commandline version.
        if ( true )
            // Todo: fix support for PclZip and correct zip of images.
//        if ( !function_exists( 'gzopen' ) )
        {
            $currentDir = getcwd();
            chdir( $this->OOExportDir );
            exec( "zip -r ../ootest.sxw *", $result );
            chdir( $currentDir );
        }
        else
        {
            require_once('extension/oo/lib/pclzip.lib.php');
            $archive = new PclZip( $this->OORootDir . "ootest.sxw" );
            $archive->create( $this->OOExportDir,
                              PCLZIP_OPT_REMOVE_PATH, $this->OOExportDir );
        }

        $fileName = $this->OORootDir . "ootest.sxw";

        // Clean up
        eZDir::recursiveDelete( $this->OOExportDir );
        eZDir::recursiveDelete( $this->OOTemplateDir);
        return $fileName;
    }

    /*!
      Adds a new header to the document.
    */
    function addHeader( $text, $level = 1 )
    {
        $this->DocumentArray[] = array( 'Type' => 'header',
                                        'Text' => $text,
                                        'Level' => $level );
    }

    /*!
      Adds a new paragraph to the document
    */
    function addParagraph( )
    {
        if ( func_num_args() > 0 and ( is_array( func_get_arg(0) ) ) )
        {
            $paragraphArray = array();
            foreach ( func_get_args() as $paragraphElement )
            {
                switch ( $paragraphElement[0] )
                {
                    case EZ_OO_TEXT:
                    {
                        $tagContent = $paragraphElement[1];

                        $tagContent =& str_replace( "&", "&amp;", $tagContent );
                        $tagContent =& str_replace( ">", "&gt;", $tagContent );
                        $tagContent =& str_replace( "<", "&lt;", $tagContent );
                        $tagContent =& str_replace( "'", "&apos;", $tagContent );
                        $tagContent =& str_replace( '"', "&quot;", $tagContent );

                        $paragraphArray[] = array( 'Type' => 'text', "Content" => $tagContent );
                    }break;

                    case EZ_OO_LINK:
                    {
                        $paragraphArray[] = array( 'Type' => 'link',
                                                   "Content" => $content = $paragraphElement[2],
                                                   "HREF" => $paragraphElement[1] );
                    }break;

                    default:
                    {
                        eZDebug::writeError( "Unknown paragraph element." );
                    }break;
                }
            }
        }
        else
        {
            $paragraphArray = array( array( 'Type' => 'text', "Content" => func_get_arg(0) ) );
        }

        $elementArray = array( 'Type' => 'paragraph',
                               'Content' => $paragraphArray );
        $this->addElement( $elementArray );
    }

    /*!
     \private
      Adds a new element to the document array.
     */
    function addElement( $elementArray )
    {
        // Check if we're inside a list or table
        if ( $this->CurrentStackNumber == 0 )
        {
            $this->DocumentArray[] = $elementArray;
        }
        else
        {
            if ( $this->DocumentStack[$this->CurrentStackNumber]['Type'] == 'list' )
            {
                // Add the paragraph inside a list
                $currentChild = $this->DocumentStack[$this->CurrentStackNumber]['CurrentChild'];
                $this->DocumentStack[$this->CurrentStackNumber]['ChildArray'][$currentChild][] = $elementArray;
            }
            else
            {
                // Add the paragraph inside a table cell
                $currentRow = $this->DocumentStack[$this->CurrentStackNumber]['CurrentRow'];
                $currentCell = $this->DocumentStack[$this->CurrentStackNumber]['CurrentCell'];
                $this->DocumentStack[$this->CurrentStackNumber]['ChildArray'][$currentRow][$currentCell][] = $elementArray;
            }
        }
    }

    /*!
      Adds an image to the document
    */
    function addImage( $fileName )
    {
        $elementArray = array( 'Type' => 'image',
                               'SRC' => $fileName['FileName'],
                               'Alignment' => $fileName['Alignment'],
                               'DisplayWidth' => $fileName['DisplayWidth'],
                               'DisplayHeight' => $fileName['DisplayHeight'] );

        $this->addElement( $elementArray );
    }

    /*!
      Starts an un-ordered or numbered list sequence. The $type parameter can either be the string
      unordered or ordered.
    */
    function startList( $type="unordered" )
    {
        $this->CurrentStackNumber += 1;
        $this->DocumentStack[$this->CurrentStackNumber]['Type'] = 'list';
        $this->DocumentStack[$this->CurrentStackNumber]['ListType'] = $type;
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentChild'] = 0;
        $this->DocumentStack[$this->CurrentStackNumber]['ChildArray'] = array();
    }

    /*!
      Creates a new list item.
    */
    function nextListItem()
    {
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentChild'] += 1;
    }

    /*!
      Ends a list sequence.
    */
    function endList()
    {
        $listItemArray = array();
        // Buils list item array
        foreach ( $this->DocumentStack[$this->CurrentStackNumber]['ChildArray'] as $listItem )
        {
            $listItemArray[] = array( 'Type' => 'listitem',
                                      'Content' => $listItem );
        }

        $this->CurrentStackNumber -= 1;

        if ( $this->CurrentStackNumber == 0 )
        {
            $this->DocumentArray[] = array( 'Type' => 'list',
                                            'ListType' => $this->DocumentStack[$this->CurrentStackNumber + 1]['ListType'],
                                            'Content' => $listItemArray );
        }
        else
        {
            // Inside a list or a table
        }
    }

    /*!
     Starts a new table sequence.
    */
    function startTable()
    {
        $this->CurrentStackNumber += 1;
        $this->DocumentStack[$this->CurrentStackNumber]['Tyoe'] = 'table';
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentRow'] = 0;
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentCell'] = 0;
        $this->DocumentStack[$this->CurrentStackNumber]['ChildArray'] = array();
    }

    /*!
      Starts a new table cell.
    */
    function nextCell()
    {
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentCell'] += 1;
    }

    /*!
      Starts a new table row.
    */
    function nextRow()
    {
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentRow'] += 1;
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentCell'] = 0;
    }

    /*!
      Ends a table sequence.
    */
    function endTable()
    {
        $rowArray = array();

        foreach ( $this->DocumentStack[$this->CurrentStackNumber]['ChildArray'] as $tableRowArray )
        {
            $cellArray = array();
            foreach ( $tableRowArray as $cell )
            {
                $cellArray[] = $cell;
            }
            $rowArray[] = $cellArray;
        }

        $this->CurrentStackNumber -= 1;

        if ( $this->CurrentStackNumber == 0 )
        {
            $this->DocumentArray[] = array( 'Type' => 'table',
                                            'Content' => $rowArray );
        }
        else
        {
            // Inside a list or a table
        }
    }

    function handleElement( $element )
    {
        $contentXML = "";
        switch ( $element['Type'] )
        {
            case "paragraph":
            {
                $contentXML .= "<text:p text:style-name='Standard'>";
                foreach ( $element['Content'] as $paragraphElement )
                {
                    switch ( $paragraphElement['Type'] )
                    {
                        case "text":
                        {
                            $contentXML .=  $paragraphElement['Content'];
                        }
                        break;

                        case "link":
                        {
                            $contentXML .= "<text:a xlink:type='simple' xlink:href='" . $paragraphElement['HREF']. "'>" . $paragraphElement['Content'] . "</text:a>";
                        }
                        break;

                        default:
                        {
                            eZDebug::writeError( "Unsupported paragraph element" );
                        }break;
                    }
                }
                $contentXML .= "</text:p>";


            }break;

            case "header":
            {
                $contentXML .= "\n<text:h text:style-name='Heading " . $element['Level'] . "' text:level='" . $element['Level'] . "'>" . $element['Text'] . "</text:h>\n";
            }break;

            case "image" :
            {
                $uniquePart = substr( md5( mktime() . rand( 0, 20000 ) ), 6 );
                $fileName = $element['SRC'];
                $destFile = $this->OOExportDir . "Pictures/" . $uniquePart . basename( $fileName );
                $relativeFile = "Pictures/" . $uniquePart . basename( $fileName );

                if ( copy( $fileName, $destFile ) )
                {
                    $realFileName = $destFile;
                    $sizeArray = getimagesize( $destFile );

                    $widthRatio = ( $element['DisplayWidth'] / 580 ) * 100;
                    $width = 6 * $widthRatio / 100;

                    $imageAspect = $sizeArray[0] / $sizeArray[1];
                    $height = $width / $imageAspect;

                    $styleName = "fr1";
                    if ( $element['Alignment'] == "left" )
                        $styleName = "imageleft";
                    if ( $element['Alignment'] == "right" )
                        $styleName = "imageright";

                    $contentXML .= "<text:p text:style-name='Standard'>" .
                         "<draw:image draw:style-name='$styleName'
                                                draw:name='Graphic1'
                                                text:anchor-type='paragraph'
                                                svg:width='" . $width ."inch'
                                                svg:height='" . $height . "inch'
                                                draw:z-index='0'
                                                xlink:href='#$relativeFile'
                                                xlink:type='simple'
                                                xlink:show='embed'
                                                xlink:actuate='onLoad'/>" .
                         "</text:p>";
                }
                else
                {
                    eZDebug::writeError( "Could not copy image while generating OpenOffice.org Writer document" );
                }
            }break;

            case 'list':
            {
                $listContent = "";
                foreach ( $element['Content'] as $listItem )
                {
                    $itemContent = "";
                    foreach ( $listItem['Content'] as $itemElement )
                    {
                        $itemContent .= $this->handleElement( $itemElement );
                    }
                    $listContent .= "<text:list-item>" . $itemContent . "</text:list-item>\n";
                }

                if ( $element['ListType'] == "ordered" )
                    $contentXML .= "<text:ordered-list text:style-name='L1'>" . $listContent . "</text:ordered-list>";
                else
                    $contentXML .= "<text:unordered-list text:style-name='L1'>" . $listContent . "</text:unordered-list>";

            }break;

            case 'table':
            {
                $cellCount = 0;
                $rowContent = "";
                foreach ( $element['Content'] as $rowArray )
                {
                    $cellContent = "";
                    $currentCellCount = 0;
                    foreach ( $rowArray as $cellArray )
                    {
                        $currentCellCount += 1;
                        if ( $currentCellCount > $cellCount )
                            $cellCount = $currentCellCount;
                        $cellElementContent = "";
                        foreach ( $cellArray as $cellElement )
                        {
                            $cellElementContent .= $this->handleElement( $cellElement );
                        }
                        $cellContent .= "<table:table-cell table:style-name='Table1.A1' table:value-type='string'>" . $cellElementContent . "</table:table-cell>";
                    }
                    $rowContent .= "<table:table-row>" . $cellContent . "</table:table-row>";
                }

                $numberLetter = "A";
                for ( $i =0; $i < $cellCount; $i++ )
                {
                    $columnDefinition .= "<table:table-column table:style-name='Table1.$numberLetter'/>";
                    $numberLetter++;
                }

                $contentXML .= "<table:table table:name='Table1' table:style-name='Table1'>\n" . $columnDefinition . $rowContent . "</table:table>";


            }break;

            default:
            {
            }break;
        }
        return $contentXML;
    }

    var $CurrentStackNumber = 0;
    var $DocumentStack = array();
    var $DocumentArray = array();

    var $OORootDir = "var/cache/oo/";
    var $OOExportDir = "var/cache/oo/export/";
    var $OOTemplateDir = "var/cache/oo/template/";
}

?>
