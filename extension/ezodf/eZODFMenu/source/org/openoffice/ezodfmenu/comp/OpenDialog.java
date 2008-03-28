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

import javax.swing.*;
import javax.swing.event.TreeSelectionEvent;
import javax.swing.event.TreeSelectionListener;
import javax.swing.table.DefaultTableModel;
import javax.swing.tree.DefaultTreeCellRenderer;
import javax.swing.tree.TreeSelectionModel;

import java.awt.*;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

/**
 * Open dialog GUI
 */
public class OpenDialog extends Dialog {

	private static final long serialVersionUID = 4400067100991729955L;
	
	
	/**
	 * Constructor
	 */
	public OpenDialog( OpenController controller )
	{
		super( controller );
	}

	/**
	 * Get button panel
	 * 
	 * @return Button panel
	 */
	protected Component getButtonPanel()
	{
		JPanel panel = new JPanel( new BorderLayout() );
		JPanel buttonPanel = new JPanel();
		panel.add( buttonPanel, BorderLayout.EAST );
		
		// Add Cancel button.
		JButton cancelButton = new JButton( "Cancel" );
		cancelButton.addActionListener( new ActionListener(){
			public void actionPerformed(ActionEvent arg0) {
				// Exit program.	 	
				controller.exit();
			}
		});
		buttonPanel.add( cancelButton );
		
		// Add open button.
		JButton openButton = new JButton( "Open" );
		openButton.addActionListener( new ActionListener(){
			public void actionPerformed(ActionEvent arg0) {
				if ( table.getSelectedRow() != -1  )
				{
					try {
						((OpenController)controller).openDocument( ((eZPTreeTableModel)table.getModel()).getTreeNode( table.getSelectedRow() ) );
					}
					catch( Exception e)
					{
						// Do nothing.
					}
				}
				else
				{
					JOptionPane.showMessageDialog( null,
						    "Please select document before opening.",
						    "Open document",
						    JOptionPane.INFORMATION_MESSAGE );
				}	
			}
		});
		buttonPanel.add( openButton );
		
		return panel;
	}
	
	/**
	 * Populate main component.
	 */
	public void populateMainComponent()
	{
		// Remove existing components.
		mainPanel.removeAll();
		
		// Add JTree
		tree = new JTree( new eZPTreeModel( controller.serverConnection ) );
		tree.getSelectionModel().setSelectionMode( TreeSelectionModel.SINGLE_TREE_SELECTION );
		tree.addTreeSelectionListener( new TreeSelectionListener(){
			public void valueChanged(TreeSelectionEvent arg0) {
				eZPTreeNode node = (eZPTreeNode)tree.getLastSelectedPathComponent();

				/* if nothing is selected, set empty list model, if not, use populated list model. */ 
				if (node == null){
					table.setModel( new DefaultTableModel() );
				}
				else{
					table.setModel( new eZPTreeTableModel( node ) );
				}
			}	
		});
		// Use folder icon for leaf icons.
		DefaultTreeCellRenderer treeRenderer = new DefaultTreeCellRenderer();
		treeRenderer.setLeafIcon( treeRenderer.getDefaultClosedIcon() );
		tree.setCellRenderer( treeRenderer );
		JScrollPane treeScrollPane = new JScrollPane( tree );
		
		// Add table.
		table = new JTable( new DefaultTableModel() );
		table.setSelectionMode( ListSelectionModel.SINGLE_SELECTION );
		JScrollPane listScrollPane = new JScrollPane( table );
		
		mainPanel.add( new JSplitPane( JSplitPane.HORIZONTAL_SPLIT, treeScrollPane, listScrollPane ) );
		
		mainPanel.updateUI();
	}

}


