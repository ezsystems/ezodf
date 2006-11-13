<?php

    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
    include_once( "extension/ezodf/modules/odf/ezooconverter.php" );
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

    // Don't display eZ publish page structure
    eZExecution::cleanExit( );

?>
