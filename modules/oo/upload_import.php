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

    $node = eZContentObjectTreeNode::fetch( $nodeID );

    // Verification : file uploaded ?
    if( !is_uploaded_file( $_FILES['file']['tmp_name'] ) )
    {
        print( 'problem:No file uploaded' );
        eZExecution::cleanExit();
    }

    $originalFilename =  basename( $_FILES['file']['name'] );
    $tmpFile = '/tmp/'.rand(10000,99999).$originalFilename;

    // Storing the incoming file + verification
    if( !move_uploaded_file( $_FILES['file']['tmp_name'], $tmpFile) )
    {
        print( 'problem:Problem while storing temporary file' );
        eZExecution::cleanExit();
    }

    // Conversion of the stored file
    $import = new eZOOImport();
    $tmpResult = $import->import( $tmpFile, $nodeID, $originalFilename, $importType );

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
    print( 'done:File successfully exported with nodeID ...' );

    // Don't display eZ publish page structure
    eZExecution::cleanExit();

?>
