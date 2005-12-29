[OOo]
# Path to zip program on windows you can use: 
# http://www.info-zip.org/pub/infozip/
# enter for example c:\zip\ or /usr/local/bin/
ZipPath=
# Directory for storing temporary files during conversion
TmpDir=/tmp

[OOImport]
DefaultImportClass=article
DefaultImportTitleAttribute=title
DefaultImportBodyAttribute=body
DefaultImportImageClass=image
RegisteredClassArray[]=article
RegisteredClassArray[]=folder
ImportedImagesMediaNodeName=Imported images
PlaceImagesInMedia=false
OOConverterPort=9090
# Currently only localhost (127.0.0.1) is allowed
OOConverterAddress=127.0.0.1


[OOExport]
UseTemplate=true
TemplateName=ezpublish.ott

# Map eZ publish attributes to OpenOffice.org section definitions
[article]
Attribute[title]=title
Attribute[intro]=intro
Attribute[body]=body

[folder]
Attribute[title]=title
Attribute[shortdescription]=shortdescription

# DocumentType tells which are the supported document type by oo
[DocumentType]
# AllowedTypes are those documents which are supported and published directely and need not to convert for support.
AllowedTypes[]
AllowedTypes[]=odt
# ConvertTypes are those documents which can be converted by oo and then it will publish it automatically.
ConvertTypes[]
ConvertTypes[]=doc

