/**
 * 
 */
package org.openoffice.ezodfmenu.comp;

import java.util.HashMap;
import javax.swing.SwingUtilities;
import com.sun.star.uno.XComponentContext;

/**
 * @author hovik
 *
 */
public class SaveAsController extends Controller {

	protected static HashMap<XComponentContext, SaveAsController> instanceList = new HashMap<XComponentContext, SaveAsController>();

	/**
	 * @param context
	 */
	public SaveAsController(XComponentContext context) {
		super(context);
		dialog = new SaveAsDialog( this );
		SwingUtilities.updateComponentTreeUI( dialog );
	}

	/**
	 * Get instance of save controller.
	 * 
	 *  @param context
	 *  
	 *  @return SaveController
	 */
	public static SaveAsController getInstance( XComponentContext context )
	{
		SaveAsController controller = instanceList.get( context );
		if ( controller == null )
		{
			controller = new SaveAsController( context );
		}
		
		return controller;
	}
}
