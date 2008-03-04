/**
 * ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
 * SOFTWARE NAME: eZ ODF
 * SOFTWARE RELEASE: 1.0.x
 * COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
 * SOFTWARE LICENSE: GNU General Public License v2.0
 * NOTICE: >
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of version 2.0  of the GNU General
 *   Public License as published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of version 2.0 of the GNU General
 *   Public License along with this program; if not, write to the Free
 *   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *   MA 02110-1301, USA.
 *
 *
 * ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
 */
package org.openoffice.ezodfmenu.comp;

import java.io.Serializable;
import java.util.Date;
import java.util.Vector;

import javax.swing.JOptionPane;
import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathFactory;

import org.w3c.dom.Node;

import sun.misc.BASE64Decoder;

/**
 * @author hovik
 *
 */
public class eZPTreeNode 
	implements Serializable, org.openoffice.ezodfmenu.eZPTreeNode {

	private static final long serialVersionUID = 5473805752377662556L;
	protected Node treeNode = null;
	protected ServerConnection serverConnection;
	
	protected byte[] OODocumentData = null;
	protected String OODocumentFilename = null;
	
	protected Vector<eZPTreeNode> menuChildren = new Vector<eZPTreeNode>();
	protected Vector<eZPTreeNode> children = new Vector<eZPTreeNode>();
	protected int childTreeCount = -1;
	
	public final static int TopNodeID = -1;
	
	/**
	 * Create empty tree node.
	 */
	public eZPTreeNode()
	{		
	}

	/**
	 * Constructor
	 *
	 * @param Server connection.
	 * @param Node
	 */
	public eZPTreeNode( ServerConnection connection, Node node )
	{
		serverConnection = connection;
		treeNode = node;
	}
	
	/**
	 * Get OpenDocument data.
	 * 
	 * @return OO document data.
	 */
	public byte[] getOODocumentData()
	{
		if ( this.OODocumentData == null )
		{
			this.loadOODocument();
		}
		
		return this.OODocumentData; 
	}

	/**
	 * Get OpenDocument filename.
	 * 
	 * @return OO document filename.
	 */
	public String getOODocumentFilename()
	{
		if ( this.OODocumentFilename == null )
		{
			this.loadOODocument();
		}
		
		return this.OODocumentFilename; 
	}

	/**
	 * Load OODocument data.
	 */
	protected void loadOODocument()
	{
		BASE64Decoder decoder = new BASE64Decoder();
		Node OODocumentNode = serverConnection.getOODocument( this );
		if ( OODocumentNode == null )
		{
			JOptionPane.showMessageDialog( null,
				    "Unable to get OODocument node.",
				    "eZPTreeNode.loadOODocument",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "@filename";
		try
		{
			this.OODocumentFilename = (String) xpath.evaluate(expression, OODocumentNode, XPathConstants.STRING);
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Unable to get filename form OODocument: " + e.getMessage(),
				    "eZPTreeNode.loadOODocument",
				    JOptionPane.WARNING_MESSAGE);
		}
		
		expression = "text()";
		try
		{
			this.OODocumentData = decoder.decodeBuffer( (String) xpath.evaluate(expression, OODocumentNode, XPathConstants.STRING) );
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Unable to get ODT data form OODocument: " + e.getMessage(),
				    "eZPTreeNode.loadOODocument",
				    JOptionPane.WARNING_MESSAGE);
		}
	}
	
	/**
	 * Get node name
	 * @return Name
	 */
	public String getName()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "Object/NameList/Name[1]/text()";
		try
		{
			return (String) xpath.evaluate(expression, treeNode, XPathConstants.STRING);
		}
		catch ( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Get name XPath failed: " + e.getMessage(),
				    "eZPTreeNode.getName()",
				    JOptionPane.WARNING_MESSAGE);
			return "";
		}
	}

	/**
	 * Get node name
	 * @return Name
	 */
	public String getClassName()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "Object/Class/NameList/Name[1]/text()";
		try
		{
			return (String) xpath.evaluate(expression, treeNode, XPathConstants.STRING);
		}
		catch ( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Get name XPath failed: " + e.getMessage(),
				    "eZPTreeNode.getName()",
				    JOptionPane.WARNING_MESSAGE);
			return "";
		}
	}

	public int getNodeID()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "@nodeID";
		try
		{
			return Integer.parseInt( (String)xpath.evaluate(expression, treeNode, XPathConstants.STRING ) );
		}
		catch ( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Get name XPath failed: " + e.getMessage(),
				    "eZPTreeNode.getNodeID()",
				    JOptionPane.WARNING_MESSAGE);
			return 0;
		}
	}

	/**
	 * Get published date time string.
	 * 
	 * @return Date time.
	 */
	public Date getPublishedDateTime()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "Object/@published";
		try
		{
			return new Date( Long.parseLong( (String)xpath.evaluate(expression, treeNode, XPathConstants.STRING ) ) * 1000 );
		}
		catch ( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Get name XPath failed: " + e.getMessage(),
				    "eZPTreeNode.getNodeID()",
				    JOptionPane.WARNING_MESSAGE);
			return new Date();
		}
	}
	
	/**
	 * Get modified date time string.
	 * 
	 * @return Date time.
	 */
	public Date getModifiedDateTime()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "Object/@modified";
		try
		{
			return new Date( Long.parseLong( (String)xpath.evaluate(expression, treeNode, XPathConstants.STRING ) ) * 1000 );
		}
		catch ( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Get name XPath failed: " + e.getMessage(),
				    "eZPTreeNode.getNodeID()",
				    JOptionPane.WARNING_MESSAGE);
			return new Date();
		}
	}
	
	/**
	 * Get Child by index.
	 * @param Index
	 * @return Tree node cild.
	 */
	public eZPTreeNode getChild( int idx )
	{
		try
		{
			return children.get( idx );
		}
		catch( Exception e )
		{
			// If unable child node is not fetched yet, fetch next 10 children, and try again.
			try
			{
				children.addAll( serverConnection.getChildren( this, children.size(), 10 ) );
				return this.getChild( idx );
			}
			catch( Exception e2 )
			{
				// Error output handled by serverConnection.getChildren();
			}
		}
		
		return null;
	}
	
	/**
	 * Get Menu Child by index.
	 * @param Index
	 * @return Tree node cild.
	 */
	public eZPTreeNode getMenuChild( int idx )
	{
		try
		{
			return menuChildren.get( idx );
		}
		catch( Exception e )
		{
			// If unable child node is not fetched yet, fetch next 10 children, and try again.
			try
			{
				menuChildren.addAll( serverConnection.getMenuChildren( this, menuChildren.size(), 10 ) );
				return this.getMenuChild( idx );
			}
			catch( Exception e2 )
			{
				// Error output handled by serverConnection.getChildren();
			}
		}
		
		return null;
	}
	
	/**
	 * Get child count.
	 * 
	 * @return Child count
	 */
	public int getMenuChildCount()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "@childMenuCount";
		try
		{
			return Integer.parseInt( (String)xpath.evaluate(expression, treeNode, XPathConstants.STRING ) );
		}
		catch ( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Get menuChildCount XPath failed: " + e.getMessage(),
				    "eZPTreeNode.getMenuChildTreeCount()",
				    JOptionPane.WARNING_MESSAGE);
			return 0;
		}
	}
	
	/**
	 * Get child count.
	 * 
	 * @return Child count
	 */
	public int getChildCount()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "@childCount";
		try
		{
			return Integer.parseInt( (String)xpath.evaluate(expression, treeNode, XPathConstants.STRING ) );
		}
		catch ( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Get childCount XPath failed: " + e.getMessage(),
				    "eZPTreeNode.getChildCount()",
				    JOptionPane.WARNING_MESSAGE);
			return 0;
		}
	}
	
	/**
	 * Get index of child node.
	 * 
	 * @param childNode
	 * 
	 * @return Index of child node.
	 */
	public int getIndexOfChild( eZPTreeNode childNode )
	{
		return children.indexOf( childNode );
	}
	
	/**
	 * Get index of menu child node.
	 * 
	 * @param childNode
	 * 
	 * @return Index of child node.
	 */
	public int getIndexOfMenuChild( eZPTreeNode childNode )
	{
		return menuChildren.indexOf( childNode );
	}
	
	
	/**
	 * @return the treeNode
	 */
	public Node getTreeNode() {
		return treeNode;
	}

	/**
	 * @param treeNode the treeNode to set
	 */
	public void setTreeNode(Node treeNode) {
		this.treeNode = treeNode;
	}
	
	public String toString()
	{
		return this.getName() + " ( " + this.getClassName() + " )";
	}
}
