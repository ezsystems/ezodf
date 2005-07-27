 <hr/>
    <a id="menu-export-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-export-ooo' ); return false;">{"Export OpenOffice.org"|i18n("design/admin/popupmenu")}</a>

{* Export to OOo document *}
<form id="menu-form-export-ooo" method="post" action={"/oo/export/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>


