<?php
//
// Created on: <17-Aug-2004 12:58:56 bf>
//
// Copyright (C) 1999-2005 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/products/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

include_once( "kernel/common/template.php" );
include_once( 'lib/ezxml/classes/ezxml.php' );
include_once( 'lib/ezutils/classes/ezhttpfile.php' );

include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );

include_once( "kernel/classes/ezcontentbrowse.php" );

include_once( "extension/oo/modules/oo/ezooimport.php" );

$http =& eZHTTPTool::instance();
$module =& $Params["Module"];

$tpl =& templateInit();

$sourceFile = "documents/test1.sxw";

if ( $module->isCurrentAction( 'OOPlace' ) )
{
    // We have the file and the placement. Do the actual import.
    $selectedNodeIDArray = eZContentBrowse::result( 'OOPlace' );

    $nodeID = $selectedNodeIDArray[0];

    if ( is_numeric( $nodeID ) )
    {
        $fileName = $http->sessionVariable( "oo_import_filename" );
        if ( file_exists( $fileName ) )
        {
            $import = new eZOOImport();
            $result = $import->import( $http->sessionVariable( "oo_import_filename" ), $nodeID );
            $tpl->setVariable( 'class_identifier', $result['ClassIdentifier'] );
            $tpl->setVariable( 'url_alias', $result['URLAlias'] );

            /* ADD removal
              $http->removeSessionVariable( 'oo_import_step' );
              $http->removeSessionVariable( 'oo_import_filename' );
              $http->removeSessionVariable( 'oo_import_original_filename' );
            */
        }
        else
        {
            eZDebug::writeError( "Cannot import. File not found. Already imported?" );
        }
    }
    else
    {
        eZDebug::writeError( "Cannot import document, supplied placement nodeID is not valid." );
    }

    $tpl->setVariable( 'oo_mode', 'imported' );
}
else
{
    $file = eZHTTPFile::fetch( "oo_file" );

    if ( $file )
    {
        if ( $file->store() )
        {
            $fileName = $file->attribute( 'filename' );
            $originalFileName = $file->attribute( 'original_filename' );

            $http->setSessionVariable( 'oo_import_step', 'browse' );
            $http->setSessionVariable( 'oo_import_filename', $fileName );
            $http->setSessionVariable( 'oo_import_original_filename', $originalFileName );

            eZContentBrowse::browse( array( 'action_name' => 'OOPlace',
                                            'description_template' => 'design:oo/browse_place.tpl',
                                            'content' => array(),
                                            'from_page' => '/oo/import/',
                                            'cancel_page' => '/oo/import/' ),
                                     $module );
            return;
        }
        else
        {
            eZDebug::writeError( "Cannot store uploaded file, cannot import" );
        }
    }

    $tpl->setVariable( 'oo_mode', 'browse' );
}

$Result = array();
$Result['content'] =& $tpl->fetch( "design:oo/import.tpl" );
$Result['path'] = array( array( 'url' => '/oo/import/',
                                'text' => 'OpenOffice.org import'  ));



?>
