<?php
//
//
// Created on: <07-Jul-2005 10:14:34 bf>
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

/*

Needs the following in PHP Configuration

  --enable-cli
  --enable-pcntl
  --enable-sockets

*/

$host = "127.0.0.1";
$port = 9090;

set_time_limit( 0 );

$socket = socket_create( AF_INET, SOCK_STREAM, 0) or die( "Could not create socket\n" );
$result = socket_bind( $socket, $host, $port ) or die( "Could not bind to socket\n" );
$result = socket_listen( $socket, 3 ) or die( "Could not set up socket listener\n" );

while ( $spawn = socket_accept( $socket ))
{
    print( "got new connection\n" );
    $pid = pcntl_fork();
    if ( $pid != 0)
    {
        // In the main process
        socket_close( $spawn );
    }
    else
    {
        // We are in the forked child process
        socket_write( $spawn, "eZ publish document conversion deamon\n");

        // Parse input
        $input = socket_read( $spawn, 1024 );

        $inputParts = explode( " ", $input );

        $command = strtolower( trim( $inputParts[0] ) );
        $fileName = trim( $inputParts[1] );


        if ( file_exists( $fileName ) )
        {
            switch ( $command )
            {
                case "convert_to_pdf":
                {
                    print( "Converting document to PDF\n" );

                    unlink( "/tmp/ooo_converted.pdf" );
                    $result = `openoffice.org-1.9 -writer "macro:///standard.Module1.convertToPDF($fileName)"`;

                    socket_write( $spawn, "FilePath: /tmp/ooo_converted.pdf" );

                }break;

                case "convert_to_ooo":
                {
                    print( "Converting document to OpenOffice.org\n" );
                    unlink( "/tmp/ooo_converted.odt" );
                    $result = `openoffice.org-1.9 -writer "macro:///standard.Module1.convertToOOo($fileName)"`;

                    socket_write( $spawn, "FilePath: /tmp/ooo_converted.odt" );

                }break;

                case "convert_to_doc":
                {
                    unlink( "/tmp/ooo_converted.doc" );
                    $result = `openoffice.org-1.9 -writer "macro:///standard.Module1.convertToDoc($fileName)"`;

                    socket_write( $spawn, "FilePath: /tmp/ooo_converted.doc" );

                }break;

                default:
                {
                    socket_write( $spawn, "Error: (1)-Unknown command" );
                }break;
            }
        }
        else
        {
            socket_write( $spawn, "Error: (2)-File not found" );
        }

        socket_close( $spawn );
        die('Data Recieved on child ' . posix_getpid(). "\n");
    }
}

socket_close( $socket );

?>
