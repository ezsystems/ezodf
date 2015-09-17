<?php
//
// Definition of eZOOGenerator class
//
// Created on: <17-Nov-2004 10:11:05 bf>
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

/*! \file ezoogenerator.php
*/

/*!
  \class eZOOGenerator ezoogenerator.php
  \brief The class eZOOGenerator does

*/

class eZOOGenerator
{
    const TEXT = 1001;
    const LINK = 1002;
    const LINE = 1003;
    const STYLE_START = 2003;
    const STYLE_STOP = 2004;

    const ERROR_TEMPLATE_NOT_READABLE = 1010;
    const ERROR_COULD_NOT_COPY = 1011;

    /*!
     Constructor
    */
    function eZOOGenerator()
    {
    }

    function writeDocument( )
    {
        $ooINI = eZINI::instance( 'odf.ini' );

        // Initalize directories
        eZDir::mkdir( $this->OORootDir );
        eZDir::mkdir( $this->OOExportDir . "META-INF", false, true );
        eZDir::mkdir( $this->OOTemplateDir );

        $metaXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                   "<office:document-meta xmlns:office='urn:oasis:names:tc:opendocument:xmlns:office:1.0' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:dc='http://purl.org/dc/elements/1.1/' xmlns:meta='urn:oasis:names:tc:opendocument:xmlns:meta:1.0' xmlns:ooo='http://openoffice.org/2004/office' office:version='1.0' xmlns:ezpublish='http://www.ez.no/ezpublish/oasis'>" .
                     "<office:meta>" .
                     "<meta:generator>eZ Publish</meta:generator>" .
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

        file_put_contents( $this->OOExportDir . "meta.xml", $metaXML );

        $settingsXML = "<?xml version='1.0' encoding='UTF-8'?>" .
                       "<office:document-settings xmlns:office='urn:oasis:names:tc:opendocument:xmlns:office:1.0' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:config='urn:oasis:names:tc:opendocument:xmlns:config:1.0' xmlns:ooo='http://openoffice.org/2004/office' office:version='1.0'>" .
                       "  <office:settings>" .
                       " </office:settings>" .
                       "</office:document-settings>";
        file_put_contents( $this->OOExportDir . "settings.xml", $settingsXML );

        $useTemplate = ( $ooINI->variable( 'ODFExport', 'UseTemplate' ) == "true" );
        $templateName = $ooINI->variable( 'ODFExport', 'TemplateName' );
        if ( $useTemplate )
        {
            $templateRepository = "extension/ezodf/templates";   
            if ( $ooINI->hasVariable( 'ODFExport', 'TemplateRepository' ) )
            {
                $templateRepository = trim( $ooINI->variable( 'ODFExport', 'TemplateRepository' ), '/' );   
            }
            $templateFile = $templateRepository . "/" . $templateName;

            $archiveOptions = new ezcArchiveOptions( array( 'readOnly' => true ) );
            $archive = ezcArchive::open( $templateFile, null, $archiveOptions );

            $archive->extract( $this->OOTemplateDir );

            // Copy styles.xml and images, if any to the document being generated
            if ( !copy( $this->OOTemplateDir . "styles.xml", $this->OOExportDir . "styles.xml" ) )
            {
                return array( self::ERROR_COULD_NOT_COPY, "Could not copy the styles.xml file." );
            }

            $sourceDir = $this->OOTemplateDir . "Pictures";
            $destDir = $this->OOExportDir . "Pictures";
            eZDir::mkdir( $destDir, false, true );
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

            file_put_contents( $this->OOExportDir . "styles.xml", $stylesXML );
        }

        $mimeType = "application/vnd.oasis.opendocument.text";
        file_put_contents( $this->OOExportDir . "mimetype", $mimeType );

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
             "   <text:list-level-style-bullet text:level='1' text:style-name='Bullet_20_Symbols' style:num-suffix='.' text:bullet-char='●'>" .
             "      <style:list-level-properties text:space-before='0.25in' text:min-label-width='0.25in'/>" .
             "       <style:text-properties style:font-name='StarSymbol'/>" .
             "   </text:list-level-style-bullet>" .
             "   <text:list-level-style-bullet text:level='2' text:style-name='Bullet_20_Symbols' style:num-suffix='.' text:bullet-char='○'>" .
             "      <style:list-level-properties text:space-before='0.5in' text:min-label-width='0.25in'/>" .
             "       <style:text-properties style:font-name='StarSymbol'/>" .
             "   </text:list-level-style-bullet>" .
             "   <text:list-level-style-bullet text:level='3' text:style-name='Bullet_20_Symbols' style:num-suffix='.' text:bullet-char='■'>" .
             "      <style:list-level-properties text:space-before='0.75in' text:min-label-width='0.25in'/>" .
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
        $charset = 'UTF-8';
        $codec = eZTextCodec::instance( false, $charset );
        $bodyXML = $codec->convertString( $bodyXML );

        $contentXML .= $bodyXML;

        // Add the content end
        $contentXML .= "</office:text></office:body></office:document-content>";

        file_put_contents( $this->OOExportDir . "content.xml", $contentXML );

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

        file_put_contents( $this->OOExportDir . "META-INF/manifest.xml", $manifestXML );

        $fileName = $this->OORootDir . "ootest.odt";

        $zipArchive = ezcArchive::open( $fileName, ezcArchive::ZIP );
        $zipArchive->truncate();

        $prefix = $this->OOExportDir;
        $fileList = array();
        eZDir::recursiveList( $this->OOExportDir, $this->OOExportDir, $fileList );

        foreach ( $fileList as $fileInfo )
        {
            $path = $fileInfo['type'] === 'dir' ?
                $fileInfo['path'] . '/' . $fileInfo['name'] . '/' :
                $fileInfo['path'] . '/' . $fileInfo['name'];
            $zipArchive->append( array( $path ), $prefix );
        }

        $zipArchive->close();

        // Clean up
        eZDir::recursiveDelete( $this->OOExportDir );
        eZDir::recursiveDelete( $this->OOTemplateDir);

        // Clean up temporary image files if any
        $fileHandler = eZClusterFileHandler::instance();

        foreach ( $this->SourceImageArray as $sourceImageFile )
            $fileHandler->fileDeleteLocal( $sourceImageFile );

        return $fileName;
    }

    /*!
      Adds a new header to the document.

      Modified by Soushi.
      $paragraphArray parameter added by Soushi.
    */
    function addHeader( $text, $level = 1, $paragraphArray = array() )
    {
        $headerContents = array();
        $headerContents['Text'] = $text;
        foreach ( $paragraphArray as $paragraphElement )
        {
            switch ( $paragraphElement[0] )
            {
                case self::TEXT:
                {
                    $tagContent = $paragraphElement[1];

                    $tagContent = str_replace( "&", "&amp;", $tagContent );
                    $tagContent = str_replace( ">", "&gt;", $tagContent );
                    $tagContent = str_replace( "<", "&lt;", $tagContent );
                    $tagContent = str_replace( "'", "&apos;", $tagContent );
                    $tagContent = str_replace( '"', "&quot;", $tagContent );


                    $headerContents['Element'][] = array( 'Type' => 'text', "Content" => $tagContent );
                }break;

                case self::LINK:
                {
                    $headerContents['Element'][] = array( 'Type' => 'link',
                                                          "Content" => $content = $paragraphElement[2],
                                                          "HREF" => $paragraphElement[1] );
                }break;

                case self::LINE:
                {
                    $headerContents['Element'][] = array( 'Type' => 'line',
                                                          "Content" => '');
                }break;

                default:
                {
                    eZDebug::writeError( "Unknown paragraph element." );
                }break;
            }
        }
        $this->DocumentArray[] = array( 'Type' => 'header',
                                        'Content' => $headerContents,
                                        'Level' => $level);
    }

    /*!
      Adds a new paragraph to the document
    */
    function addParagraph()
    {
        $style = "";
        $numArgs = func_num_args();
        $argArray = func_get_args();

        if ( $numArgs > 1 )
        {
            // Check for style definition
            if ( !is_array( $argArray[0] ) )
            {
                $style = $argArray[0];
                $argArray = array_slice( $argArray, 1, count( $argArray ) -1 );
            }
        }

        if ( $numArgs > 0 and is_array( $argArray[0] ) )
        {
            $paragraphArray = array();

            foreach ( $argArray as $paragraphElement )
            {
                switch ( $paragraphElement[0] )
                {
                    case self::TEXT:
                    {
                        $tagContent = $paragraphElement[1];

                        $tagContent = str_replace( "&", "&amp;", $tagContent );
                        $tagContent = str_replace( ">", "&gt;", $tagContent );
                        $tagContent = str_replace( "<", "&lt;", $tagContent );
                        $tagContent = str_replace( "'", "&apos;", $tagContent );
                        $tagContent = str_replace( '"', "&quot;", $tagContent );

                        $paragraphArray[] = array( 'Type' => 'text', "Content" => $tagContent );
                    } break;

                    case self::STYLE_START:
                    {
                        if ( $paragraphElement[1] == "bold" )
                            $paragraphArray[] = array( 'Type' => 'bold_start' );
                        if ( $paragraphElement[1] == "italic" )
                            $paragraphArray[] = array( 'Type' => 'italic_start' );

                        if ( substr( $paragraphElement[1], 0, 18 ) == "eZCustominline_20_" )
                            $paragraphArray[] = array( 'Type' => 'custom_inline_start', 'Name' => $paragraphElement[1] );

                    } break;

                    case self::STYLE_STOP:
                    {
                        $paragraphArray[] = array( 'Type' => 'style_stop' );
                    } break;

                    case self::LINK:
                    {
                        $paragraphArray[] = array( 'Type' => 'link',
                                                   "Content" => $content = $paragraphElement[2],
                                                   "HREF" => $paragraphElement[1] );
                    } break;

                    case self::LINE:
                    {
                        $paragraphArray[] = array( 'Type' => 'line',
                                                   "Content" => '');
                    }break;

                    default:
                    {
                        eZDebug::writeError( "Unknown paragraph element." );
                    } break;
                }
            }
        }
        else if ( $numArgs > 0 )
        {
            // Alex 2008-06-03 - Added isset()
            $paragraphArray = array( array( 'Type' => 'text', "Content" => isset( $argArray[0] ) ? $argArray[0] : '' ) );
        }
        else
        {
            $paragraphArray = array( array( 'Type' => 'text', "Content" => '' ) );
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
                // Alex 2008-06-03 - Added isset()
                if ( isset( $this->DocumentStack[$this->CurrentStackNumber]['CurrentColSpan'] )
                     && is_numeric( $this->DocumentStack[$this->CurrentStackNumber]['CurrentColSpan'] ) )
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
    function startList( $type = "unordered" )
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
       Starts a new paragraph section with the given name.

       Added by Soushi.
    */
    function startClassMapHeader( $name )
    {
        $this->DocumentArray[] = array( 'Type' => 'class-map-header',
                                        'Text' => $name );
    }

    /*!
       Starts a new section with the given name.
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
      Sets the inside table heading
     */
    function setIsInsideTableHeading( $heading )
    {
        $this->IsInsideTableHeading = $heading;
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
        $this->CurrentStackNumber -= 1;

        if ( $this->CurrentStackNumber == 0 )
        {
            $this->DocumentArray[] = array( 'Type' => 'table',
                                            'Content' => $this->DocumentStack[1]['ChildArray'] );
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
                        }break;

                        case "custom_inline_start":
                        {
                            $contentXML .=  "<text:span text:style-name='" . $paragraphElement['Name'] . "'>";
                        }break;

                        case "bold_start":
                        {
                            $contentXML .=  "<text:span text:style-name='T1'>";
                        }break;

                        case "italic_start":
                        {
                            $contentXML .=  "<text:span text:style-name='T2'>";
                        }break;

                        case "style_stop":
                        {
                            $contentXML .=  "</text:span>";
                        }break;

                        case "link":
                        {
                            $contentXML .= "<text:a xlink:type='simple' xlink:href='" . $paragraphElement['HREF']. "'>" . $paragraphElement['Content'] . "</text:a>";
                        }break;

                        case "line":
                        {
                            $contentXML .= "<text:line-break />";
                        }break;

                        default:
                        {
                            eZDebug::writeError( "Unsupported paragraph element" );
                        }break;
                    }
                }
                $contentXML .= "</text:p>";


            }break;

            case "class-map-header":
            {
                $contentXML .= "<text:p text:style-name='Default'></text:p>" . "\n";
                $contentXML .= "<text:p text:style-name='eZSectionDefinition'>" . "\n";
                $contentXML .= $element['Text'];
                $contentXML .= "</text:p>". "\n";
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
                $contentXML .= "\n<text:h text:style-name='Heading " . $element['Level'] . "' text:outline-level='" . $element['Level'] . "'>";
                // Alex 2008/04/22 - Added isset()
                if ( isset( $element['Content']['Element'] ) )
                {
                    foreach( $element['Content']['Element'] as $headerElement )
                    {
                        switch ( $headerElement['Type'] )
                        {
                            case "text":
                            {
                                // @as 2008-11-25 comment: before merging to trunk,
                                // htmlspecialchars() was called on the 'Text' element of $element.
                                // But Soushi's changes do the same earlier (see lines 347-359 in this file)
                                // so htmlspecialchars() is not needed anymore
                                $contentXML .= $headerElement['Content'];
                            }break;

                            case "link":
                            {
                                $contentXML .= "<text:a xlink:type='simple' xlink:href='" . $headerElement['HREF']. "'>";

                                if( strlen( $element['ClassName'] ) )
                                {
                                    $contentXML .=  "<text:span text:style-name='eZClassification_20_" . $element['ClassName'] . "'>";
                                    $contentXML .=  $headerElement['Content'];
                                    $contentXML .=  '</text:span>';
                                }
                                else
                                {
                                    $contentXML .= $headerElement['Content'];

                                }
                                $contentXML .= "</text:a>";
                            }break;

                            case "line":
                            {
                                $contentXML .= "<text:line-break />";
                            }break;

                            default:
                            {
                                eZDebug::writeError( "Unsupported paragraph element" );
                            }break;
                        }
                    }
                }
                else
                {
                    $contentXML .= $element['Content']['Text'];
                }
                $contentXML .= "</text:h>\n";
            }break;

            case "image" :
            {
                $uniquePart = substr( md5( time() . rand( 0, 20000 ) ), 6 );
                $fileName = $element['SRC'];
                $relativeFile = "Pictures/" . $uniquePart . basename( $fileName );
                $destFile = $this->OOExportDir . $relativeFile;

                if ( copy( $fileName, $destFile ) )
                {
                    $realFileName = $destFile;
                    $sizeArray = getimagesize( $destFile );

                    $this->ImageFileArray[] = $relativeFile;
                    $this->SourceImageArray[] = $fileName;
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

                        if ( $rowCount > 1 )
                            $this->IsInsideTableHeading = false;

                        $colSpan = false;
                        foreach ( $cellArray as $cellElement )
                        {
                            // Check for colspan
                            // Alex 2008-06-03 - Added isset()
                            if ( isset( $cellElement['ColSpan'] )
                                 && is_numeric( $cellElement['ColSpan'] ) )
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

                    if ( $this->IsInsideTableHeading )
                        $rowContent .= "<table:table-header-rows>";

                    $rowContent .= "<table:table-row>\n" . $cellContent . "</table:table-row>\n";
                    if ( $this->IsInsideTableHeading )
                        $rowContent .= "</table:table-header-rows>";

                    $rowCount++;
                }

                $numberLetter = "A";
                $numberOfColumns = $columnCount;

                $columnDefinition = "<table:table-column table:style-name='Table$tableCounter.$numberLetter' table:number-columns-repeated='$numberOfColumns' />\n";

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
    var $SourceImageArray = array();

    var $OORootDir = "var/cache/ezodf/";
    var $OOExportDir = "var/cache/ezodf/export/";
    var $OOTemplateDir = "var/cache/ezodf/template/";
}

?>
