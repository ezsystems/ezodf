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

function makeErrorArray( $num, $msg )
{
    return array( 'number' => $num, 'message' => $msg );
}

$http =& eZHTTPTool::instance();
$module =& $Params["Module"];
$NodeID = $Params['NodeID'];
$ImportType = $Params['ImportType'];

$tpl =& templateInit();

$tpl->setVariable( 'error', false );

$doImport = false;
$replaceObject = false;
// Check if we should create a new document as a child of the selected or
// replace it creating a new version
if ( $http->hasPostVariable( "ImportType" ) )
{
    $type = $http->postVariable( "ImportType" );
    if ( $type == "replace" )
    {
        $replaceObject = true;
    }
}

// Check import type in GET variables
if ( $ImportType == "replace" )
{
    $replaceObject = true;
}

if ( $http->hasPostVariable( "NodeID" ) or is_numeric( $NodeID ) )
{
    if ( is_numeric( $NodeID ) )
        $nodeID = $NodeID;
    else
        $nodeID = $http->postVariable( "NodeID" );

    $doImport = true;
    $node = eZContentObjectTreeNode::fetch( $nodeID );

    if ( $replaceObject == true )
    {
        $tpl->setVariable( 'import_type', "replace" );
        $http->setSessionVariable( 'oo_import_type', 'replace' );
    }
    else
    {
        $http->setSessionVariable( 'oo_import_type', 'import' );
    }

    $tpl->setVariable( 'import_node', $node );
    $http->setSessionVariable( 'oo_direct_import_node', $nodeID );
}

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
            $result = $import->import( $http->sessionVariable( "oo_import_filename" ), $nodeID, $http->sessionVariable( "oo_import_original_filename" ) );
            // Cleanup of uploaded file
            //unlink( $http->sessionVariable( "oo_import_filename" ) );


            if( $result )
            {
                $tpl->setVariable( 'class_identifier', $result['ClassIdentifier'] );
                $tpl->setVariable( 'url_alias', $result['URLAlias'] );
                $tpl->setVariable( 'node_name', $result['NodeName'] );
                $tpl->setVariable( 'oo_mode', 'imported' );
            }
            else
            {
                if( $import->getErrorNumber() != 0 )
                {
                    $tpl->setVariable( 'error', makeErrorArray( $import->getErrorNumber(), $import->getErrorMessage() ) );
                }
                else
                {
                    $tpl->setVariable( 'error', makeErrorArray( OOIMPORT_ERROR_DOCNOTSUPPORTED,
                                                                ezi18n( 'extension/oo/import/error', "Document is not suported" ) ) );
                }

            }
            $http->removeSessionVariable( 'oo_import_step' );
            $http->removeSessionVariable( 'oo_import_filename' );
            $http->removeSessionVariable( 'oo_import_original_filename' );

        }
        else
        {
            eZDebug::writeError( "Cannot import. File not found. Already imported?" );
            $tpl->setVariable( 'error', makeErrorArray( OOIMPORT_ERROR_FILENOTFOUND,
                                                        ezi18n( 'extension/oo/import/error', "Cannot import. File not found. Already imported?" ) ) );
        }
    }
    else
    {
        eZDebug::writeError( "Cannot import document, supplied placement nodeID is not valid." );
        $tpl->setVariable( 'error', makeErrorArray( OOIMPORT_ERROR_PLACEMENTINVALID,
                                                    ezi18n( 'extension/oo/import/error', "Cannot import document, supplied placement nodeID is not valid." ) ) );
    }

//    $tpl->setVariable( 'oo_mode', 'imported' );
}
else
{
    $tpl->setVariable( 'oo_mode', 'browse' );

if( eZHTTPFile::canFetch( "oo_file" ) )
 {
    $file = eZHTTPFile::fetch( "oo_file" );

    if ( $file )
    {
        if ( $file->store() )
        {
            $fileName = $file->attribute( 'filename' );
            $originalFileName = $file->attribute( 'original_filename' );

            // If we have the NodeID do the import/replace directly
            if (  $http->sessionVariable( 'oo_direct_import_node' )  )
            {
                $nodeID = $http->sessionVariable( 'oo_direct_import_node' );
                $importType = $http->sessionVariable( 'oo_import_type' );
                if ( $importType != "replace" )
                    $importType = "import";
                $import = new eZOOImport();
                $result = $import->import( $fileName, $nodeID, $originalFileName, $importType );
                // Cleanup of uploaded file
                //unlink( $fileName );

                if( $result )
                {
                    $tpl->setVariable( 'class_identifier', $result['ClassIdentifier'] );
                    $tpl->setVariable( 'url_alias', $result['URLAlias'] );
                    $tpl->setVariable( 'node_name', $result['NodeName'] );
                    $tpl->setVariable( 'oo_mode', 'imported' );
                }
                else
                {
                    if( $import->getErrorNumber() != 0 )
                    {
                        $tpl->setVariable( 'error', makeErrorArray( $import->getErrorNumber(), $import->getErrorMessage() ) );
                    }
                    else
                    {
                        $tpl->setVariable( 'error', makeErrorArray( OOIMPORT_ERROR_DOCNOTSUPPORTED,
                                                                    ezi18n( 'extension/oo/import/error',"Document is not suported" ) ) );
                    }
                }
                $http->removeSessionVariable( 'oo_direct_import_node' );
            }
            else
            {
                // Make the user browser for document placement
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
        }
        else
        {
            eZDebug::writeError( "Cannot store uploaded file, cannot import" );
            $tpl->setVariable( 'error', makeErrorArray( OOIMPORT_ERROR_CANNOTSTORE,
                                                        ezi18n( 'extension/oo/import/error',"Cannot store uploaded file, cannot import" ) ) );
        }
    }
 }

}



$Result = array();
$Result['content'] =& $tpl->fetch( "design:oo/import.tpl" );
$Result['path'] = array( array( 'url' => '/oo/import/',
                                'text' => ezi18n( 'extension/oo', 'OpenOffice.org import' ) ));



?>
