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
        $ooRootDir = "var/cache/oo/";
        $ooExportDir = "var/cache/oo/export/";
        $ooTemplateDir = $ooRootDir . "template/";
        eZDir::mkdir( $ooRootDir );
        eZDir::mkdir( $ooExportDir );
        eZDir::mkdir( $ooTemplateDir );

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

        $fileName = $ooExportDir . "meta.xml";
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

        $fileName = $ooExportDir . "settings.xml";
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
                exec( "unzip -o $templateFile -d $ooTemplateDir", $unzipResult );
            }
            else
            {
                require_once('extension/oo/lib/pclzip.lib.php');
                $templateArchive = new PclZip( $templateFile );
                $templateArchive->extract( PCLZIP_OPT_PATH, $ooTemplateDir );
            }

            // Copy styles.xml and images, if any to the document beeing generated
            if ( !copy( $ooTemplateDir . "styles.xml", $ooExportDir . "styles.xml" ) )
            {
                print( "Could not copy styles file" );
            }

            $sourceDir = $ooTemplateDir . "Pictures";
            $destDir = $ooExportDir . "Pictures";
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

            $fileName = $ooExportDir . "styles.xml";
            $fp = fopen( $fileName, "w" );
            fwrite( $fp, $stylesXML );
            fclose( $fp );
        }


        // Write mimetype file
        $mimeType = "application/vnd.sun.xml.writer";

        $fileName = $ooExportDir . "mimetype";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $mimeType );
        fclose( $fp );

        // Write content XML file
        $contentXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                      "<!DOCTYPE office:document-content PUBLIC '-//OpenOffice.org//DTD OfficeDocument1.0//EN' 'office.dtd'>" .
                      "<office:document-content xmlns:office='http://openoffice.org/2000/office' xmlns:style='http://openoffice.org/2000/style' xmlns:text='http://openoffice.org/2000/text' xmlns:table='http://openoffice.org/2000/table' xmlns:draw='http://openoffice.org/2000/drawing' xmlns:fo='http://www.w3.org/1999/XSL/Format' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:number='http://openoffice.org/2000/datastyle' xmlns:svg='http://www.w3.org/2000/svg' xmlns:chart='http://openoffice.org/2000/chart' xmlns:dr3d='http://openoffice.org/2000/dr3d' xmlns:math='http://www.w3.org/1998/Math/MathML' xmlns:form='http://openoffice.org/2000/form' xmlns:script='http://openoffice.org/2000/script' office:class='text' office:version='1.0'>" .
                     " <office:script/>" .
                     " <office:font-decls/>" .
                     " <office:automatic-styles/>" .
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
                    $contentXML .= "<text:h text:style-name='Heading 1' text:level='1'>" . $element['Text'] . "</text:h>";
                }break;

                case "image" :
                {
                    $uniquePart = substr( md5( mktime() . rand( 0, 20000 ) ), 6 );
                    $fileName = $element['SRC'];
                    $documentRoot = "var/cache/oo/";
                    $destFile = $documentRoot . "Pictures/" . $uniquePart . basename( $fileName );
                    $relativeFile = "Pictures/" . $uniquePart . basename( $fileName );

                    if ( copy( $fileName, $destFile ) )
                    {
                        $realFileName = $destFile;
                        $sizeArray = getimagesize( $destFile );

                        $width = ( (double)$sizeArray[0] / (double)150 );
                        $height = ( (double)$sizeArray[1] / (double)150 );

                        $contentXML .= "<text:p text:style-name='Standard'>" .
                             "<draw:image draw:style-name='fr1'
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

                default:
                {
                }break;
            }
        }

        // Add the content end
        $contentXML .= "</office:body></office:document-content>";

        $fileName = $ooExportDir . "content.xml";
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

        $fileName = $ooExportDir . "META-INF/manifest.xml";
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
            chdir( $ooExportDir );
            exec( "zip -r ../ootest.sxw *", $result );
            chdir( $currentDir );
        }
        else
        {
            require_once('extension/oo/lib/pclzip.lib.php');
            $archive = new PclZip( $ooRootDir . "ootest.sxw" );
            $archive->create( $ooExportDir,
                              PCLZIP_OPT_REMOVE_PATH, $ooExportDir );
        }

        $fileName = $ooRootDir . "ootest.sxw";

        return $fileName;
    }

    /*!
      Adds a new header to the document.
    */
    function addHeader( $text, $level )
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
            $this->DocumentArray[] = array( 'Type' => 'paragraph',
                                            'Content' => $paragraphArray );
        }
        else
        {
            $this->DocumentArray[] = array( 'Type' => 'paragraph',
                                            'Content' => array( array( 'Type' => 'text', "Content" => func_get_arg(0) ) ) );
        }
    }

    /*!
      Adds an image to the document
    */
    function addImage( $fileName )
    {
        $this->DocumentArray[] = array( 'Type' => 'image',
                                        'SRC' => $fileName );
    }

    var $DocumentArray = array();
}

?>
