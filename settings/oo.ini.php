[OOo]
# Path to zip program on windows you can use: 
# http://www.info-zip.org/pub/infozip/
# enter for example c:\zip\ or /usr/local/bin/
ZipPath=


[OOImport]
DefaultImportClass=article
DefaultImportTitleAttribute=title
DefaultImportBodyAttribute=body
RegisteredClassArray[]=article
RegisteredClassArray[]=folder
ImportedImagesMediaNodeName=Imported images
PlaceImagesInMedia=true


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

