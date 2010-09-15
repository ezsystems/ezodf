<?php
//
// Created on: <07-Jul-2005 10:14:34 bf>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.1.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2010 eZ Systems AS
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

/*

Needs the following in PHP Configuration

  --enable-cli
  --enable-pcntl
  --enable-sockets

*/

$homeDir = "/lhome/odfdaemon";
$displayNum = ':1';
#$ooexecutable = "openoffice.org-2.0";
$ooexecutable = "/usr/bin/ooffice";

set_time_limit( 0 );

function convert_to( $sourceFileName, $convertCommand, $destinationFileName )
{
    global $ooexecutable;
    global $homeDir;
    global $displayNum;

    switch ( $convertCommand )
    {
        case "convertToPDF":
        case "convertToOOo":
        case "convertToDoc":
        {
            $convertShellCommand = "export HOME=\"$homeDir\"\n" . escapeshellcmd( $ooexecutable . " -writer -invisible -display " . $displayNum ) . " " . 
            escapeshellarg( "macro:///eZconversion.Module1.$convertCommand(\"$sourceFileName\", \"$destinationFileName\")" ) . " 2>&1 ";

            $result = shell_exec( $convertShellCommand );
        }break;

        default:
        {
            return "(1)-Unknown command $convertCommand";
        }break;
    }

    if ( !file_exists( $destinationFileName ) )
    {
        return "(3) - Unknown failure converting document \n command was: '$convertShellCommand' \nresult: $result";
    }

    return true;
}


print( "eZ Publish document conversion daemon\n");
// Parse input
$input = fread( STDIN, 1024 );

$inputParts = explode( " ", $input );

$command = trim( $inputParts[0] );
$fileName = trim( $inputParts[1] );
$destName = trim( $inputParts[2] );


if ( file_exists( $fileName ) )
{
    sleep(10);
    $result = convert_to( $fileName, $command, $destName );
    if ( !( $result === true ) )
    {
        print( "Error: $result" );
    }
    else
    {
        echo( "FilePath: $destName" );
    }
}
else
{
    echo( "Error: (2)-File not found" );
}



?>
