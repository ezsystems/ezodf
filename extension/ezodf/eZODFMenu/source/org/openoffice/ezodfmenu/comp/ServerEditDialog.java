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

import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JTextField;

import java.awt.BorderLayout;
import java.awt.GridLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.WindowEvent;
import java.awt.event.WindowStateListener;
import java.net.URL;

/**
 * @author hovik
 *
 */
public class ServerEditDialog extends JDialog 
{

	private static final long serialVersionUID = -8627326145818032301L;
	protected ServerInfo serverInfo;
	protected OpenDialog openDialog;
	
	protected JTextField urlField;
	protected JTextField usernameField;
	protected JPasswordField password1;
	protected JPasswordField password2;

	/**
	 * Constructor
	 * 
	 * @param Server info.
	 */
	public ServerEditDialog( final OpenDialog openDialog, ServerInfo serverInfo )
	{
		super( openDialog );
		openDialog.setEnabled( false );
		setSize( 600, 200 );
		setServerInfo( serverInfo );
		
		addWindowStateListener( new WindowStateListener(){
			public void windowStateChanged(WindowEvent arg0) {
				openDialog.setEnabled( true );
			}
		});
		
		this.openDialog = openDialog;
		populateDialog();
	}
	
	/**
	 * Populate dialog.
	 */
	protected void populateDialog()
	{
		setLayout( new BorderLayout() );
		add( new JLabel( "Edit server settings " ), BorderLayout.NORTH );
		
		JPanel serverPanel = new JPanel( new GridLayout( 4,2 ) );
		
		serverPanel.add( new JLabel( "Server URL" ) );
		serverPanel.add( urlField = new JTextField( getServerInfo().getUrl() ) );
		
		serverPanel.add( new JLabel( "Username" ) );
		serverPanel.add( usernameField = new JTextField( getServerInfo().getUsername() ) );
		
		serverPanel.add( new JLabel( "Password" ) );
		serverPanel.add( password1 = new JPasswordField( getServerInfo().getPassword() ) );

		serverPanel.add( new JLabel( "Password(again)" ) );
		serverPanel.add( password2 = new JPasswordField( getServerInfo().getPassword() ) );

		add( serverPanel, BorderLayout.CENTER );
		
		JPanel buttonPanel = new JPanel();
		
		JButton cancelButton = new JButton( "Cancel" );
		cancelButton.addActionListener( new ActionListener(){
			public void actionPerformed(ActionEvent e)
			{
				close();
			}
		});
		buttonPanel.add( cancelButton );
		
		JButton storeButton = new JButton( "Store" );
		storeButton.addActionListener( new ActionListener(){
			public void actionPerformed(ActionEvent e)
			{
				if ( validateAndStore() )
				{
					close();
				}
			} 
		});
		buttonPanel.add( storeButton );
		
		add( buttonPanel, BorderLayout.SOUTH );
	}
	
	/**
	 * Store and add server info to server info list.
	 */
	protected boolean validateAndStore()
	{
		// Check URL
		if ( urlField.getText().trim() == "" )
		{
			JOptionPane.showMessageDialog(this,
				    "Invalid URL",
				    "URL",
				    JOptionPane.WARNING_MESSAGE);
			return false;
		}
		try {
			new URL( urlField.getText() );
		} 
		catch( Exception e ) {
			JOptionPane.showMessageDialog(this,
				    "Invalid URL",
				    "URL",
				    JOptionPane.WARNING_MESSAGE);
			return false;			
		}
		
		// Check username
		if ( usernameField.getText().trim() == "" )
		{
			JOptionPane.showMessageDialog(this,
				    "Invalid username",
				    "Username",
				    JOptionPane.WARNING_MESSAGE);
			return false;
		}
		
		// Password check
		if ( new String( password1.getPassword() ).trim() == "" ||
			 !( new String( password1.getPassword() ).equals( new String( password2.getPassword() ) ) ) )
		{
			JOptionPane.showMessageDialog(this,
					"Missmatching password, " + new String( password1.getPassword() ) + '-' + new String( password2.getPassword() ),
					"Password",
					JOptionPane.WARNING_MESSAGE);
			return false;
		}

		serverInfo.setUrl( urlField.getText() );
		serverInfo.setUsername( usernameField.getText() );
		serverInfo.setPassword( new String( password1.getPassword() ) );
		
		ServerInfo.addToList( serverInfo );
		openDialog.populateServerList();
		
		return true;
	}

	/**
	 * Close dialog.
	 */
	protected void close()
	{
		this.openDialog.setEnabled( true );
		this.setVisible( false );
	}

	/**
	 * @return the serverInfo
	 */
	public ServerInfo getServerInfo() {
		return serverInfo;
	}
	/**
	 * @param serverInfo the serverInfo to set
	 */
	public void setServerInfo(ServerInfo serverInfo) {
		this.serverInfo = serverInfo;
	}
}
