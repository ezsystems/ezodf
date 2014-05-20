<?php
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

    include_once( "extension/ezodf/modules/ezodf/ezooimport.php" );
    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
    include_once( "lib/ezutils/classes/ezhttptool.php" );
    include_once( "lib/ezutils/classes/ezhttpfile.php" );

    $http = eZHTTPTool::instance();

    if ( $http->hasPostVariable( 'Username' ) );
        $username = $http->postVariable( 'Username' );

    if ( $http->hasPostVariable( 'Password' ) );
        $password = $http->postVariable( 'Password' );

    if ( $http->hasPostVariable( 'NodeID' ) );
        $nodeID = $http->postVariable( 'NodeID' );

    if ( $http->hasPostVariable( 'ImportType' ) );
        $importType = $http->postVariable( 'ImportType' );

    // User authentication
    $user = eZUser::loginUser( $username, $password );
    if ( $user == false )
    {
        print( 'problem:Authentication failed' );
        eZExecution::cleanExit();
    }

    if ( !eZHTTPFile::canFetch( 'File' ) )
    {
        print( 'problem:Can\'t fetch HTTP file.' );
        eZExecution::cleanExit();
    }

    $file = eZHTTPFile::fetch('File');

    $fileName = $file->attribute( 'filename' );
    $originalFilename = $file->attribute('original_filename');

    $content = base64_decode( file_get_contents( $fileName ) );

    $fd = fopen( $fileName, 'w' );
    fwrite( $fd, $content );
    fclose( $fd );

    // Conversion of the stored file
    $import = new eZOOImport();
    $importResult = $import->import( $fileName, $nodeID, $originalFilename, $importType );

    // Verification : conversion OK ?
    if ( $import->getErrorNumber( ) != 0 )
    {
        print( 'problem:Import : ' . $import->getErrorMessage( ) );
        eZExecution::cleanExit();
    }

    // End : print return string
    print( 'done:File successfully exported with nodeID ' . $importResult['MainNode']->attribute('node_id') );

    // Don't display eZ Publish page structure
    eZExecution::cleanExit();

?>
