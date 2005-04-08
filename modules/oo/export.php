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

$tpl =& templateInit();

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

if ( $module->isCurrentAction( 'OOPlace' ) )
{
    // We have the file and the placement. Do the actual import.
    $selectedNodeIDArray = eZContentBrowse::result( 'OOPlace' );

    $nodeID = $selectedNodeIDArray[0];

    if ( is_numeric( $nodeID ) )
    {
        // Do the actual eZ publish export
        $fileName = eZOOConverter::objectToOO( $nodeID );

        if ( !is_array( $fileName ) )
        {
            $contentLength = filesize( $fileName );
            $originalFileName = "test.sxw";

            // Download the file
            header( "Pragma: " );
            header( "Cache-Control: " );
            /* Set cache time out to 10 minutes, this should be good enough to work around an IE bug */
            header( "Expires: ". gmdate('D, d M Y H:i:s', time() + 600) . 'GMT');
            header( "Content-Length: $contentLength" );
            header( "Content-Type: application/vnd.sun.xml.writer" );
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
            $tpl->setVariable( "error_string", $fileName[1] );
        }
    }
}

$Result = array();
$Result['content'] =& $tpl->fetch( "design:oo/export.tpl" );
$Result['path'] = array( array( 'url' => '/oo/export/',
                                'text' => 'OpenOffice.org export'  ));

?>
