/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.io.File;
import java.io.IOException;

import javax.swing.JOptionPane;
import javax.swing.UIManager;
import javax.swing.plaf.metal.MetalLookAndFeel;

import com.sun.star.uno.XComponentContext;

/**
 * @author hovik
 *
 */
public class Controller  {

	protected ServerInfo[] serverInfoList;
	protected ServerConnection serverConnection;
	protected XComponentContext xContext;
	protected Dialog dialog;
	
	/**
	 * Constructor
	 * 
	 * @param context
	 */
	public Controller(XComponentContext context) {
		try {
			UIManager.setLookAndFeel( new MetalLookAndFeel() );
		}
		catch( Exception e ){
		}
		xContext = context;
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
	
	/**
	 * Connect to server
	 * 
	 * @param Server info.
	 */
	public void connectToServer( ServerInfo serverInfo )
	{
		if ( serverInfo == null )
		{
			JOptionPane.showMessageDialog( this.dialog,
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
		dialog.populateMainComponent();
	}

	/**
	 * Convert filename to url.
	 * @param filename
	 * @return url
	 */
	protected String filePathToURL(String file) {
        File f = new File(file);
        StringBuffer sb = new StringBuffer("file:///");
        try {
            sb.append(f.getCanonicalPath().replace('\\', '/'));
        } catch (IOException e) {
        }
        return sb.toString();
    } 

	/**
	 * Open OO Open dialog
	 */
	public void openDialog()
	{
		dialog.setVisible( true );
	}

	/**
	 * Exit program.
	 */
	public void exit()
	{
		dialog.setVisible( false );
	}	
}
