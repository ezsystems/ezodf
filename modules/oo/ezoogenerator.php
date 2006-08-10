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
define( "EZ_OO_STYLE_START", 2003 );
define( "EZ_OO_STYLE_STOP", 2004 );

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
        eZDir::mkdir( $this->OOExportDir . "/META-INF" );
        eZDir::mkdir( $this->OOTemplateDir );

        // Write meta XML file
        $metaXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                   "<office:document-meta xmlns:office='urn:oasis:names:tc:opendocument:xmlns:office:1.0' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:dc='http://purl.org/dc/elements/1.1/' xmlns:meta='urn:oasis:names:tc:opendocument:xmlns:meta:1.0' xmlns:ooo='http://openoffice.org/2004/office' office:version='1.0' xmlns:ezpublish='http://www.ez.no/ezpublish/oasis'>" .
                     "<office:meta>" .
                     "<meta:generator>eZ publish</meta:generator>" .
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
                       "<office:document-settings xmlns:office='urn:oasis:names:tc:opendocument:xmlns:office:1.0' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:config='urn:oasis:names:tc:opendocument:xmlns:config:1.0' xmlns:ooo='http://openoffice.org/2004/office' office:version='1.0'>" .
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
                         "<office:document-styles xmlns:office='urn:oasis:names:tc:opendocument:xmlns:office:1.0' xmlns:style='urn:oasis:names:tc:opendocument:xmlns:style:1.0' xmlns:text='urn:oasis:names:tc:opendocument:xmlns:text:1.0' xmlns:table='urn:oasis:names:tc:opendocument:xmlns:table:1.0' xmlns:draw='urn:oasis:names:tc:opendocument:xmlns:drawing:1.0' xmlns:fo='urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:dc='http://purl.org/dc/elements/1.1/' xmlns:meta='urn:oasis:names:tc:opendocument:xmlns:meta:1.0' xmlns:number='urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0' xmlns:svg='urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0' xmlns:chart='urn:oasis:names:tc:opendocument:xmlns:chart:1.0' xmlns:dr3d='urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0' xmlns:math='http://www.w3.org/1998/Math/MathML' xmlns:form='urn:oasis:names:tc:opendocument:xmlns:form:1.0' xmlns:script='urn:oasis:names:tc:opendocument:xmlns:script:1.0' xmlns:ooo='http://openoffice.org/2004/office' xmlns:ooow='http://openoffice.org/2004/writer' xmlns:oooc='http://openoffice.org/2004/calc' xmlns:dom='http://www.w3.org/2001/xml-events' office:version='1.0'>" .
                 "  <office:font-face-decls>" .
                 "  </office:font-face-decls>" .
                 "   <office:styles>" .
                 "     <style:style style:name='Table_20_Heading' style:display-name='Table Heading' style:family='paragraph' style:parent-style-name='Table_20_Contents' style:class='extra'>" .
                 " <style:paragraph-properties fo:text-align='center' style:justify-single-word='false' text:number-lines='false' text:line-number='0'/>" .
                 "  <style:text-properties fo:font-style='italic' fo:font-weight='bold' style:font-style-asian='italic' style:font-weight-asian='bold' style:font-style-complex='italic' style:font-weight-complex='bold'/>" .
                 " </style:style>" .

                 " <style:style style:name='Preformatted_20_Text' style:display-name='Preformatted Text' style:family='paragraph' style:parent-style-name='Standard' style:class='html'>" .
                 "   <style:paragraph-properties fo:margin-top='0in' fo:margin-bottom='0in'/>" .
                 "   <style:text-properties style:font-name='Courier New' fo:font-size='10pt' style:font-name-asian='Courier New' style:font-size-asian='10pt' style:font-name-complex='Courier New' style:font-size-complex='10pt'/>" .
                 "  </style:style>".

                 " <style:style style:name='eZCustom_20_factbox' style:display-name='eZCustom_20_factbox' style:family='paragraph' style:parent-style-name='Standard' style:class='text'>" .
                 "   <style:paragraph-properties fo:margin-top='0in' fo:margin-bottom='0in'/>" .
                 "   <style:text-properties style:font-name='Helvetica' fo:font-size='10pt' style:font-name-asian='Helvetica' style:font-size-asian='10pt' style:font-name-complex='Helvetica' style:font-size-complex='10pt'/>" .
                 "  </style:style>".
                 " <style:style style:name='eZCustom_20_quote' style:display-name='eZCustom_20_quote' style:family='paragraph' style:parent-style-name='Standard' style:class='text'>" .
                 "   <style:paragraph-properties fo:margin-top='0in' fo:margin-bottom='0in'/>" .
                 "   <style:text-properties style:font-name='Helvetica' fo:font-size='10pt' style:font-name-asian='Helvetica' style:font-size-asian='10pt' style:font-name-complex='Helvetica' style:font-size-complex='10pt'/>" .
                 "  </style:style>".


                 "  </office:styles>" .
                 "</office:document-styles>";

            $fileName = $this->OOExportDir . "styles.xml";
            $fp = fopen( $fileName, "w" );
            fwrite( $fp, $stylesXML );
            fclose( $fp );
        }


        // Write mimetype file
        $mimeType = "application/vnd.oasis.opendocument.text";

        $fileName = $this->OOExportDir . "mimetype";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $mimeType );
        fclose( $fp );

        // Write content XML file
        $contentXML = "<?xml version='1.0' encoding='UTF-8'?>" .
             "<!DOCTYPE office:document-content PUBLIC '-//OpenOffice.org//DTD OfficeDocument1.0//EN' 'office.dtd'>" .
             "<office:document-content xmlns:office='urn:oasis:names:tc:opendocument:xmlns:office:1.0'".
             "                          xmlns:meta='urn:oasis:names:tc:opendocument:xmlns:meta:1.0'" .
             "                          xmlns:config='urn:oasis:names:tc:opendocument:xmlns:config:1.0'" .
             "                          xmlns:text='urn:oasis:names:tc:opendocument:xmlns:text:1.0'" .
             "                          xmlns:table='urn:oasis:names:tc:opendocument:xmlns:table:1.0'" .
             "                          xmlns:draw='urn:oasis:names:tc:opendocument:xmlns:drawing:1.0'" .
             "                          xmlns:presentation='urn:oasis:names:tc:opendocument:xmlns:presentation:1.0'" .
             "                          xmlns:dr3d='urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0'" .
             "                          xmlns:chart='urn:oasis:names:tc:opendocument:xmlns:chart:1.0'" .
             "                          xmlns:form='urn:oasis:names:tc:opendocument:xmlns:form:1.0'" .
             "                          xmlns:script='urn:oasis:names:tc:opendocument:xmlns:script:1.0'" .
             "                          xmlns:style='urn:oasis:names:tc:opendocument:xmlns:style:1.0'" .
             "                          xmlns:number='urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0'" .
             "                          xmlns:math='http://www.w3.org/1998/Math/MathML'" .
             "                          xmlns:svg='urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0'" .
             "                          xmlns:fo='urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0'" .
             "                          xmlns:koffice='http://www.koffice.org/2005/'" .
             "                          xmlns:dc='http://purl.org/dc/elements/1.1/'" .
             "                          xmlns:xlink='http://www.w3.org/1999/xlink'>" .
             " <office:script/>" .
             " <office:font-face-decls/>" .
             " <office:automatic-styles>" .
             "  <text:list-style style:name='bulletlist'>" .
             "   <text:list-level-style-bullet text:level='1' text:style-name='Bullet_20_Symbols' style:num-suffix='.' text:bullet-char='•'>" .
             "      <style:list-level-properties text:space-before='0.25in' text:min-label-width='0.25in'/>" .
             "       <style:text-properties style:font-name='StarSymbol'/>" .
             "   </text:list-level-style-bullet>" .
             "  </text:list-style>" .
             "  <text:list-style style:name='numberedlist'>" .
             "   <text:list-level-style-number text:level='1' text:style-name='Numbering_20_Symbols' style:num-suffix='.' style:num-format='1'>" .
             "      <style:list-level-properties text:space-before='0.25in' text:min-label-width='0.25in'/>" .
             "   </text:list-level-style-number>" .
             "  </text:list-style>" .
             " <style:style style:name='imagecentered' style:family='graphic' style:parent-style-name='Graphics'>" .
             "  <style:graphic-properties style:horizontal-pos='center' style:horizontal-rel='paragraph' style:mirror='none' fo:clip='rect(0in 0in 0in 0in)' draw:luminance='0%' draw:contrast='0%' draw:red='0%' draw:green='0%' draw:blue='0%' draw:gamma='100%' draw:color-inversion='false' draw:image-opacity='100%' draw:color-mode='standard'/>" .
             " </style:style>" .
             " <style:style style:name='imageleft' style:family='graphic' style:parent-style-name='Graphics'>" .
             "   <style:graphic-properties style:wrap='right' style:horizontal-pos='left' style:horizontal-rel='paragraph' style:mirror='none' fo:clip='rect(0in 0in 0in 0in)' draw:luminance='0%' draw:contrast='0%' draw:red='0%' draw:green='0%' draw:blue='0%' draw:gamma='100%' draw:color-inversion='false' draw:image-opacity='100%' draw:color-mode='standard'/>" .
             "  </style:style>" .
             "  <style:style style:name='imageright' style:family='graphic' style:parent-style-name='Graphics'>" .
             "   <style:graphic-properties style:wrap='left' style:horizontal-pos='right' style:horizontal-rel='paragraph' style:mirror='none' fo:clip='rect(0in 0in 0in 0in)' draw:luminance='0%' draw:contrast='0%' draw:red='0%' draw:green='0%' draw:blue='0%' draw:gamma='100%' draw:color-inversion='false' draw:image-opacity='100%' draw:color-mode='standard'/>" .
             "  </style:style>" .
             " <style:style style:name='T1' style:family='text'>" .
             "   <style:text-properties fo:font-weight='bold' style:font-weight-asian='bold' style:font-weight-complex='bold'/>" .
             "   </style:style>" .
             " <style:style style:name='T2' style:family='text'>" .
             "  <style:text-properties fo:font-style='italic' style:font-style-asian='italic' style:font-style-complex='italic'/>" .
             " </style:style>" .
             " </office:automatic-styles>" .
             " <office:body>" .
             " <office:text>";


        $bodyXML = "";
        // Add body contents
        foreach ( $this->DocumentArray as $element )
        {
            $bodyXML .= $this->handleElement( $element );
        }

        // Handle charset conversion if needed
        include_once( 'lib/ezi18n/classes/eztextcodec.php' );
        $charset = 'UTF-8';
        $codec =& eZTextCodec::instance( false, $charset );
        $bodyXML = $codec->convertString( $bodyXML );

        $contentXML .= $bodyXML;

        // Add the content end
        $contentXML .= "</office:text></office:body></office:document-content>";

        $fileName = $this->OOExportDir . "content.xml";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $contentXML );
        fclose( $fp );

        // Write the manifest file
        $manifestXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                      "<!DOCTYPE manifest:manifest PUBLIC '-//OpenOffice.org//DTD Manifest 1.0//EN' 'Manifest.dtd'>" .
                      "<manifest:manifest xmlns:manifest='urn:oasis:names:tc:opendocument:xmlns:manifest:1.0'>" .
                      "<manifest:file-entry manifest:media-type='application/vnd.oasis.opendocument.text' manifest:full-path='/'/>" .
                      "<manifest:file-entry manifest:media-type='application/vnd.sun.xml.ui.configuration' manifest:full-path='Configurations2/'/>" .
                      "<manifest:file-entry manifest:media-type='' manifest:full-path='Pictures/'/>" .
                      "<manifest:file-entry manifest:media-type='text/xml' manifest:full-path='content.xml'/>" .
                      "<manifest:file-entry manifest:media-type='text/xml' manifest:full-path='styles.xml'/>" .
                      "<manifest:file-entry manifest:media-type='text/xml' manifest:full-path='meta.xml'/>" .
                      "<manifest:file-entry manifest:media-type='' manifest:full-path='Thumbnails/'/>" .
             "<manifest:file-entry manifest:media-type='text/xml' manifest:full-path='settings.xml'/>";

        // Do not include the thumnail file.
        // "<manifest:file-entry manifest:media-type='' manifest:full-path='Thumbnails/thumbnail.png'/>" .

        foreach ( $this->ImageFileArray as $imageFile )
        {
            $manifestXML .= "<manifest:file-entry manifest:media-type='' manifest:full-path='$imageFile'/>\n";
        }
        $manifestXML .= "</manifest:manifest>";

        $fileName = $this->OOExportDir . "META-INF/manifest.xml";
        $fp = fopen( $fileName, "w" );
        fwrite( $fp, $manifestXML );
        fclose( $fp );

        // Check if zlib extension is loaded, if it's loaded use bundled ZIP library,
        // if not rely on the zip commandline version.
            // Todo: fix support for PclZip and correct zip of images.
        if ( true )
//        if ( !function_exists( 'gzopen' ) )
        {
            $currentDir = getcwd();
            chdir( $this->OOExportDir );
            $zipPath = $ooINI->variable( 'OOo', 'ZipPath' );
            $execStr = $zipPath . "zip -r ../ootest.odt *";

            exec(  $execStr, $result );
            chdir( $currentDir );
        }
        else
        {
            require_once('extension/oo/lib/pclzip.lib.php');
            $archive = new PclZip( $this->OORootDir . "ootest.odt" );

            $archive->create( $this->OOExportDir,
                              PCLZIP_OPT_REMOVE_PATH, $this->OOExportDir );
        }

        $fileName = $this->OORootDir . "ootest.odt";


        // Clean up
   //     eZDir::recursiveDelete( $this->OOExportDir );
    //    eZDir::recursiveDelete( $this->OOTemplateDir);
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
        $style = "";
        $argArray = func_get_args();

        if ( func_num_args() > 1 )
        {
            // Check for style definition
            if ( !is_array( $argArray[0] ) )
            {
                $style = $argArray[0];
                $argArray = array_slice( $argArray, 1, count( $argArray ) -1 );
            }
        }

        if ( func_num_args() > 0 and ( is_array( $argArray[0] ) ) )
        {
            $paragraphArray = array();

            foreach ( $argArray as $paragraphElement )
            {
                switch ( $paragraphElement[0] )
                {
                    case EZ_OO_TEXT:
                    {
                        $tagContent = $paragraphElement[1];

                        $tagContent = str_replace( "&", "&amp;", $tagContent );
                        $tagContent = str_replace( ">", "&gt;", $tagContent );
                        $tagContent = str_replace( "<", "&lt;", $tagContent );
                        $tagContent = str_replace( "'", "&apos;", $tagContent );
                        $tagContent = str_replace( '"', "&quot;", $tagContent );

                        $paragraphArray[] = array( 'Type' => 'text', "Content" => $tagContent );
                    }break;

                    case EZ_OO_STYLE_START:
                    {
                        if ( $paragraphElement[1] == "bold" )
                            $paragraphArray[] = array( 'Type' => 'bold_start' );
                        if ( $paragraphElement[1] == "italic" )
                            $paragraphArray[] = array( 'Type' => 'italic_start' );

                        if ( substr( $paragraphElement[1], 0, 18 ) == "eZCustominline_20_" )
                            $paragraphArray[] = array( 'Type' => 'custom_inline_start', 'Name' => $paragraphElement[1] );

                    }break;

                    case EZ_OO_STYLE_STOP:
                    {
                        $paragraphArray[] = array( 'Type' => 'style_stop' );
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
            $paragraphArray = array( array( 'Type' => 'text', "Content" => $argArray[0] ) );
        }

        $elementArray = array( 'Type' => 'paragraph',
                               'Style' => $style,
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
                if ( is_numeric( $this->DocumentStack[$this->CurrentStackNumber]['CurrentColSpan'] ) )
                    $elementArray = array_merge( $elementArray, array( "ColSpan" => $this->DocumentStack[$this->CurrentStackNumber]['CurrentColSpan'] ) );
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
            $elementArray = array( 'Type' => 'list',
                                   'ListType' => $this->DocumentStack[$this->CurrentStackNumber + 1]['ListType'],
                                   'Content' => $listItemArray );

            $this->addElement( $elementArray );
        }
    }

    /*!
       Stars a new section with the given name
    */
    function startSection( $name )
    {
        $this->DocumentArray[] = array( 'Type' => 'section',
                                        'Text' => $name );
    }

    /*!
       Ends the current defined section
    */
    function endSection( )
    {
        $this->DocumentArray[] = array( 'Type' => 'end-section' );
    }

    /*!
     Starts a new table sequence.
    */
    function startTable()
    {
        $this->CurrentStackNumber += 1;
        $this->DocumentStack[$this->CurrentStackNumber]['Type'] = 'table';
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentRow'] = 0;
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentCell'] = 0;
        $this->DocumentStack[$this->CurrentStackNumber]['ChildArray'] = array();
    }

    /*!
      Starts a new table cell.
    */
    function nextCell( $colSpan = false )
    {
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentCell'] += 1;
        if ( $colSpan != false )
            $this->DocumentStack[$this->CurrentStackNumber]['CurrentColSpan'] = $colSpan;
        else
            unset( $this->DocumentStack[$this->CurrentStackNumber]['CurrentColSpan'] );
    }

    /*!
      Sets the col span for the current cell
     */
    function setCurrentColSpan( $colSpan )
    {
        $this->DocumentStack[$this->CurrentStackNumber]['CurrentColSpan'] = $colSpan;
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
                if ( $this->IsInsideTableHeading == true )
                    $contentXML .= "<text:p text:style-name='Table_20_Heading'>";
                else
                {
                    if ( $element['Style'] == "" )
                    {
                        $contentXML .= "<text:p text:style-name='Standard'>";
                    }
                    else
                    {
                        $contentXML .= "<text:p text:style-name='" . $element['Style'] . "'>";
                    }

                }


                foreach ( $element['Content'] as $paragraphElement )
                {
                    switch ( $paragraphElement['Type'] )
                    {
                        case "text":
                        {
                            $contentXML .=  $paragraphElement['Content'];
                        }
                        break;

                        case "custom_inline_start":
                        {
                            $contentXML .=  "<text:span text:style-name='" . $paragraphElement['Name'] . "'>";
                        }
                        break;

                        case "bold_start":
                        {
                            $contentXML .=  "<text:span text:style-name='T1'>";
                        }
                        break;

                        case "italic_start":
                        {
                            $contentXML .=  "<text:span text:style-name='T2'>";
                        }
                        break;

                        case "style_stop":
                        {
                            $contentXML .=  "</text:span>";
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

            case "section":
            {
                $contentXML .= "<text:section text:style-name='Sect1' text:name='" . $element['Text'] .  "'>\n";
            }break;

            case "end-section":
            {
                $contentXML .= "</text:section>\n   <text:p text:style-name='Standard'/>\n";
            }break;

            case "header":
            {
                $contentXML .= "\n<text:h text:style-name='Heading " . $element['Level'] . "' text:outline-level='" . $element['Level'] . "'>" . $element['Text'] . "</text:h>\n";
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

                    $this->ImageFileArray[] = "Pictures/" . $uniquePart . basename( $fileName );
                    $widthRatio = ( $element['DisplayWidth'] / 580 ) * 100;

                    // If image is larger than 300 px make it full page, or pixelsize
                    if ( $element['DisplayWidth'] >= 300 )
                    {
                        // Check how wide the image becomes in 75 dpi
                        $fullWidthInches = round( $sizeArray[0] / 75, 2 );
                        if ( $fullWidthInches > 5.77 )
                            $width = 5.77;
                        else
                            $width = $fullWidthInches;
                    }
                    else
                    {
                        $width = 6 * $widthRatio / 100;
                    }


                    $imageAspect = $sizeArray[0] / $sizeArray[1];
                    $height = $width / $imageAspect;

                    $styleName = "fr1";
                    if ( $element['Alignment'] == "left" )
                        $styleName = "imageleft";
                    if ( $element['Alignment'] == "right" )
                        $styleName = "imageright";

                    $contentXML .= "<text:p text:style-name='Standard'>" .
                                   "<draw:frame draw:style-name='$styleName'
                                                draw:name='graphics1'
                                                text:anchor-type='paragraph'
                                                svg:width='" . $width . "in'
                                                svg:height='" . $height . "in'
                                                draw:z-index='0'>" .
                                   "<draw:image xlink:href='$relativeFile'
                                                xlink:type='simple'
                                                xlink:show='embed'
                                                xlink:actuate='onLoad'/>" .
                         "</draw:frame>" .
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
                    $contentXML .= "<text:list text:style-name='numberedlist'>" . $listContent . "</text:list>";
                else
                    $contentXML .= "<text:list text:style-name='bulletlist'>" . $listContent . "</text:list>";

            }break;

            case 'table':
            {
                // Global counter of number of tables in document
                $tableCounter = 1;

                $columnCount = 0;
                $rowContent = "";
                $rowCount = 1;
                foreach ( $element['Content'] as $rowArray )
                {
                    $cellContent = "";
                    $currentCellCount = 0;
                    $cellLetter = "A";
                    foreach ( $rowArray as $cellArray )
                    {
                        $currentCellCount += 1;
                        $cellElementContent = "";
                        if ( $rowCount == 1 )
                            $this->IsInsideTableHeading = true;
                        else
                            $this->IsInsideTableHeading = false;

                        $colSpan = false;
                        foreach ( $cellArray as $cellElement )
                        {
                            // Check for colspan
                            if ( is_numeric( $cellElement['ColSpan'] ) )
                            {
                                $colSpan = $cellElement['ColSpan'];
                                // Increase cell count with 1-colspan
                                $currentCellCount += $colSpan - 1;
                            }
                            $cellElementContent .= $this->handleElement( $cellElement );

                        }

                        if ( $currentCellCount > $columnCount )
                            $columnCount = $currentCellCount;

                        $colSpanXML = "";
                        if ( $colSpan != false )
                            $colSpanXML = " table:number-columns-spanned='$colSpan' ";
                        $cellContent .= "    <table:table-cell table:style-name='Table$tableCounter.$cellLetter$rowCount' $colSpanXML office:value-type='string'>" . $cellElementContent . "</table:table-cell>\n";
                        $cellLetter++;
                    }

                    if ( $rowCount == 1 )
                        $rowContent .= "<table:table-header-rows>";

                    $rowContent .= "<table:table-row>\n" . $cellContent . "</table:table-row>\n";
                    if ( $rowCount == 1 )
                        $rowContent .= "</table:table-header-rows>";

                    $rowCount++;
                }

                $numberLetter = "A";
                $numberOfColumns = $columnCount;

                $columnDefinition .= "<table:table-column table:style-name='Table$tableCounter.$numberLetter' table:number-columns-repeated='$numberOfColumns' />\n";

                $contentXML .= "<table:table table:name='Table$tableCounter' table:style-name='Table$tableCounter'>\n" . $columnDefinition . $rowContent . "</table:table>";

            }break;

            default:
            {
                eZDebug::writeError( "Unsupported node type: " .$element['Type'] );
            }break;
        }
        return $contentXML;
    }

    var $IsInsideTableHeading = false;
    var $CurrentStackNumber = 0;
    var $DocumentStack = array();
    var $DocumentArray = array();

    var $ImageFileArray = array();

    var $OORootDir = "var/cache/oo/";
    var $OOExportDir = "var/cache/oo/export/";
    var $OOTemplateDir = "var/cache/oo/template/";
}

?>
