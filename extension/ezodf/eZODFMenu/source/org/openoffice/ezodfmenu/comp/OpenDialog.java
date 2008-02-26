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
import java.awt.*;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.*;

/**
 * Open dialog GUI
 */
public class OpenDialog extends JFrame {

	protected JList serverList;
	private static final long serialVersionUID = 4400067100991729955L;

	/**
	 * Constructor. Populates the OpenDialog, but will not display it.
	 */
	public OpenDialog()
	{
		super();
		populateDialog();
	}
	
	/**
	 * Populate open dialog.
	 */
	protected void populateDialog()
	{
		// Set size
		setSize( 800, 600 );
		
		// Add main layout components
		setLayout( new BorderLayout() );
		add( getTopComponent(), BorderLayout.NORTH );
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
		
		panel.add( new JLabel( "Select server" ), BorderLayout.NORTH );
		
		// Build server list.
		JPanel serverPanel = new JPanel( new BorderLayout() );
		HashMap<String, ServerInfo> serverInfoList = ServerInfo.loadHashMapFromFile();
		serverList = new JList( new Vector( serverInfoList.values() ) );
		serverList.setCellRenderer( new ListCellRenderer() {
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
		serverList.setSelectionMode( ListSelectionModel.SINGLE_SELECTION );
		serverList.setVisibleRowCount( 1 );
		serverPanel.add( serverList, BorderLayout.CENTER );


		// Add "Connect", "Edit" and "Add server" buttons
		JPanel buttonPanel = new JPanel();
		JButton connect =  new JButton( "Connect" );
		connect.addActionListener( new ActionListener( ) {
				public void actionPerformed( ActionEvent e ) {
					//connectToSever
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
				ServerEditDialog editDialog = new ServerEditDialog( getThis(), (ServerInfo)serverList.getSelectedValue() );
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
		serverList.setListData( new Vector( serverInfoList.values() ) );
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


