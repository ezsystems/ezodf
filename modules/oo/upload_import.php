<?php

    include_once( "extension/oo/modules/oo/ezooimport.php" );
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

    // Don't display eZ publish page structure
    eZExecution::cleanExit();

?>
