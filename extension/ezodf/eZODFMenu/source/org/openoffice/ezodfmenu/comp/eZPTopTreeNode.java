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

/**
 * @author hovik
 *
 */
public class eZPTopTreeNode extends eZPTreeNode {

	protected String name;

	/*
	 * Constructor
	 * 
	 * @param name
	 */
	public eZPTopTreeNode( ServerConnection connection, String nodeName )
	{
		serverConnection = connection;
		name = nodeName;
		
		children = serverConnection.getTopNodeList();
		menuChildren = children;
	}

	/*
	 * @see org.openoffice.ezodfmenu.comp.eZPTreeNode#getName()
	 */
	public String getName()
	{
		return name;
	}
	
	/*
	 * @see org.openoffice.ezodfmenu.comp.eZPTreeNode#getChildCount()
	 */
	public int getMenuChildCount()
	{
		return children.size();
	}
	
	/*
	 * @see org.openoffice.ezodfmenu.comp.eZPTreeNode#getChildCount()
	 */
	public int getChildCount()
	{
		return children.size();
	}
	
	/*
	 * @see eZPTreeNode.getNodeID
	 */
	public int getNodeID()
	{
		return eZPTreeNode.TopNodeID;
	}
	
	public String toString()
	{
		return this.getName();
	}
}
