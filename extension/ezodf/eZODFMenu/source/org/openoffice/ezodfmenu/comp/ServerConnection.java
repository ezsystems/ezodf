/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.io.InputStream;
import java.io.Serializable;
import java.util.HashMap;
import java.util.Vector;

import javax.swing.JOptionPane;
import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathFactory;

import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

import sun.misc.BASE64Encoder; 

import com.sun.org.apache.xerces.internal.parsers.DOMParser;



/**
 * @author hovik
 *
 */
public class ServerConnection implements Serializable {

	private static final long serialVersionUID = 2854107146010800577L;
	private static final String LoginPath = "ezrest/login";
	private static final String TopNodeListPath = "ezrest/ezodfGetTopNodeList";
	private static final String GetChildrenPath = "ezrest/ezodfGetChildren";
	private static final String GetMenuChildrenPath = "ezrest/ezodfGetMenuChildren";
	private static final String GetNodeInfoPath = "ezrest/ezodfGetNodeInfo";
	private static final String FetchOONodePath = "ezrest/ezodfFetchOONode";
	private static final String PutOONodePath = "ezrest/ezodfPutOONode";
	private static final String ReplaceOONodePath = "ezrest/ezodfReplaceOONode";
	
	protected ServerInfo serverInfo;
	protected String sessionID;
	
	/**
	 * Constructor
	 * 
	 * @param Server info
	 */
	public ServerConnection( ServerInfo info )
	{
		this.serverInfo = info;
	}
	
	/**
	 * Connect to server
	 * 
	 * @return True if connection is created. The connection properties is stored in this object.
	 */
	public boolean connect()
	{		
		// Log in.
		return this.login();
	}

	/**
	 * Login selected connection.
	 * 
	 * @return True if successfully logged in.
	 */
	protected boolean login()
	{
		HashMap<String,String> getParameters = new HashMap<String,String>();
		getParameters.put( "login", serverInfo.getUsername() );
		getParameters.put( "password", serverInfo.getPassword() );
		
		try
		{
			InputStream in = MenuLib.sendHTTPGetRequest( getLoginURL(), getParameters);
			DOMParser parser = new DOMParser();
			InputSource source = new InputSource(in);
			parser.parse(source);
			in.close();
			
			Document domDoc = parser.getDocument();
		    Node sessionIDNode = domDoc.getElementsByTagName( "SessionID" ).item( 0 );
		    
		    XPath xpath = XPathFactory.newInstance().newXPath();
			String expression = "text()";
			setSessionID( (String)xpath.evaluate(expression, sessionIDNode, XPathConstants.STRING ) );		    
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Failed logging in: " + getLoginURL() + ": " +  e.getMessage(),
				    "Login",
				    JOptionPane.WARNING_MESSAGE);
			return false;
		}
		
		return true;
	}
	
	/**
	 * 
	 */
	
	/**
	 * Get eZ Publish top node. The top node also contains top node list.
	 */
	public eZPTreeNode getTopNode()
	{
		return new eZPTopTreeNode( this, "Root" );
	}

	public Vector<eZPTreeNode> getTopNodeList()
	{
		Vector<eZPTreeNode> result = new Vector<eZPTreeNode>();
		
		HashMap<String,String> getParameters = new HashMap<String,String>();

		try
		{
			// Send request
			InputStream in = MenuLib.sendHTTPGetRequest( getTopNodeListURL(), getParameters, this.sessionID );
			DOMParser parser = new DOMParser();
			InputSource source = new InputSource(in);
			parser.parse(source);
			in.close();
			
			// Parse result and create eZPTreeNode objects.
			Document domDoc = parser.getDocument();
		    NodeList nodeList = domDoc.getElementsByTagName( "Node" );
		    for( int idx = 0; idx < nodeList.getLength(); idx++ )
		    {
		    	result.add( new eZPTreeNode( this, nodeList.item( idx ) ) );
		    }
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Failed to get top node list: " + getChildrenURL() + ": " +  e.getMessage(),
				    "ServerConnection.getTopNodeList",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		return result;
	}
	
	/**
	 * Get OO document data.
	 * 
	 * @param eZPTreeNode
	 * 
	 * @return OO Data Node.
	 */
	public Node getOODocument( eZPTreeNode treeNode )
	{
		HashMap<String,String> getParameters = new HashMap<String,String>();
		getParameters.put( "nodeID", Integer.toString( treeNode.getNodeID() ) );

		try
		{
			// Send request
			InputStream in = MenuLib.sendHTTPGetRequest( getOODocumentURL(), getParameters, this.sessionID );
			DOMParser parser = new DOMParser();
			InputSource source = new InputSource(in);
			parser.parse(source);
			in.close();
			
			// Parse result and create eZPTreeNode objects.
			Document domDoc = parser.getDocument();
		    return domDoc.getElementsByTagName( "OODocument" ).item( 0 );
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Failed to get top node list: " + getChildrenURL() + ": " +  e.getMessage(),
				    "ServerConnection.getOODocument",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		return null;
	}

	/**
	 * Get children of specified parent node. 
	 * 
	 * @param Parent node
	 * @param offset
	 * @param limit
	 * @return Vector of eZPTreeNodes.
	 */
	public Vector<eZPTreeNode> getChildren( eZPTreeNode parentNode, int offset, int limit )
	{
		Vector<eZPTreeNode> result = new Vector<eZPTreeNode>();
		
		HashMap<String,String> getParameters = new HashMap<String,String>();
		getParameters.put( "nodeID", Integer.toString( parentNode.getNodeID() ) );
		getParameters.put( "offset", Integer.toString( offset ) );
		getParameters.put( "limit", Integer.toString( limit ) );
		
		try
		{
			// Send request	
			InputStream in = MenuLib.sendHTTPGetRequest( getChildrenURL(), getParameters, this.sessionID );
			DOMParser parser = new DOMParser();
			InputSource source = new InputSource(in);
			parser.parse(source);
			in.close();
			
			// Parse result and create eZPTreeNode objects.
			Document domDoc = parser.getDocument();
		    NodeList nodeList = domDoc.getElementsByTagName( "Node" );
		    for( int idx = 0; idx < nodeList.getLength(); idx++ )
		    {
		    	result.add( new eZPTreeNode( this, nodeList.item( idx ) ) );
		    }
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Failed to get children: " + getChildrenURL() + ": " +  e.getMessage(),
				    "MenuLib.getChildren",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		return result;
	}

	/**
	 * Get menu children of specified parent node. 
	 * 
	 * @param Parent node
	 * @param offset
	 * @param limit
	 * @return Vector of eZPTreeNodes.
	 */
	public Vector<eZPTreeNode> getMenuChildren( eZPTreeNode parentNode, int offset, int limit )
	{
		Vector<eZPTreeNode> result = new Vector<eZPTreeNode>();
		
		HashMap<String,String> getParameters = new HashMap<String,String>();
		getParameters.put( "nodeID", Integer.toString( parentNode.getNodeID() ) );
		getParameters.put( "offset", Integer.toString( offset ) );
		getParameters.put( "limit", Integer.toString( limit ) );
		
		try
		{
			// Send request	
			InputStream in = MenuLib.sendHTTPGetRequest( getMenuChildrenURL(), getParameters, this.sessionID );
			DOMParser parser = new DOMParser();
			InputSource source = new InputSource(in);
			parser.parse(source);
			in.close();
			
			// Parse result and create eZPTreeNode objects.
			Document domDoc = parser.getDocument();
		    NodeList nodeList = domDoc.getElementsByTagName( "Node" );
		    for( int idx = 0; idx < nodeList.getLength(); idx++ )
		    {
		    	result.add( new eZPTreeNode( this, nodeList.item( idx ) ) );
		    }
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Failed to get children: " + getChildrenURL() + ": " +  e.getMessage(),
				    "MenuLib.getMenuChildren",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		return result;
	}

	/**
	 * Replace OO document data.
	 * 
	 * @param eZPTreeNode
	 * @param Data
	 */
	public void replaceOODocument( eZPTreeNode treeNode, byte[] data )
	{
		BASE64Encoder encoder = new BASE64Encoder();

		HashMap<String,String> postParameters = new HashMap<String,String>();
		postParameters.put( "nodeID", Integer.toString( treeNode.getNodeID() ) );
		postParameters.put( "filename", treeNode.getOODocumentFilename() );
		postParameters.put( "data", encoder.encodeBuffer( data ) );
		postParameters.put( "base64Encoded", "1" );

		String str = "";
		try
		{
			// Send request
			InputStream in = MenuLib.sendHTTPPostRequest( getReplaceOODocumentURL(), postParameters, this.sessionID );			
			
			DOMParser parser = new DOMParser();
			InputSource source = new InputSource(in);
			parser.parse(source);
			in.close();			
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Failed to replace OO Document: " + getReplaceOODocumentURL() + ": " +  e.getMessage() + ", " + str,
				    "ServerConnection.replaceOODocument",
				    JOptionPane.WARNING_MESSAGE);
		}
	}

	/**
	 * Get login URL
	 * 
	 * @return Login URL
	 */
	protected String getLoginURL()
	{
		return serverInfo.getUrl() + "/" + ServerConnection.LoginPath; 	
	}

	protected String getTopNodeListURL()
	{
		return serverInfo.getUrl() + "/" + ServerConnection.TopNodeListPath;
	}
	/**
	 * Get children URL
	 * 
	 * @return URL to get children REST service
	 */
	protected String getChildrenURL()
	{
		return serverInfo.getUrl() + "/" + ServerConnection.GetChildrenPath;
	}
	
	/**
	 * Get OO Document URL
	 * 
	 * @return URL to getOODocument
	 */
	protected String getOODocumentURL()
	{
		return serverInfo.getUrl() + "/" + ServerConnection.FetchOONodePath;
	}

	/**
	 * Get OO Document URL
	 * 
	 * @return URL to getOODocument
	 */
	protected String getReplaceOODocumentURL()
	{
		return serverInfo.getUrl() + "/" + ServerConnection.ReplaceOONodePath;
	}

	/**
	 * Get menu children URL
	 * 
	 * @return URL to get children REST service
	 */
	protected String getMenuChildrenURL()
	{
		return serverInfo.getUrl() + "/" + ServerConnection.GetMenuChildrenPath;
	}

	/**
	 * @return the sessionID
	 */
	public String getSessionID() {
		return sessionID;
	}

	/**
	 * @param sessionID the sessionID to set
	 */
	public void setSessionID(String sessionID) {
		this.sessionID = sessionID;
	}
}