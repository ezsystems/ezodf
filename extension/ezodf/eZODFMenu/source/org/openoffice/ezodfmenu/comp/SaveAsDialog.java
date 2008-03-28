/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.awt.BorderLayout;
import java.awt.Component;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JButton;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JTree;
import javax.swing.tree.DefaultTreeCellRenderer;
import javax.swing.tree.TreeSelectionModel;

/**
 * @author hovik
 *
 */
public class SaveAsDialog extends Dialog {

	private static final long serialVersionUID = 2255635211984564117L;

	/**
	 * @param controller
	 */
	public SaveAsDialog(Controller controller) {
		super(controller);
	}

	/**
	 * @see org.openoffice.ezodfmenu.comp.Dialog#getButtonPanel()
	 */
	protected Component getButtonPanel() {
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
		JButton saveButton = new JButton( "Save here" );
		saveButton.addActionListener( new ActionListener(){
			public void actionPerformed(ActionEvent arg0) {
				if ( table.getSelectedRow() != -1  )
				{
					try 
					{
						((SaveAsController)controller).saveAsDocument( (eZPTreeNode)tree.getLastSelectedPathComponent() );
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
						    "SaveAsDialog",
						    JOptionPane.INFORMATION_MESSAGE );
				}	
			}
		});
		buttonPanel.add( saveButton );
		
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

		// Use folder icon for leaf icons.
		DefaultTreeCellRenderer treeRenderer = new DefaultTreeCellRenderer();
		treeRenderer.setLeafIcon( treeRenderer.getDefaultClosedIcon() );
		tree.setCellRenderer( treeRenderer );
		
		mainPanel.add( tree );
		
		mainPanel.updateUI();
	}


}
