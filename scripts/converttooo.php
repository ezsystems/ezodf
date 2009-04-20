<?php
//
//
// Created on: <07-Jul-2005 10:14:34 bf>
//
// Copyright (C) 1999-2005 eZ systems as. All rights reserved.
//
// This source file is part of the eZ Publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ Publish professional licence" version 2
// may use this file in accordance with the "eZ Publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ Publish professional licence" version 2 is available at
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
            $envVarStr ="export HOME=\"$homeDir\"\n";
            $convertShellCommand = $envVarStr . $ooexecutable . " -writer -invisible -display " . $displayNum . " 'macro:///eZconversion.Module1." . $convertCommand . "(\"$sourceFileName\", \"$destinationFileName\")'" . "  2>&1 ";
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
