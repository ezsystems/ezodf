<?php

    include_once( "kernel/common/template.php" );
    include_once( "extension/oo/modules/oo/ezooimport.php" );
    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );

    $username = $_POST['username'];
    $password = $_POST['password'];
    $nodeID = $_POST['nodeID'];
    $importType = $_POST['ImportType'];

    // User authentication
    $userClass = eZUser::currentUser();
    $user = $userClass->loginUser( $username, $password );
    if ( $user == false )
    {
        print( 'problem:Authentication failed' );
        eZExecution::cleanExit();
    }
     
    // Verification : file uploaded ?
    if( !is_uploaded_file( $_FILES['file']['tmp_name'] ) )
    {
        print( 'problem:No file uploaded' );
        eZExecution::cleanExit();
    }
    
    $fileName = $_FILES['file']['tmp_name'];
    
    $content = base64_decode( file_get_contents( $fileName ) );
    
    $fd = fopen( $fileName, 'w' );
    fwrite( $fd, $content );
    fclose( $fd );
    
    $originalFilename = $_FILES['file']['name'];
    
    // Conversion of the stored file
    $import = new eZOOImport();
    $tmpResult = $import->import( $fileName, $nodeID, $originalFilename, $importType );

    // Verification : conversion OK ?
    $error = $import->getErrorNumber( );
    if ( $error != 0 )
    {
        print( 'problem:Import : '.$import->getErrorMessage( ) );
        eZExecution::cleanExit( );
    }

    // Store the article in the eZ publish tree diagram
    $result['contentobject'] = $tmpResult['Object'];
    $result['contentobject_main_node'] = $tmpResult['MainNode'];

    // Delete the temporary file
    // No verification, file is published
    unlink( $tmpFile );

    // End : print return string
    print( 'done:File successfully exported with nodeID ' . $tmpResult['MainNode']->attribute('node_id') );

    // Don't display eZ publish page structure
    eZExecution::cleanExit();

?>
