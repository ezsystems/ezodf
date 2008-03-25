/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.awt.BorderLayout;
import java.awt.Component;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Vector;

import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JList;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JSplitPane;
import javax.swing.JTable;
import javax.swing.JTree;
import javax.swing.ListCellRenderer;
import javax.swing.ListSelectionModel;
import javax.swing.SwingConstants;
import javax.swing.event.TreeSelectionEvent;
import javax.swing.event.TreeSelectionListener;
import javax.swing.table.DefaultTableModel;
import javax.swing.tree.DefaultTreeCellRenderer;
import javax.swing.tree.TreeSelectionModel;

/**
 * @author hovik
 *
 */
public abstract class Dialog extends JFrame {

	protected Controller controller;
	protected JComboBox serverList;
	protected JTree tree;
	protected JTable table;
	protected JPanel mainPanel;
	
	/**
	 * 
	 */
	private static final long serialVersionUID = -5712029151989993846L;

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
	 * Get button panel
	 * 
	 * @return Button panel
	 */
	protected abstract Component getButtonPanel();

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
	 * Get this.
	 * 
	 * @return This
	 */
	protected Dialog getThis()
	{
		return this;
	}
}
