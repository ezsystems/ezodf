eZ publish OpenOffice.org extension
===================================
This extension enables import and export of OpenOffice.org Writer documents within 
eZ publish. It comes with a general OpenOffice.org Writer document generation library 
as well, if you have custom modules that needs to generate OpenOffice.org Writer 
documents.

Requirements
------------
To get this extension up and running you need to have eZ publish 3.5. or later 
installed.

You also need either to have the zlib extension enabled in your PHP version or
you need to have the command line versions of 'zip' and 'unzip' available on your
operating system. This is needed to create and unpack the zip files which makes up
the OpenOffice.org documents.

Installation
------------
Unpack the tar.gz archive and place the oo folder under the extension folder in 
eZ publish, normally "extension". Then go to the setup tab in eZ publish and enable 
the oo extension under the Setup->Extensions menu.

Usage
-----
In your eZ publish installation you need to reach the import and export functions. 
These are available via the url's

* /oo/import
* /oo/export

These URL's are relative to your eZ publish installation.

When importing objects you can do it in two ways general and custom:

General import
..............
This import is the default which imports any OpenOffice.org writer document as an 
object by the default eZ publish content class. This class is defined in oo.ini.

Custom import
.............
Since you have the possibility of defining several different types of content in 
eZ publish. You can define sections in OpenOffice.org documents which can map to 
eZ publish attributes. E.g. you can define a section for title, introduction and body 
and this can be imported automatically to an article while another document is 
imported as a product. This is configured in oo.ini. See the examples for sample 
documents.

Export
......
During the export of content you have the possibility to specify an OpenOffice.org 
Writer template file to style your document. This template file is simply placed in 
the templates/ subdirectory of the OpenOffice.org extension. Then you set the setting
UseTemplate=true in oo.ini and put the name of the document in the setting 
TemplateName=mytemplate.stw.

Examples
--------
There are some documents under examples/ in the oo extension which you can use to 
test the import to eZ publish. 

Supported features
------------------
The integration between OpenOffice.org has some limitations. There is a difference
in the way OpenOffice.org and eZ publish handles content in terms of presentation.

The following is a summary of the supported features during import and export.

Import features
...............
* Normal paragraph text
* Bold and italic text span
* Multiple levels of headers


Export features
...............

* Normal paragraph text
* Multiple levels of headers
* Images with sizes and alignment
