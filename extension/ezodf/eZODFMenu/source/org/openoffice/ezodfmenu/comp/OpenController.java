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
import javax.swing.SwingUtilities;
import javax.swing.UIManager;
import javax.swing.plaf.metal.MetalLookAndFeel;
import javax.swing.plaf.synth.SynthLookAndFeel;


/**
 * eZODFMenuOepnController. This class creates "Open" dialog
 * and contains the controlling methods.
 */
public class OpenController {

	protected OpenDialog openDialog;
	protected ServerInfo[] serverInfoList;
	protected ServerConnection serverConnection;
	
	/**
	 * Constructor. Initializes the open dialog. Execute the
	 * open() method to open the dialog. 
	 */
	public OpenController() {
		try {
			UIManager.setLookAndFeel( new MetalLookAndFeel() );
		}
		catch( Exception e ){
		}
		openDialog = new OpenDialog( this );
		SwingUtilities.updateComponentTreeUI( openDialog );
	}
	
	/**
	 * Load data needed by the open dialog window
	 */
	public void loadData()
	{
		// TODO
	}

	/**
	 * Connect to server
	 * 
	 * @param Server info.
	 */
	public void connectToServer( ServerInfo serverInfo )
	{
		if ( serverInfo == null )
		{
			JOptionPane.showMessageDialog( this.openDialog,
					"No server selected",
					"Connect",
					JOptionPane.WARNING_MESSAGE );
			return;
		}
		this.serverConnection = new ServerConnection( serverInfo );
		if ( !this.serverConnection.connect() )
		{
			// Error output handled by serverConnection.
			return;
		}
	}

	/**
	 * Open OO Open dialog
	 */
	public void openDialog()
	{
		openDialog.setVisible( true );
	}

	/**
	 * @return the serverInfoList
	 */
	public ServerInfo[] getServerInfoList() {
		return serverInfoList;
	}

	/**
	 * @param serverInfoList the serverInfoList to set
	 */
	public void setServerInfoList(ServerInfo[] serverInfoList) {
		this.serverInfoList = serverInfoList;
	}

}
