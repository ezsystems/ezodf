<?php
//
// Created on: <10-Nov-2004 11:42:23 bf>
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

include_once( "kernel/common/template.php" );

include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );

include_once( "kernel/classes/ezcontentbrowse.php" );

include_once( "extension/oo/modules/oo/ezooconverter.php" );


$http =& eZHTTPTool::instance();
$module =& $Params["Module"];
$NodeID = $Params['NodeID'];
$exportTypeParam = $Params['ExportType'];

$tpl =& templateInit();

$success = true;

if ( $http->hasPostVariable( "ExportButton" ) )
{
    eZContentBrowse::browse( array( 'action_name' => 'OOPlace',
                                    'description_template' => 'design:oo/browse_place.tpl',
                                    'content' => array(),
                                    'from_page' => '/oo/export/',
                                    'cancel_page' => '/oo/export/' ),
                             $module );
    return;
}

$doExport = false;
if ( $module->isCurrentAction( 'OOPlace' ) )
{
    // We have the file and the placement. Do the actual import.
    $selectedNodeIDArray = eZContentBrowse::result( 'OOPlace' );
    $nodeID = $selectedNodeIDArray[0];
    $doExport = true;
}

if ( $http->hasPostVariable( "NodeID" ) )
{
    $nodeID = $http->postVariable( "NodeID" );
    $doExport = true;
}
else if ( is_numeric( $NodeID ) )
{
    $nodeID = $NodeID;
    $doExport = true;
}

$exportType = false;
if ( $http->hasPostVariable( "ExportType" ) )
{
    $type = $http->postVariable( "ExportType" );

    if ( $type == "PDF" or $type == "Word" )
    {
        $exportType = $type;
    }
    else
    {
        $tpl->setVariable( "error_string", ezi18n( 'extension/oo/export/error',"Destination file format not supported" ) );
        $success = false;
    }
}
else if ( $exportTypeParam == "PDF" or $exportTypeParam == "Word" )
{
    $exportType = $exportTypeParam;
}
else if ( strlen( trim ( $exportTypeParam) ) != 0 )
{
    $tpl->setVariable( "error_string", ezi18n( 'extension/oo/export/error',"Destination file format not supported" ) );
    $success = false;
}

$ooINI =& eZINI::instance( 'oo.ini' );
//$tmpDir = $ooINI->variable( 'OOo', 'TmpDir' );
$tmpDir = getcwd() . "/" . eZSys::cacheDirectory();

if ( $doExport == true )
{

    if ( is_numeric( $nodeID ) )
    {

        $node = eZContentObjectTreeNode::fetch( $nodeID );

        // Check if we have read access to this node
        if ( $node && $node->canRead() )
        {
            // Do the actual eZ publish export
            $fileName = eZOOConverter::objectToOO( $nodeID );

            if ( !is_array( $fileName ) )
            {
                $nodeName = $node->attribute( 'name' );

                $originalFileName = $nodeName . ".odt";
                $contentType = "application/vnd.oasis.opendocument.text";

                include_once( 'lib/ezi18n/classes/ezchartransform.php' );
                $trans =& eZCharTransform::instance();
                $nodeName = $trans->transformByGroup( $nodeName, 'urlalias' );

                $uniqueStamp = md5( mktime() );

                $server = $ooINI->variable( "OOImport", "OOConverterAddress" );
                $port = $ooINI->variable( "OOImport", "OOConverterPort" );

                switch ( $exportType )
                {
                    case "PDF" :
                    {
                        if ( ( $result = deamonConvert( $server, $port, realpath( $fileName ), "convertToPDF", $tmpDir . "/ooo_converted_$uniqueStamp.pdf" ) ) )
                        {
                            $originalFileName = $nodeName . ".pdf";
                            $contentType = "application/pdf";
                            $fileName = $tmpDir . "/ooo_converted_$uniqueStamp.pdf";
                        }
                        else
                        {
                            $success = false;
                            $tpl->setVariable( "error_string", ezi18n( 'extension/oo/export/error',"PDF conversion failed" ) );
                        }

                    }break;

                    case "Word" :
                    {
                        if ( ( $result = deamonConvert( $server, $port, realpath( $fileName ), "convertToWord", $tmpDir . "/ooo_converted_$uniqueStamp.pdf" ) ) )
                        {
                            $originalFileName = $nodeName . ".doc";
                            $contentType = "application/ms-word";
                            $fileName = $tmpDir . "/ooo_converted_$uniqueStamp.doc";
                        }
                        else
                        {
                            $success = false;
                            $tpl->setVariable( "error_string", ezi18n( 'extension/oo/export/error',"Word conversion failed" ) );
                        }

                    }break;

                }

            }
            else
            {
                $tpl->setVariable( "error_string", $fileName[1] );
                $success = false;
            }
        }
        else
        {
            $tpl->setVariable( "error_string", ezi18n( 'extension/oo/export/error',"Unable to fetch node, or no read access" ) );
            $success = false;
        }

        if ( $success )
        {
            $contentLength = filesize( $fileName );
            if ( $contentLength > 0 )
            {

                // Download the file
                header( "Pragma: " );
                header( "Cache-Control: " );
                /* Set cache time out to 10 minutes, this should be good enough to work around an IE bug */
                header( "Expires: ". gmdate('D, d M Y H:i:s', time() + 600) . 'GMT');
                header( "Content-Length: $contentLength" );
                header( "Content-Type: $contentType" );
                header( "X-Powered-By: eZ publish" );
                header( "Content-disposition: attachment; filename=\"$originalFileName\"" );
                header( "Content-Transfer-Encoding: binary" );
                header( "Accept-Ranges: bytes" );

                $fh = fopen( "$fileName", "rb" );
                if ( $fileOffset )
                {
                    fseek( $fh, $fileOffset );
                }

                ob_end_clean();
                fpassthru( $fh );
                fclose( $fh );
                fflush();

                unlink( $fileName );
                eZExecution::cleanExit();
            }
            else
            {
                $tpl->setVariable( "error_string", ezi18n( 'extension/oo/export/error',"Unable to open file %1 on server side", null, array( $fileName ) ) );
            }
        }
    }
}

$Result = array();
$Result['content'] =& $tpl->fetch( "design:oo/export.tpl" );
$Result['path'] = array( array( 'url' => '/oo/export/',
                                'text' => ezi18n( 'extension/oo', 'OpenOffice.org export' ) ) );


/*!
      Connects to the eZ publish document conversion deamon and converts the document to specified format
*/
function deamonConvert( $server, $port, $sourceFile, $conversionCommand, $destFile )
{
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
            $commandString = "$conversionCommand $sourceFile $destFile";
            fputs( $fp, $commandString, strlen( $commandString ) );

            $result = fread( $fp, 1024 );
            $result = trim( $result );
        }
        fclose( $fp );

        return $result;
    }
    return false;
}

?>
