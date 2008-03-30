*About:
This readme file describes how to add the OO menu to the OO package.

* Adding the project to eclipse.
1. Import the project by selecting File->Import.
2. Select "Existing Project into Workspace"
3. Select the eZODFMenu project from the checked out location.
4. Exit eclipse
5. Go to the root of the default workspace of Eclipse.
6. Untar the file workspace_settings.tar.gz at the location.
7. Start eclipse again
8. Enter Window->Preference, and re-select OpenOffice SDK and location ( if this is not done, package creation will generate a null-pointer exception )


* Creating a package:
1. Select File->Export from Eclipse.
2. Export UNO package.
3. Remember package destination.
4. Add the files in the OOMenu to the root of the package created ( The package is a zip file )
5. Add the file OOMenu/manifest.xml to the MANIFEST path in the extracted package ( overwrite the existing file )
6. Replace the manifest.xml file with the one located in OOMenu
7. The package is ready for distribution.