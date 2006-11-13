import unohelper
from com.sun.star.lang import XServiceName
from com.sun.star.lang import XMain
import string
import urllib
import base64

g_ImplementationHelper = unohelper.ImplementationHelper()

class receive( unohelper.Base, XServiceName, XMain ):
    def __init__( self, ctx ):
        self.output = "No output"
      
    def getServiceName( self ):
        return self.output
        
    def run( self, args ):
        url = args[0]
        username = args[1]
        password = args[2]
        nodeID = args[3]
        filename = args[4]

        # format the URL
        if url.find( 'http://' ) == -1:
            url = 'http://' + url
        if url[len( url ) - 1] != '/':
            url = url + '/'
        url = url + 'odf/upload_export'

        # send the request to the server
        try:
            params = urllib.urlencode( { "Username": username, "Password": password, "NodeID": nodeID } )
            f = urllib.urlopen( url, params )
            output = f.read()
            if output.find( 'problem:' ) == 0:
                self.output = output
            output = base64.standard_b64decode( output )
            try:
                fobject = file( filename, 'w' )
                fobject.write( output )
                fobject.close( )
            except:
                self.output = 'problem:Couldn\'t write to local temporary file'
                self.output = filename
        except:
            outputList = []
            outputList.append( 'problem:Connection to the server impossible' )
            outputList.append( 'Possible causes are :' )
            outputList.append( '- bad server URL' )
            outputList.append( '- no connection to the server' )
            output = ""
            self.output = output.join( outputList )
        self.output = 'Done'
        return 1

g_ImplementationHelper.addImplementation( \
    receive, "org.openoffice.pyuno.eZsystems.receive",
    ( "com.sun.star.lang.XServiceName", "com.sun.star.lang.XMain" ), )
