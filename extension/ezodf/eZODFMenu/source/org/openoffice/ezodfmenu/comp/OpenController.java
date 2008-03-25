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

import java.io.File;
import java.io.IOException;
import java.util.ArrayList;

import javax.swing.JOptionPane;
import javax.swing.SwingUtilities;
import javax.swing.UIManager;
import javax.swing.plaf.metal.MetalLookAndFeel;

import com.sun.star.awt.XWindow;
import com.sun.star.awt.XWindowListener;
import com.sun.star.beans.PropertyValue;
import com.sun.star.document.EventObject;
import com.sun.star.document.XEventListener;
import com.sun.star.frame.XComponentLoader;
import com.sun.star.frame.XController;
import com.sun.star.frame.XFrame;
import com.sun.star.frame.XModel;
import com.sun.star.lang.XComponent;
import com.sun.star.lang.XMultiComponentFactory;
import com.sun.star.uno.UnoRuntime;
import com.sun.star.uno.XComponentContext;
import com.sun.star.xml.dom.XDocument;


/**
 * eZODFMenuOepnController. This class creates "Open" dialog
 * and contains the controlling methods.
 */
public class OpenController extends Controller {
	
	/**
	 * Constructor. Initializes the open dialog. Execute the
	 * open() method to open the dialog. 
	 */
	public OpenController( XComponentContext xContext ) {
		super( xContext );
		dialog = new OpenDialog( this );
		SwingUtilities.updateComponentTreeUI( dialog );
	}
	
	/**
	 * Open OO Document in OpenOffice.org
	 * 
	 * @param Tree node representing the document.
	 */
	public void openDocument( eZPTreeNode treeNode )
	{
		byte[] ooDocumentData = treeNode.getOODocumentData();
		
		// If OO data is null, return.
		if ( ooDocumentData == null )
		{
			return;
		}
		
		XMultiComponentFactory serviceManager = xContext.getServiceManager();
		Object desktop;

        // Retrieve the Desktop object and get its XComponentLoader.
		try 
		{
			desktop = serviceManager.createInstanceWithContext( "com.sun.star.frame.Desktop", xContext );
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Unable to load desktop from service manager: " + e.getMessage(),
				    "OpenController.openDocument",
				    JOptionPane.WARNING_MESSAGE);
			return;
		}
        XComponentLoader loader = (XComponentLoader)UnoRuntime.queryInterface( XComponentLoader.class, desktop);

        // Define general document properties (see
        // com.sun.star.document.MediaDescriptor for the possibilities).
        ArrayList<PropertyValue> props = new ArrayList<PropertyValue>();
        PropertyValue p = null;
        // Set document filename
        p = new PropertyValue();
        p.Name = "eZPFilename";
        p.Value = treeNode.getOODocumentFilename();
        props.add(p);
        p = new PropertyValue();
        p.Name = "Title";
        p.Value = treeNode.getName();
        props.add(p);
        // Add eZPTreeNode
        p = new PropertyValue();
        p.Name = "eZPTreeNode";
        p.Value = treeNode;
        props.add( p );

        PropertyValue[] properties = new PropertyValue[props.size()];
        props.toArray(properties);

        // Create the document
        // (see com.sun.star.frame.XComponentLoader for argument details).

        XComponent document = null;
        String odfFile = MenuLib.storeTempFile( ooDocumentData, treeNode.getOODocumentFilename() );

        if ( odfFile != null) {
            // Create a new document that is a duplicate of the template.

            String templateFileURL = this.filePathToURL( odfFile );
            try {
            	document = loader.loadComponentFromURL(
            			templateFileURL,    // URL of templateFile.
            			"_blank",           // Target frame name (_blank creates new frame).
            			0,                  // Search flags.
            			properties);        // Document attributes.
            }
            catch ( Exception e )
            {
            	JOptionPane.showMessageDialog( null,
    				    "Unable to load component from URL : " + e.getMessage(),
    				    "OpenController.openDocument",
    				    JOptionPane.WARNING_MESSAGE);
            	return;
            }
       
               // Get the document window and frame.

        	XModel model = (XModel) UnoRuntime.queryInterface(XModel.class, document);
        	XController c = model.getCurrentController();
        	XFrame frame = c.getFrame();
        	XWindow window = frame.getContainerWindow();

        	window.setEnable( true );
        	window.setVisible( true );
        }
	}
}
