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

import java.io.Serializable;
import java.util.HashMap;
import java.util.Arrays;
import java.io.*;

/**
 * @author hovik
 *
 */
public class ServerInfo implements Serializable, Comparable {

	public static HashMap<String, ServerInfo> ServerList = new HashMap<String, ServerInfo>();

	private static final long serialVersionUID = 7934826971361655809L;
	
	protected String url;
	protected String username;
	protected String password;
	protected long accessTime;

	/**
	 * @return the password
	 */
	public String getPassword() {
		accessTime = System.currentTimeMillis();
		return password;
	}
	/**
	 * @param password the password to set
	 */
	public void setPassword(String password) {
		this.password = password;
	}

	/**
	 * Get Key for ServerInfo list
	 * 
	 * @return Key
	 */
	public String getKey()
	{
		return getUsername() + '@' + getUrl();
	}
	
	/**
	 * @return the url
	 */
	public String getUrl() {
		return url;
	}

	/**
	 * @param url the url to set
	 */
	public void setUrl(String url) {
		this.url = url;
	}
	/**
	 * @return the username
	 */
	public String getUsername() {
		return username;
	}
	/**
	 * @param username the username to set
	 */
	public void setUsername(String username) {
		this.username = username;
	}

	/**
	 * Load ServerInfo list from persistent file.
	 * 
	 * @return Map of server info. 
	 */
	@SuppressWarnings("unchecked")
	public static HashMap<String, ServerInfo> loadHashMapFromFile()
	{
		try {
			File file = new File( MenuLib.getStoragePath(), getFilename() );
			ObjectInputStream inStream = new ObjectInputStream( new FileInputStream( file ) );
			ServerInfo.ServerList = (HashMap<String, ServerInfo>)inStream.readObject();
			return ServerInfo.ServerList;
		}
		catch( Exception e ){
			return new HashMap<String, ServerInfo>();
		}
	}
	
	/**
	 * Store HashMap to file.
	 */
	public static void storeHashMapToFile()
	{
		// Keep 10 latest installations. ( ordered by accessTime ).
		HashMap<String, ServerInfo> serverHashMap = new HashMap<String, ServerInfo>();
		Object[] serverArray = (Object[])ServerInfo.ServerList.values().toArray();
		Arrays.sort( serverArray );
		for( int idx = 0; idx < ( serverArray.length < 10 ? serverArray.length : 10 ); ++idx )
		{
			ServerInfo serverInfo = (ServerInfo)serverArray[idx];
			serverHashMap.put( serverInfo.getKey(), serverInfo );
		}
		
		try {
			File file = new File( MenuLib.getStoragePath(), getFilename() );
			ObjectOutputStream oos = new ObjectOutputStream( new FileOutputStream( file ) );
			oos.writeObject( serverHashMap );
		}
		catch( Exception e )
		{
			// Do nothing.
		}
	}
	
	/**
	 * Add Server Info to server list.
	 */
	public static void addToList( ServerInfo serverInfo )
	{
		ServerInfo.ServerList.put( serverInfo.getKey(), serverInfo );
		ServerInfo.storeHashMapToFile();
	}

	/**
	 * Get ServerInfo filename.
	 */
	public static String getFilename()
	{
		return "ServerInfo.dat";
	}
	/**
	 * @return the accessTime
	 */
	public long getAccessTime() {
		return accessTime;
	}
	/**
	 * @param accessTime the accessTime to set
	 */
	public void setAccessTime(long accessTime) {
		this.accessTime = accessTime;
	}

	public int compareTo(Object obj) {
		return (int)(this.getAccessTime() - ((ServerInfo)obj).getAccessTime());
	}
}
