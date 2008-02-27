/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
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

import com.sun.org.apache.xerces.internal.impl.xs.dom.DOMParser;

/**
 * @author hovik
 *
 */
public class ServerConnection {
	
	private static final String LoginPath = "ezrest/login";
	private static final String TopNodeListPath = "ezrest/ezodfGetTopNodeList";
	private static final String GetChildrenPath = "ezrest/ezodfGetChildren";
	private static final String GetChildCountPath = "ezrest/ezodfGetChildCount";
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
		    NodeList sessionIDNodeList = domDoc.getElementsByTagName( "SessionID" );
		    if ( sessionIDNodeList.getLength() == 0 )
		    {
		    	JOptionPane.showMessageDialog( null,
					    "Failed logging in: " + domDoc.getElementsByTagName( "Error" ).item( 0 ).getTextContent(),
					    "Login",
					    JOptionPane.WARNING_MESSAGE);
		    }
		    setSessionID( sessionIDNodeList.item(0).getTextContent() );
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
				    "getTopNodeList",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		return result;
	}

	/**
	 * Get child count
	 * 
	 * @param Tree node
	 * 
	 * @return Child count
	 */
	public int getChildCount( eZPTreeNode node )
	{
		HashMap<String,String> getParameters = new HashMap<String,String>();
		getParameters.put( "nodeID", Integer.toString( node.getNodeID() ) );
		
		try
		{
			// Send request	
			InputStream in = MenuLib.sendHTTPGetRequest( getChildCountURL(), getParameters, this.sessionID );
			DOMParser parser = new DOMParser();
			InputSource source = new InputSource(in);
			parser.parse(source);
			in.close();
			
			// Parse result and create eZPTreeNode objects.
			XPath xpath = XPathFactory.newInstance().newXPath();
			
			Document domDoc = parser.getDocument();
		    return Integer.parseInt( (String)xpath.evaluate( "@count", domDoc.getElementsByTagName( "ChildCount" ).item(0), XPathConstants.STRING ) );
		    
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Failed to get children: " + getChildrenURL() + ": " +  e.getMessage(),
				    "getChildren",
				    JOptionPane.WARNING_MESSAGE);
			return -1;
		}
	}
	
	/**
	 * Get children of specified parent node. 
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
				    "getChildren",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		return result;
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
	
	protected String getChildCountURL()
	{
		return serverInfo.getUrl() + "/" + ServerConnection.GetChildCountPath;
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