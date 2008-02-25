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
import java.util.*;

/**
 * Open dialog GUI
 */
public class eZODFMenuOpenDialog extends JFrame {

	/**
	 * 
	 */
	private static final long serialVersionUID = 4400067100991729955L;

	/**
	 * Constructor. Populates the eZODFMenuOpenDialog, but will not display it.
	 */
	public eZODFMenuOpenDialog()
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
	protected Component getTopComponent()
	{
		JPanel panel = new JPanel( new BorderLayout() );
		
		panel.add( new JLabel( "Select server" ), BorderLayout.NORTH );
		
		// Build server list.
		HashMap<String, eZODFMenuServerInfo> serverList = eZODFMenuServerInfo.loadHashMapFromFile();
		JList list = new JList( new Vector( serverList.values() ) );
		list.setCellRenderer( new ServerCellRenderer() );
		panel.add( list, BorderLayout.SOUTH );
		
		return panel;
	}
	
	/**
	 * Server list cell renderer.
	 */
	class ServerCellRenderer implements ListCellRenderer {
		/**
		 * Reimp.
		 */
		public Component getListCellRendererComponent( JList list,
                									   Object value,
                									   int index,
                									   boolean isSelected,
                									   boolean cellHasFocus)
		{
			eZODFMenuServerInfo serverInfo = (eZODFMenuServerInfo)value;
			return new JLabel( serverInfo.getUsername() + "@" + serverInfo.getUrl() );
		}
	}
}


