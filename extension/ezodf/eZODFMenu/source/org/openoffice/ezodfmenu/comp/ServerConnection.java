/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.net.HttpURLConnection;
import java.net.URL;

/**
 * @author hovik
 *
 */
public class ServerConnection {
	
	protected ServerInfo serverInfo;
	protected HttpURLConnection connection = null;
	
	/**
	 * Constructor
	 * 
	 * @param Server info
	 */
	public ServerConnection( ServerInfo info )
	{
		this.serverInfo = info;
	}
	
	/**
	 * Connect to server
	 * 
	 * @return True if connection is created. The connection properties is stored in this object.
	 */
	public boolean connect()
	{		
		this.connection = null;
		try
		{
			URL url = new URL( serverInfo.getUrl() );
			this.connection = (HttpURLConnection)url.openConnection();
			return true;
		}
		catch( Exception e )
		{
			return false;
		}
	}
	
	/**
	 * Check if server is connected
	 */
	public boolean isConnected()
	{
		return ( this.connection != null );
	}
}
