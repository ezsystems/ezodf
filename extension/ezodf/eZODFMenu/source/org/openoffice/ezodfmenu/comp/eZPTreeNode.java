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

import javax.swing.JOptionPane;
import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathFactory;

import org.w3c.dom.Node;

/**
 * @author hovik
 *
 */
public class eZPTreeNode {

	protected Node treeNode = null;
	protected ServerConnection serverConnection;
	
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
	 * Get node name
	 * @return Name
	 */
	public String getName()
	{
		XPath xpath = XPathFactory.newInstance().newXPath();
		String expression = "Object/NameList/Name[@locale=Object/@initialLanguage]/text()";
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
		// TODO
		return 0;
	}
	
	/**
	 * Get Child by index.
	 * @param Index
	 * @return Tree node cild.
	 */
	public eZPTreeNode getChild( int idx )
	{
		//TODO
		return null;
	}
	
	public int getChildCount()
	{
		// TODO
		return 0;
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
	
	
}
