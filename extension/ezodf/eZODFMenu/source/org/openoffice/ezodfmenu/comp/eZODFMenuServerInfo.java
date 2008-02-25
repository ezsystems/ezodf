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
public class eZODFMenuServerInfo implements Serializable, Comparable {

	public static HashMap<String, eZODFMenuServerInfo> ServerList;

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
	 * Load eZODFMenuServerInfo list from persistent file.
	 * 
	 * @return Map of server info. 
	 */
	@SuppressWarnings("unchecked")
	public static HashMap<String, eZODFMenuServerInfo> loadHashMapFromFile()
	{
		try {
			File file = new File( eZODFMenuLib.getStoragePath(), getFilename() );
			ObjectInputStream inStream = new ObjectInputStream( new FileInputStream( file ) );
			eZODFMenuServerInfo.ServerList = (HashMap<String, eZODFMenuServerInfo>)inStream.readObject();
			return eZODFMenuServerInfo.ServerList;
		}
		catch( Exception e ){
			return new HashMap<String, eZODFMenuServerInfo>();
		}
	}
	
	/**
	 * Store HashMap to file.
	 */
	public static void storeHashMapToFile()
	{
		// Keep 10 latest installations. ( ordered by accessTime ).
		HashMap<String, eZODFMenuServerInfo> serverHashMap = new HashMap<String, eZODFMenuServerInfo>();
		eZODFMenuServerInfo[] serverArray = (eZODFMenuServerInfo[])eZODFMenuServerInfo.ServerList.values().toArray();
		Arrays.sort( serverArray );
		for( int idx = 0; idx < ( serverArray.length < 10 ? serverArray.length : 10 ); ++idx )
		{
			serverHashMap.put( serverArray[idx].getUrl(), serverArray[idx] );
		}
		
		try {
			File file = new File( eZODFMenuLib.getStoragePath(), getFilename() );
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
	public static void addToList( eZODFMenuServerInfo serverInfo )
	{
		eZODFMenuServerInfo.ServerList.put( serverInfo.getUrl(), serverInfo );
		eZODFMenuServerInfo.storeHashMapToFile();
	}

	/**
	 * Get eZODFMenuServerInfo filename.
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
		return (int)(this.getAccessTime() - ((eZODFMenuServerInfo)obj).getAccessTime());
	}
}
