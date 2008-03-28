/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.net.MalformedURLException;
import java.util.HashMap;

import javax.swing.JOptionPane;

import com.sun.star.beans.PropertyValue;
import com.sun.star.frame.XDesktop;
import com.sun.star.frame.XStorable;
import com.sun.star.io.IOException;
import com.sun.star.lang.XComponent;
import com.sun.star.lang.XMultiComponentFactory;
import com.sun.star.text.XTextDocument;
import com.sun.star.uno.UnoRuntime;
import com.sun.star.uno.XComponentContext;

/**
 * @author hovik
 *
 */
public class SaveController extends Controller {

	protected static HashMap<XComponentContext, SaveController> instanceList = new HashMap<XComponentContext, SaveController>();

	/**
	 * @param context
	 */
	public SaveController(XComponentContext context) {
		super(context);
		instanceList.put( context, this );
	}
	
	/**
	 * Get instance of save controller.
	 * 
	 *  @param context
	 *  
	 *  @return SaveController
	 */
	public static SaveController getInstance( XComponentContext context )
	{
		SaveController controller = instanceList.get( context );
		if ( controller == null )
		{
			controller = new SaveController( context );
		}
		
		return controller;
	}
	
	/**
	 * Save document.
	 */
	public void saveDocument()
	{
		XMultiComponentFactory serviceManager = xContext.getServiceManager();
		XDesktop xDesktop;
		
        // Retrieve the Desktop object and get its XComponentLoader.
		try 
		{
			Object desktop = serviceManager.createInstanceWithContext( "com.sun.star.frame.Desktop", xContext );
			xDesktop = (XDesktop)UnoRuntime.queryInterface(XDesktop.class, desktop); 
		}
		catch( Exception e )
		{
			JOptionPane.showMessageDialog( null,
				    "Unable to load desktop from service manager: " + e.getMessage(),
				    "SaveController.saveDocument",
				    JOptionPane.WARNING_MESSAGE);
			return;
		}
		
		XComponent document = xDesktop.getCurrentComponent();
		XTextDocument textDocument = (XTextDocument)UnoRuntime.queryInterface( com.sun.star.text.XTextDocument.class, document );		
	      
	    // Access filename and eZPTreeNode property value.
		eZPTreeNode treeNode = DocumentInfo.getTreeNode( textDocument.getURL() );
		String filename = treeNode.getOODocumentFilename();
	    
		// URL where the document is to be stored
		File storeFile = new File( MenuLib.getStoragePath(), filename );
		String storeUrl;
		try 
		{
			storeUrl = storeFile.toURL().toString();
		} 
		catch ( MalformedURLException e ) 
		{
			JOptionPane.showMessageDialog( null,
				    "Unable to get correct tmp file name: " + e.getMessage(),
				    "SaveController.saveDocument",
				    JOptionPane.WARNING_MESSAGE);
			return;
		}
		XStorable xStorable = (XStorable)UnoRuntime.queryInterface(XStorable.class, textDocument);

		// Store document to mtp path.
		try 
		{
			xStorable.storeAsURL( storeUrl, new PropertyValue[0] ) ;
		} 
		catch ( IOException e ) 
		{
			JOptionPane.showMessageDialog( null,
				    "Unable to save file to temporary location: " + e.getMessage(),
				    "SaveController.saveDocument",
				    JOptionPane.WARNING_MESSAGE);
			return;
		}
		
		// Read document to byte[]
	     try
	     {
			 InputStream is = new FileInputStream( storeFile );
			 long length = storeFile.length();
		     byte[] data = new byte[(int)length];
		    
		     int offset = 0;
		     int numRead = 0;
		     while ( offset < data.length
		    		 && ( numRead=is.read(data, offset, data.length - offset ) ) >= 0 )
		     {
		    	 offset += numRead;
		     }
		     treeNode.getServerConnection().replaceOODocument( treeNode, data );
	     }
	     catch( Exception e )
	     {
			JOptionPane.showMessageDialog( null,
				    "Unable to read file from temporary location: " + e.getMessage(),
				    "SaveController.saveDocument",
				    JOptionPane.WARNING_MESSAGE);
			return;
		 }
	}
}
