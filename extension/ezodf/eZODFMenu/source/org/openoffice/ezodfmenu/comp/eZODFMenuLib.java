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


/**
 * Class containing eZ ODF menu library. The library include
 * functions for communicating with the eZ REST service.
 */
public class eZODFMenuLib {

	public static final int UNIX = 0;
	public static final int WINDOWS = 0;
	
	/**
	 * Get persistent storage path.
	 * 
	 * @return String Persistent storage path
	 */
	public static String getStoragePath()
	{
		return System.getProperty( "java.io.tmpdir" );
	}
	
	/**
	 * Get Operating system.
	 * 
	 * @return int Operating system
	 */
	public static int getOS()
	{
		int os = UNIX;
		// Detect OS.
		try
		{
			String home = System.getenv( "HOME" );
			if ( home.indexOf( "/" ) == -1 )
			{
				os = WINDOWS;
			}
		}
		catch ( Exception e )
		{
			os = WINDOWS;
		}
		
		return os;
	}
}
