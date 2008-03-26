/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.util.HashMap;

/**
 * The DocumentInfo class contains information about the  
 * @author hovik
 *
 */
public class DocumentInfo {

	private static HashMap<String,eZPTreeNode> documentHash = new HashMap<String,eZPTreeNode>();
	
	/**
	 * Set document info
	 * 
	 * @param key
	 * @param Tree node info
	 */
	public static void setTreeNode( String name, eZPTreeNode treeNode )
	{
		documentHash.put( name, treeNode );
	}
	
	/**
	 * Get tree node
	 * 
	 * @param key
	 * 
	 * @return Tree node
	 */
	public static eZPTreeNode getTreeNode( String name )
	{
		return documentHash.get( name );
	}
}
