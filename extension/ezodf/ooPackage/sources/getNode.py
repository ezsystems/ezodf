import unohelper
from com.sun.star.lang import XServiceName
from com.sun.star.lang import XMain
import string
import urllib

g_ImplementationHelper = unohelper.ImplementationHelper()

class getNode( unohelper.Base, XServiceName, XMain ):
    def __init__( self, ctx ):
        self.output = "No output"
      
    def getServiceName( self ):
        return self.output
        
    def run( self, args ):
        url = args[0]
        username = args[1]
        password = args[2]
        nodeID = args[3]
        
        if url.find( 'http://' ) == -1:
            url = 'http://' + url
        if url[len( url ) - 1] != '/':
            url = url + '/'
        try:
            params = urllib.urlencode( { "Username": username, "Password": password, "NodeID": nodeID } )
            f = urllib.urlopen( url + "odf/authenticate", params )
            output = f.read( ).strip( )
            if output.find( '<!DOCTYPE' ) == 0:
                self.output = 'problem:Server unreachable'
            elif output.find( '<html>' ) == 0:
                self.output = 'problem:Server unreachable'
            elif output.find( '<br />' ) == 0:
                self.output = 'problem:Invalid node ID'
            else:
                self.output = output
        except:
            self.output = 'problem:Server unreachable'
        
        return 1

g_ImplementationHelper.addImplementation( \
    getNode, "org.openoffice.pyuno.eZsystems.getNode",
    ( "com.sun.star.lang.XServiceName", "com.sun.star.lang.XMain" ), )
