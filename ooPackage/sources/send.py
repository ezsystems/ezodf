import unohelper
from com.sun.star.lang import XServiceName
from com.sun.star.lang import XMain
import string
import httplib
import base64

g_ImplementationHelper = unohelper.ImplementationHelper()

class send( unohelper.Base, XServiceName, XMain ):
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
        importType = args[5]
    
        # remove the 'http://' substring
        url = url.replace( 'http://', '' )
        # keep only the server name
        url = url.split( '/' )[0]

        page = '/odf/upload_import'

        # try to read the content of the file
        try:
            content = open( filename, "r" ).read( )
        except:
            self.output = 'problem:Temporary file not found'
            return 0
            
        try:
            content = base64.standard_b64encode( content )
        except:
            self.output = 'problem:Conversion to Base64 impossible'
            return 0

        # create the body of the HTTP message
        boundary = 'O_o__BOUNDARY__o_O'
        CRLF = '\r\n'
        bodyList = []
        bodyList.append( '--' + boundary )
        bodyList.append( 'Content-Disposition: form-data; name="Username"'  )
        bodyList.append( '' )
        bodyList.append( str( username ) )
        bodyList.append( '--' + boundary )
        bodyList.append( 'Content-Disposition: form-data; name="Password"'  )
        bodyList.append( '' )
        bodyList.append( str( password ) )
        bodyList.append( '--' + boundary )
        bodyList.append( 'Content-Disposition: form-data; name="NodeID"'  )
        bodyList.append( '' )
        bodyList.append( str( nodeID ) )
        bodyList.append( '--' + boundary )
        bodyList.append( 'Content-Disposition: form-data; name="ImportType"'  )
        bodyList.append( '' )
        bodyList.append( str( importType ) )
        bodyList.append( '--' + boundary )
        bodyList.append( 'Content-Disposition: form-data; name="File"; filename="%s"' % filename )
        bodyList.append( 'Content-Type: application/octet-stream' )
        bodyList.append( 'Content-Transfer-Encoding: binary' )
        bodyList.append( '' )
        bodyList.append( str( content ) )
        bodyList.append( '--' + boundary + '--' )
        bodyList.append( '' )
        body = CRLF.join( bodyList )

        # create the headers of the HTTP message
        headers = {
            'User-Agent': 'INSERT USERAGENTNAME',
            'Content-Type': 'multipart/form-data; boundary=' + boundary
        }

        # try to connect to the url and send the message
        try:
            h = httplib.HTTPConnection( url)
            h.request('POST', page, body, headers)
            res = h.getresponse( )
            self.output = res.read( )
            return 1
        except:
            output = ""
            outputList = []
            outputList.append( 'problem:Connection to the server impossible\n' )
            outputList.append( 'Possible causes are :\n' )
            outputList.append( '- bad server URL\n' )
            outputList.append( '- no connection to the server\n' )
            self.output = output.join( outputList )
            return 0

g_ImplementationHelper.addImplementation( \
    send, "org.openoffice.pyuno.eZsystems.send",
    ( "com.sun.star.lang.XServiceName", "com.sun.star.lang.XMain" ), )
