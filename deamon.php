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
$ooexecutable = "openoffice.org-1.9";
$ooexecutable = "openoffice.org-2.0";
$tmpPath = "/tmp/";

set_time_limit( 0 );

$socket = socket_create( AF_INET, SOCK_STREAM, 0) or die( "Could not create socket\n" );
$result = socket_bind( $socket, $host, $port ) or die( "Could not bind to socket\n" );
$result = socket_listen( $socket, 3 ) or die( "Could not set up socket listener\n" );

print( "Started OpenOffice.org deamon\n" );

function convert_to( $fileName, $convertCommand, $tmpFile )
{
    global $ooexecutable, $spawn, $tmpPath;

    print( "Converting document with $convertCommand\n" );

    $tmpFile = $tmpPath . $tmpFile;

    if( filesize( $tmpFile ) >= disk_free_space("/") )
    {
        socket_write( $spawn, "Error: (3)-Not Enough Disk space." );
        return false;
    }
    unlink( $tmpFile );
    $result = shell_exec( $ooexecutable . " -writer 'macro:///standard.Module1." . $convertCommand . "($fileName)'" );

    if ( !file_exists( $tmpFile ) )
        return false;

    socket_write( $spawn, "FilePath: $tmpFile" );
    return true;
}

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
                    $result = convert_to( $fileName,  "convertToPDF", "ooo_converted.pdf" );

                }break;

                case "convert_to_ooo":
                {
                    $result = convert_to( $fileName, "convertToOOo", "ooo_converted.odt" );

                }break;

                case "convert_to_doc":
                {
                    $result = convert_to( $fileName, "convertToDoc", "ooo_converted.odt"  );

                }break;

                default:
                {
                    echo "unknown command";
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
