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


    include_once ('lib/ezutils/classes/ezfunctionhandler.php');
    include_once ('lib/ezutils/classes/ezsys.php');
    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
    include_once( "lib/ezutils/classes/ezhttptool.php" );

    $tpl = eZTemplate::factory();
    $http = eZHTTPTool::instance();

    if ( $http->hasPostVariable( 'Username' ) );
        $username = $http->postVariable( 'Username' );

    if ( $http->hasPostVariable( 'Password' ) );
        $password = $http->postVariable( 'Password' );

    if ( $http->hasPostVariable( 'NodeID' ) );
        $parentNodeID = $http->postVariable( 'NodeID' );

    // User authentication
    $user = eZUser::loginUser( $username, $password );
    if ( $user == false )
    {
        print( 'problem:Authentication failed' );
        eZExecution::cleanExit();
    }
    else
    {
        // Print the list of ID nodes..
        //Structure : name, type, ID
        $nodes = eZFunctionHandler::execute( 'content','list', array( 'parent_node_id' => $parentNodeID ) );

        $array = array();
        foreach( $nodes as $node )
        {
            $tpl->setVariable( 'node', $node );

            $nodeID = $node->attribute( 'node_id' );
            $name = $node->attribute( 'name' );
            $className = $node->attribute( 'class_name' );
            $object =& $node->object();
            $contentClass = $object->contentClass();
            $isContainer = $contentClass->attribute( 'is_container' );

            preg_match( '/\/+[a-z0-9\-\._]+\/?[a-z0-9_\.\-\?\+\/~=&#;,]*[a-z0-9\/]{1}/si', $tpl->fetch( 'design:ezodf/icon.tpl' ), $matches );
            $iconPath = 'http://'. eZSys::hostname(). ':' . eZSys::serverPort() . $matches[0];
            $array[] = array( $nodeID, $name, $className, $isContainer, $iconPath );
        }

        //Test if not empty
        if ( empty( $array ) )
        {
            print( 'problem:No Items' );
            eZExecution::cleanExit();
        }

        //Convert the array into a string and display it
        $display = '';
        foreach( $array as $line )
        {
            foreach( $line as $element )
            {
                $display .= $element . ';';
            }
            $display .= chr( 13 );
        }

        print( $display );

        // Don't display eZ Publish page structure
        eZExecution::cleanExit();
    }
?>
