/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import javax.swing.ListModel;
import javax.swing.event.ListDataListener;

/**
 * @author hovik
 *
 */
public class eZPTreeListModel implements ListModel {

	protected eZPTreeNode treeNode;	

	/**
	 * Constructor
	 * @param Tree node. The list model will be populated by the children of the 
	 * tree node.
	 */
	public eZPTreeListModel( eZPTreeNode treeNode )
	{
		this.treeNode = treeNode;
	}

	/* (non-Javadoc)
	 * @see javax.swing.ListModel#addListDataListener(javax.swing.event.ListDataListener)
	 */
	public void addListDataListener(ListDataListener arg0) {
		// Do nothing yet.
	}

	/* (non-Javadoc)
	 * @see javax.swing.ListModel#getElementAt(int)
	 */
	public Object getElementAt( int idx ) {
		return treeNode.getChild( idx );
	}

	/* (non-Javadoc)
	 * @see javax.swing.ListModel#getSize()
	 */
	public int getSize() {
		return treeNode.getChildCount();
	}

	/* (non-Javadoc)
	 * @see javax.swing.ListModel#removeListDataListener(javax.swing.event.ListDataListener)
	 */
	public void removeListDataListener(ListDataListener arg0) {
		// Do nothing.
	}

}
