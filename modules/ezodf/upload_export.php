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

    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
    include_once( "extension/ezodf/modules/ezodf/ezooconverter.php" );
    include_once( "lib/ezutils/classes/ezhttptool.php" );

    $http = eZHTTPTool::instance();

    if ( $http->hasPostVariable( 'Username' ) );
        $username = $http->postVariable( 'Username' );

    if ( $http->hasPostVariable( 'Password' ) );
        $password = $http->postVariable( 'Password' );

    if ( $http->hasPostVariable( 'NodeID' ) );
        $nodeID = $http->postVariable( 'NodeID' );

    // User authentication
    $user = eZUser::loginUser( $username, $password );
    if ( $user == false )
    {
        print( 'problem:Authentication failed' );
        eZExecution::cleanExit();
    }

    // Conversion of the stored file
    $converter = new eZOOConverter( );
    $ooDocument = $converter->objectToOO( $nodeID );

    if ( $ooDocument == false )
    {
        print( 'problem:Conversion failed' );
        eZExecution::cleanExit( );
    }

    $file = file_get_contents( $ooDocument );
    $file = base64_encode( $file );
    print( $file );

    // Don't display eZ Publish page structure
    eZExecution::cleanExit( );

?>
