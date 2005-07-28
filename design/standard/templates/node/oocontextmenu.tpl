 <hr/>
    <a id="menu-export-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-export-ooo' ); return false;">{"Export OpenOffice.org"|i18n("design/admin/popupmenu")}</a>
    <a id="menu-import-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-import-ooo' ); return false;">{"Import OpenOffice.org"|i18n("design/admin/popupmenu")}</a>

{* Export to OOo / OASIS document *}
<form id="menu-form-export-ooo" method="post" action={"/oo/export/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

{* Import OOo / OASIS document *}
<form id="menu-form-import-ooo" method="post" action={"/oo/import/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

