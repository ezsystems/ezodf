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
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;

/**
 * Open dialog GUI
 */
public class OpenDialog extends Dialog {

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
				controller.exit();
			}
			public void windowDeactivated(WindowEvent e) {}
			public void windowDeiconified(WindowEvent e) {}
			public void windowIconified(WindowEvent e) {}
			public void windowOpened(WindowEvent e) {}
		} );
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
	

}


