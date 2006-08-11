<?php

    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
    include_once( "extension/oo/modules/oo/ezooconverter.php" );

    $username = $_POST['username'];
    $password = $_POST['password'];
    $nodeID = $_POST['nodeID'];

    // User authentication
    $userClass = eZUser::currentUser( );
    $user = $userClass->loginUser( $username, $password );
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

    // Don't display eZ publish page structure
    eZExecution::cleanExit( );

?>
