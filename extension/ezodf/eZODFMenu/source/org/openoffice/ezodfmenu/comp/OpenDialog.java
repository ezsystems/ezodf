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
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.util.*;

/**
 * Open dialog GUI
 */
public class OpenDialog extends JFrame {

	protected JComboBox serverList;
	protected JTree tree;
	protected JTable table;
	protected JPanel mainPanel;
	protected OpenController controller;
	private static final long serialVersionUID = 4400067100991729955L;

	/**
	 * Constructor. Populates the OpenDialog, but will not display it.
	 */
	public OpenDialog( OpenController openController )
	{
		super();
		this.controller = openController;
		populateDialog();
		this.addWindowListener( new WindowListener() {
			public void windowClosed(WindowEvent e) {}
			public void windowActivated(WindowEvent e) {}
			public void windowClosing(WindowEvent e) {
				System.exit( 0 );
			}
			public void windowDeactivated(WindowEvent e) {}
			public void windowDeiconified(WindowEvent e) {}
			public void windowIconified(WindowEvent e) {}
			public void windowOpened(WindowEvent e) {}
		} );
	}
	
	/**
	 * Populate open dialog.
	 */
	protected void populateDialog()
	{
		// Set size
		setSize( 800, 600 );		

		setLayout( new BorderLayout() );
//		 Add Server selection
		add( getTopComponent(), BorderLayout.NORTH );
		
		// Add server browse/file selection
		mainPanel = new JPanel( new BorderLayout() );
		add( mainPanel, BorderLayout.CENTER );
		
		// Add Open/Cancel buttons.
		add( getButtonPanel(), BorderLayout.SOUTH );
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
		
		JButton cancelButton = new JButton( );
		cancelButton.addActionListener( new ActionListener(){
			public void actionPerformed(ActionEvent arg0) {
					// TODO
			}
		});
		
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
	
	/**
	 * Populate top of open dialog.
	 * 
	 * @return Component Top component.
	 */
	@SuppressWarnings("unchecked")
	protected Component getTopComponent()
	{
		JPanel panel = new JPanel( new BorderLayout() );
		
		JLabel title = new JLabel( "Select server" );
		title.setHorizontalAlignment( SwingConstants.CENTER );
		panel.add( title, BorderLayout.NORTH );
		
		// Build server list.
		JPanel serverPanel = new JPanel( new BorderLayout() );
		HashMap<String, ServerInfo> serverInfoList = ServerInfo.loadHashMapFromFile();
		serverList = new JComboBox( new Vector( serverInfoList.values() ) );
		serverList.setRenderer( new ListCellRenderer() {
			public Component getListCellRendererComponent( JList list,
	                									   Object value,
	                									   int index,
	                									   boolean isSelected,
	                									   boolean cellHasFocus)
			{
				ServerInfo serverInfo = (ServerInfo)value;
				if (isSelected) 
				{
					setBackground(list.getSelectionBackground());
					setForeground(list.getSelectionForeground());
		        }
				else 
				{
					setBackground(list.getBackground());
					setForeground(list.getForeground());
		        }
				return new JLabel( serverInfo.getUsername() + "@" + serverInfo.getUrl() );
			}});
		serverList.setMaximumRowCount( 8 );
		serverList.setEditable( false );
		serverPanel.add( serverList, BorderLayout.CENTER );


		// Add "Connect", "Edit" and "Add server" buttons
		JPanel buttonPanel = new JPanel();
		JButton connect =  new JButton( "Connect" );
		connect.addActionListener( new ActionListener( ) {
				public void actionPerformed( ActionEvent e ) {
					controller.connectToServer( (ServerInfo)serverList.getSelectedItem() );
				}
		});
		buttonPanel.add( connect );
		
		JButton addServer =  new JButton( "Add" );
		addServer.addActionListener( new ActionListener( ) {
				public void actionPerformed( ActionEvent e ) {
					ServerEditDialog editDialog = new ServerEditDialog( getThis(), new ServerInfo() );
					editDialog.setVisible( true );
				}
		});
		buttonPanel.add( addServer );
		
		JButton editServer = new JButton( "Edit" );
		editServer.addActionListener( new ActionListener( ) {
			public void actionPerformed( ActionEvent e ) {
				ServerEditDialog editDialog = new ServerEditDialog( getThis(), (ServerInfo)serverList.getSelectedItem() );
				editDialog.setVisible( true );
			}
		});
		buttonPanel.add( editServer );
		
		serverPanel.add( buttonPanel, BorderLayout.EAST );
		panel.add( serverPanel, BorderLayout.CENTER );
		
		return panel;
	}

	/**
	 * Populate server list content.
	 */
	@SuppressWarnings("unchecked")
	public void populateServerList()
	{
		HashMap<String, ServerInfo> serverInfoList = ServerInfo.loadHashMapFromFile();
		serverList.removeAllItems();
		for( Iterator<ServerInfo> iterator = serverInfoList.values().iterator(); iterator.hasNext(); )
		{
			serverList.addItem( iterator.next() );
		}
	}
	/**
	 * Get this.
	 * 
	 * @return This
	 */
	protected OpenDialog getThis()
	{
		return this;
	}
}


